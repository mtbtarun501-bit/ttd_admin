<?php

namespace App\Imports;

use App\Models\Devotee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;

class DevoteeImport implements ToModel, WithHeadingRow
{
    /**
     * Family label => head id resolved in this import session.
     */
    protected array $familyHeadIds = [];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Require at least a name.
        if (empty($row['name'])) {
            return null;
        }

        // The referred agent earns credit. The same agent can refer many families,
        // so we resolve (or create) ONE shared agent record per referred name.
        $referredAgentId = $this->resolveReferredAgent($row['referred'] ?? null);

        $familyLabel = trim((string) ($row['family'] ?? ''));
        $familyKey = $this->normalizeName($familyLabel);

        $headId = null;

        if ($familyLabel !== '') {
            if (isset($this->familyHeadIds[$familyKey])) {
                $headId = $this->familyHeadIds[$familyKey];
            } else {
                // First row of this family becomes the family head. The head is
                // a real person (taken from this row), never a label stub, so
                // the index never shows duplicate head rows for one household.
                $headId = $this->resolveFamilyHeadFromRow($row, $referredAgentId);
                $this->familyHeadIds[$familyKey] = $headId;

                // This row was consumed as the head, do not create it again.
                return null;
            }
        }

        // Check if Aadhaar or Phone already exists to prevent duplicates.
        $existing = null;
        if (!empty($row['aadhaar'])) {
            $existing = Devotee::where('aadhaar', $row['aadhaar'])->first();
        }
        if (!$existing && !empty($row['phone'])) {
            $existing = Devotee::where('phone', $row['phone'])->first();
        }

        if ($existing) {
            return null;
        }

        return new Devotee([
            'user_id' => Auth::id() ?? 1,
            'name' => $row['name'],
            'age' => $row['age'] ?? null,
            'gender' => ucfirst(strtolower($row['gender'] ?? '')),
            'aadhaar' => $row['aadhaar'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'city' => $row['city'] ?? null,
            'state' => $row['state'] ?? null,
            'pin_code' => $row['pincode'] ?? null,
            'gothram' => $row['gothram'] ?? null,
            'remarks' => $row['remarks'] ?? null,
            'is_head_of_family' => false,
            'head_devotee_id' => $headId,
            // A standalone individual is credited directly; family members are
            // credited through their family head.
            'referred_devotee_id' => $headId ? null : $referredAgentId,
        ]);
    }

    /**
     * Find or create the referred agent (credit owner) by name.
     */
    protected function resolveReferredAgent(?string $referredName): ?int
    {
        $referredName = trim((string) $referredName);
        if ($referredName === '') {
            return null;
        }

        $agent = Devotee::where('name', 'like', $referredName)->first();

        if ($agent) {
            if (!$agent->is_head_of_family) {
                $agent->is_head_of_family = true;
                $agent->save();
            }

            return $agent->id;
        }

        $agent = Devotee::create([
            'user_id' => Auth::id() ?? 1,
            'name' => $referredName,
            'age' => 30,
            'gender' => 'Unknown',
            'is_head_of_family' => true,
            'remarks' => 'Auto-created as referred agent from Excel Import',
        ]);

        return $agent->id;
    }

    /**
     * Resolve the family head from the first member row of a family.
     * Reuses the referred agent (or any existing top-level head) when the name
     * matches, otherwise creates ONE head record from this row's real data.
     */
    protected function resolveFamilyHeadFromRow(array $row, ?int $referredAgentId): int
    {
        $rowName = trim($row['name']);

        // If the referred agent is also this member, the agent IS the family
        // head. No separate head record, no self-referral.
        if ($referredAgentId) {
            $agent = Devotee::find($referredAgentId);
            if ($agent && $this->normalizeName($agent->name) === $this->normalizeName($rowName)) {
                $this->fillHeadFromRow($agent, $row, null);
                return $agent->id;
            }
        }

        // Reuse an existing top-level head with the same name (covers re-imports).
        $existing = Devotee::whereNull('head_devotee_id')
            ->where('is_head_of_family', true)
            ->get()
            ->first(fn ($d) => $this->normalizeName($d->name) === $this->normalizeName($rowName));

        if ($existing) {
            $this->fillHeadFromRow($existing, $row, $referredAgentId);
            return $existing->id;
        }

        $head = Devotee::create([
            'user_id' => Auth::id() ?? 1,
            'name' => $rowName,
            'age' => $row['age'] ?? 30,
            'gender' => ucfirst(strtolower($row['gender'] ?? 'Unknown')),
            'aadhaar' => $row['aadhaar'] ?? null,
            'phone' => $row['phone'] ?? null,
            'is_head_of_family' => true,
            'referred_devotee_id' => $referredAgentId,
            'remarks' => 'Auto-created family head from Excel Import',
        ]);

        return $head->id;
    }

    /**
     * Copy any missing member fields onto an existing/reused head.
     * When the head is not the referred agent itself, re-attach the family
     * credit to the row's referred agent (mirrors previous behaviour).
     */
    protected function fillHeadFromRow(Devotee $head, array $row, ?int $referredAgentId): void
    {
        $updates = [];

        if (empty($head->aadhaar) && !empty($row['aadhaar'])) {
            $updates['aadhaar'] = $row['aadhaar'];
        }
        if (empty($head->age) && !empty($row['age'])) {
            $updates['age'] = $row['age'];
        }
        if (empty($head->phone) && !empty($row['phone'])) {
            $updates['phone'] = $row['phone'];
        }
        if (empty($head->gender) || $head->gender === 'Unknown') {
            if (!empty($row['gender'])) {
                $updates['gender'] = ucfirst(strtolower($row['gender']));
            }
        }

        if ($referredAgentId && (int) $head->referred_devotee_id !== $referredAgentId && (int) $referredAgentId !== $head->id) {
            $updates['referred_devotee_id'] = $referredAgentId;
        }

        if ($updates) {
            $head->update($updates);
        }
    }

    /**
     * Lowercase alphanumeric name key used to match across spellings/spacing.
     */
    protected function normalizeName(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
    }
}