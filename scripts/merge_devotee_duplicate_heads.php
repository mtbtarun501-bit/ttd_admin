<?php

/**
 * One-time cleanup: merge duplicated family head rows created by the old
 * label-based tabular import.
 *
 * Old imports created two top-level heads per family:
 *   1. the referred-agent stub  (remarks: "Auto-created as referred agent from Excel Import")
 *   2. a label-based family head (remarks: "Auto-created family group from Excel Import")
 * plus a duplicate member row that repeated the agent's name.
 *
 * This script merges them safely (heuristic: only when a member inside the
 * label head shares the SAME normalized name as the referred agent). Legitimate
 * separate households that happen to share an agent are left untouched.
 *
 * Run:  php scripts/merge_devotee_duplicate_heads.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Devotee;
use Illuminate\Support\Facades\DB;

function normalizeName(string $name): string
{
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
}

$merged = 0;
$skipped = 0;

DB::beginTransaction();

// Old label-based family-group heads that are credited to an agent.
$labelHeads = Devotee::whereNull('head_devotee_id')
    ->where('is_head_of_family', true)
    ->whereNotNull('referred_devotee_id')
    ->where('remarks', 'like', '%Auto-created family group from Excel Import%')
    ->get();

foreach ($labelHeads as $fg) {
    $agent = $fg->referredAgent;

    if (!$agent) {
        $skipped++;
        continue;
    }

    // Find the member row that is actually the same person as the agent.
    $dup = null;
    foreach ($fg->familyMembers as $member) {
        if (normalizeName($member->name) === normalizeName($agent->name)) {
            $dup = $member;
            break;
        }
    }

    if (!$dup) {
        // No member matching the agent: this is a legitimate separate family
        // sharing the same referred agent. Leave it alone.
        $skipped++;
        continue;
    }

    // Fill any missing detail on the agent from the duplicate member row.
    $updates = [];
    if (empty($agent->aadhaar) && !empty($dup->aadhaar)) {
        $updates['aadhaar'] = $dup->aadhaar;
    }
    if (empty($agent->age) && !empty($dup->age)) {
        $updates['age'] = $dup->age;
    }
    if (empty($agent->gender) || $agent->gender === 'Unknown') {
        if (!empty($dup->gender)) {
            $updates['gender'] = $dup->gender;
        }
    }
    if (empty($agent->phone) && !empty($dup->phone)) {
        $updates['phone'] = $dup->phone;
    }
    if ($updates) {
        $agent->update($updates);
    }

    // Re-point all other members of the label head to the agent.
    Devotee::where('head_devotee_id', $fg->id)
        ->where('id', '!=', $dup->id)
        ->update(['head_devotee_id' => $agent->id]);

    // Remove the duplicate member row and the label head.
    $dup->forceDelete();
    $fg->forceDelete();

    echo "Merged: \"{$fg->name}\" (id={$fg->id}) + member \"{$dup->name}\" (id={$dup->id}) into head \"{$agent->name}\" (id={$agent->id})\n";
    $merged++;
}

DB::commit();

echo "\nDone. {$merged} duplicate head(s) merged, {$skipped} legitimate family(ies) kept untouched.\n";