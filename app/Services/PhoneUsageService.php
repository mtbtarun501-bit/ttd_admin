<?php

namespace App\Services;

use App\Models\PhoneUsage;
use App\Models\PhoneUsageServiceStatus;
use App\Models\PhoneUsageBookingHistory;
use App\Models\SevaType;
use App\Models\Booking;
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
     * Includes a 5-day buffer (+5 days) for every seva type.
     */
    public function calculateNextEligibleDate(?Carbon $lastBookedDate, int $cooldownMonths): ?Carbon
    {
        if (!$lastBookedDate) {
            return null; // Eligible immediately
        }

        return $lastBookedDate->copy()->addMonths($cooldownMonths)->addDays(5);
    }

    /**
     * Add a booking to history and update the service status.
     *
     * WHY this structure:
     *  1. SevaType is read OUTSIDE the transaction to keep lock duration as short as possible.
     *  2. lockForUpdate() on status row prevents concurrent bookings from colliding.
     *  3. max('booking_date') ensures backdated/past bookings do not corrupt eligibility.
     *  4. Returns array with full context so controller avoids extra DB round-trips.
     *
     * @return array{history: PhoneUsageBookingHistory, status: PhoneUsageServiceStatus, seva: SevaType, nextEligibleDate: \Carbon\Carbon|null}
     */
    public function addBooking(PhoneUsage $phoneUsage, int $sevaTypeId, string $bookingDateStr, ?string $remarks, ?int $userId = null): array
    {
        $bookingDate = Carbon::parse($bookingDateStr);
        $seva        = SevaType::findOrFail($sevaTypeId);

        return DB::transaction(function () use ($phoneUsage, $sevaTypeId, $bookingDate, $seva, $remarks, $userId) {
            // 1. Append to booking history
            $history = PhoneUsageBookingHistory::create([
                'phone_usage_id' => $phoneUsage->id,
                'seva_type_id'   => $sevaTypeId,
                'booking_date'   => $bookingDate,
                'remarks'        => $remarks,
                'created_by'     => $userId,
            ]);

            // 2. Lock the status row for this phone + seva
            $status = PhoneUsageServiceStatus::where('phone_usage_id', $phoneUsage->id)
                ->where('seva_type_id', $sevaTypeId)
                ->lockForUpdate()
                ->first();

            // 3. Determine the true latest booking date (skips redundant DB query when booking is today/newer)
            if ($status && $status->last_booked_date && $status->last_booked_date->greaterThan($bookingDate)) {
                $latestBookingDate = PhoneUsageBookingHistory::where('phone_usage_id', $phoneUsage->id)
                    ->where('seva_type_id', $sevaTypeId)
                    ->max('booking_date');
                $latestBookingDate = $latestBookingDate ? Carbon::parse($latestBookingDate) : $bookingDate;
            } else {
                $latestBookingDate = $bookingDate;
            }

            $nextEligibleDate = $this->calculateNextEligibleDate($latestBookingDate, $seva->cooldown_months);

            // 4. Update or create status record
            if (!$status) {
                $status = PhoneUsageServiceStatus::create([
                    'phone_usage_id'    => $phoneUsage->id,
                    'seva_type_id'      => $sevaTypeId,
                    'last_booked_date'  => $latestBookingDate,
                    'next_eligible_date'=> $nextEligibleDate,
                ]);
            } else {
                $status->update([
                    'last_booked_date'  => $latestBookingDate,
                    'next_eligible_date'=> $nextEligibleDate,
                ]);
            }

            return [
                'history'          => $history,
                'status'           => $status,
                'seva'             => $seva,
                'nextEligibleDate' => $nextEligibleDate,
            ];
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

    /**
     * Auto-record a confirmed booking against the phone usage record of the booking's agent.
     *
     * Returns an array describing the outcome so the caller can flash the right message:
     *  ['type' => 'success'|'warning'|'skipped'|'already', 'message' => string]
     */
    public function syncBooking(Booking $booking): array
    {
        $agent = $booking->agent;
        if (!$agent || empty($agent->phone)) {
            return ['type' => 'warning', 'message' => "No agent phone to link phone usage for booking {$booking->booking_no}."];
        }

        $phoneUsage = PhoneUsage::where('mobile_number', $agent->phone)->first();
        if (!$phoneUsage) {
            $phoneUsage = $this->createPhoneUsage([
                'member_name' => $agent->name,
                'mobile_number' => $agent->phone,
                'status' => 'Active',
                'remarks' => 'Auto-created from booking ' . $booking->booking_no,
            ], []);
        }

        $sevaTypeId = $booking->bookingType?->seva_type_id;
        if (!$sevaTypeId) {
            return ['type' => 'skipped', 'message' => "Booking type '{$booking->bookingType?->name}' is not mapped to a seva, so it won't be tracked in Phone Usage."];
        }

        return DB::transaction(function () use ($booking, $phoneUsage, $sevaTypeId, $agent) {
            $already = PhoneUsageBookingHistory::where('booking_id', $booking->id)->exists();
            if ($already) {
                return ['type' => 'already', 'message' => 'Booking already recorded in Phone Usage.'];
            }

            $bookingDate = $booking->preferred_date ? Carbon::parse($booking->preferred_date) : Carbon::today();

            PhoneUsageBookingHistory::create([
                'phone_usage_id' => $phoneUsage->id,
                'seva_type_id' => $sevaTypeId,
                'booking_date' => $bookingDate,
                'remarks' => sprintf(
                    'Booking %s · Agent: %s · %s',
                    $booking->booking_no,
                    $agent->name,
                    $booking->bookingType?->name
                ),
                'created_by' => auth()->id(),
                'booking_id' => $booking->id,
            ]);

            $this->recomputeServiceStatus($phoneUsage, $sevaTypeId);

            return ['type' => 'success', 'message' => "Recorded in Phone Usage under {$phoneUsage->member_name} ({$phoneUsage->mobile_number})."];
        });
    }

    /**
     * Remove any phone usage history rows linked to a booking and recompute the affected seva status.
     * Used when a confirmed/completed booking is cancelled or deleted.
     */
    public function removeBooking(Booking $booking): void
    {
        $histories = PhoneUsageBookingHistory::where('booking_id', $booking->id)->get();
        if ($histories->isEmpty()) {
            return;
        }

        foreach ($histories as $history) {
            $phoneUsageId = $history->phone_usage_id;
            $sevaTypeId = $history->seva_type_id;
            $history->delete();
            $phoneUsage = PhoneUsage::find($phoneUsageId);
            if ($phoneUsage) {
                DB::transaction(function () use ($phoneUsage, $sevaTypeId) {
                    $this->recomputeServiceStatus($phoneUsage, $sevaTypeId);
                });
            }
        }
    }

    /**
     * Recompute last_booked_date / next_eligible_date for a phone+seva from its history rows.
     */
    protected function recomputeServiceStatus(PhoneUsage $phoneUsage, int $sevaTypeId): void
    {
        $status = PhoneUsageServiceStatus::where('phone_usage_id', $phoneUsage->id)
            ->where('seva_type_id', $sevaTypeId)
            ->first();

        if (!$status) {
            return;
        }

        $lastBookedDate = PhoneUsageBookingHistory::where('phone_usage_id', $phoneUsage->id)
            ->where('seva_type_id', $sevaTypeId)
            ->max('booking_date');

        $lastBookedDate = $lastBookedDate ? Carbon::parse($lastBookedDate) : null;
        $nextEligibleDate = $this->calculateNextEligibleDate($lastBookedDate, $status->sevaType->cooldown_months);

        $status->update([
            'last_booked_date' => $lastBookedDate,
            'next_eligible_date' => $nextEligibleDate,
        ]);
    }
}
