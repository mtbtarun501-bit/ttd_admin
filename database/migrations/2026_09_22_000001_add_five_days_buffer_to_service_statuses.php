<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\PhoneUsageServiceStatus;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Recalculate next_eligible_date with a 5-day safety buffer for all existing statuses.
     */
    public function up(): void
    {
        $statuses = PhoneUsageServiceStatus::with('sevaType')->whereNotNull('last_booked_date')->get();

        foreach ($statuses as $status) {
            if ($status->sevaType && $status->last_booked_date) {
                $status->update([
                    'next_eligible_date' => $status->last_booked_date->copy()
                        ->addMonths($status->sevaType->cooldown_months)
                        ->addDays(5),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $statuses = PhoneUsageServiceStatus::with('sevaType')->whereNotNull('last_booked_date')->get();

        foreach ($statuses as $status) {
            if ($status->sevaType && $status->last_booked_date) {
                $status->update([
                    'next_eligible_date' => $status->last_booked_date->copy()
                        ->addMonths($status->sevaType->cooldown_months),
                ]);
            }
        }
    }
};
