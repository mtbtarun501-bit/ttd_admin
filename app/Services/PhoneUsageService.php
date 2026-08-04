<?php

namespace App\Services;

use App\Models\PhoneUsage;
use App\Models\PhoneUsageServiceStatus;
use App\Models\PhoneUsageBookingHistory;
use App\Models\SevaType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PhoneUsageService
{
    /**
     * Create a new phone usage record and initialize its seva statuses.
     */
    public function createPhoneUsage(array $data, array $sevaDates): PhoneUsage
    {
        return DB::transaction(function () use ($data, $sevaDates) {
            $phoneUsage = PhoneUsage::create([
                'member_name' => $data['member_name'],
                'mobile_number' => $data['mobile_number'],
                'status' => $data['status'] ?? 'Active',
                'remarks' => $data['remarks'] ?? null,
            ]);

            $sevas = SevaType::all();

            foreach ($sevas as $seva) {
                $lastBookedDateStr = $sevaDates[$seva->id] ?? null;
                $lastBookedDate = $lastBookedDateStr ? Carbon::parse($lastBookedDateStr) : null;
                
                $nextEligibleDate = $this->calculateNextEligibleDate($lastBookedDate, $seva->cooldown_months);

                PhoneUsageServiceStatus::create([
                    'phone_usage_id' => $phoneUsage->id,
                    'seva_type_id' => $seva->id,
                    'last_booked_date' => $lastBookedDate,
                    'next_eligible_date' => $nextEligibleDate,
                ]);
            }

            return $phoneUsage;
        });
    }

    /**
     * Calculate the next eligible date based on last booked date and cooldown.
     */
    public function calculateNextEligibleDate(?Carbon $lastBookedDate, int $cooldownMonths): ?Carbon
    {
        if (!$lastBookedDate) {
            return null; // Eligible immediately
        }

        return $lastBookedDate->copy()->addMonths($cooldownMonths);
    }

    /**
     * Add a booking to history and update the service status.
     */
    public function addBooking(PhoneUsage $phoneUsage, int $sevaTypeId, string $bookingDateStr, ?string $remarks, ?int $userId = null): PhoneUsageBookingHistory
    {
        return DB::transaction(function () use ($phoneUsage, $sevaTypeId, $bookingDateStr, $remarks, $userId) {
            $bookingDate = Carbon::parse($bookingDateStr);
            $seva = SevaType::findOrFail($sevaTypeId);

            // Add history
            $history = PhoneUsageBookingHistory::create([
                'phone_usage_id' => $phoneUsage->id,
                'seva_type_id' => $sevaTypeId,
                'booking_date' => $bookingDate,
                'remarks' => $remarks,
                'created_by' => $userId,
            ]);

            // Update status
            $status = PhoneUsageServiceStatus::where('phone_usage_id', $phoneUsage->id)
                ->where('seva_type_id', $sevaTypeId)
                ->firstOrFail();

            $nextEligibleDate = $this->calculateNextEligibleDate($bookingDate, $seva->cooldown_months);

            $status->update([
                'last_booked_date' => $bookingDate,
                'next_eligible_date' => $nextEligibleDate,
            ]);

            return $history;
        });
    }

    /**
     * Get a list of Seva Types that are eligible to be booked today for a given phone usage.
     */
    public function getEligibleSevasToday(PhoneUsage $phoneUsage)
    {
        $today = Carbon::today();
        
        return $phoneUsage->serviceStatuses()->with('sevaType')->get()->filter(function ($status) use ($today) {
            // If next_eligible_date is null, it's eligible.
            // If today is greater than or equal to next_eligible_date, it's eligible.
            if (!$status->next_eligible_date) {
                return true;
            }
            return $today->greaterThanOrEqualTo($status->next_eligible_date);
        })->map(function ($status) {
            return $status->sevaType;
        });
    }
}
