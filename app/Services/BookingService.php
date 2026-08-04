<?php

namespace App\Services;

use App\Repositories\BookingRepository;
use App\Models\Devotee;
use Illuminate\Support\Str;

class BookingService
{
    protected BookingRepository $repository;
    protected PhoneUsageService $phoneUsageService;

    public function __construct(BookingRepository $repository, PhoneUsageService $phoneUsageService)
    {
        $this->repository = $repository;
        $this->phoneUsageService = $phoneUsageService;
    }

    public function getAllBookings()
    {
        return $this->repository->all(); // Eager load relations later in controller/repo if needed
    }

    public function createBooking(array $data)
    {
        // Find devotee to get phone number
        $devotee = Devotee::findOrFail($data['devotee_id']);

        // Phone usage eligibility check was moved to a separate module.

        // Generate unique booking number
        $data['booking_no'] = 'SGB-' . strtoupper(Str::random(8));
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $data['status'] = 'pending';

        $booking = $this->repository->create($data);
        
        if (isset($data['attendee_ids']) && is_array($data['attendee_ids'])) {
            $booking->attendees()->sync($data['attendee_ids']);
        }

        // Phone usage recording is now handled by the Phone Usage Management module manually.

        return $booking;
    }

    public function updateBooking($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function getBookingById($id)
    {
        return $this->repository->find($id);
    }

    public function deleteBooking($id)
    {
        return $this->repository->delete($id);
    }
}
