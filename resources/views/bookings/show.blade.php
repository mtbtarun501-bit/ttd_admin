@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:var(--temple-dark-brown); font-weight:700;"><i class="fas fa-ticket-alt me-2"></i> Booking Details</h4>
        <a href="{{ route('bookings.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="row">
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm border-0" style="border-radius:15px;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 text-center">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Booking Category</h6>
                    <h3 class="fw-bold mb-0" style="color:var(--temple-gold);">
                        {{ $booking->bookingType->name ?? 'N/A' }}
                    </h3>
                </div>
                <div class="card-body">
                    <hr class="text-muted opacity-25">
                    <table class="table table-borderless mt-2">
                        <tr>
                            <td class="text-muted fw-bold">Booking No:</td>
                            <td class="fw-bold text-dark fs-5">{{ $booking->booking_no }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Primary Booker:</td>
                            <td>{{ $booking->devotee->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Booking Date:</td>
                            <td>{{ \Carbon\Carbon::parse($booking->booking_date)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Preferred Date:</td>
                            <td>{{ $booking->preferred_date ? \Carbon\Carbon::parse($booking->preferred_date)->format('d M Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Status:</td>
                            <td>
                                @if($booking->status == 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($booking->status == 'confirmed')
                                    <span class="badge bg-success">Confirmed</span>
                                @elseif($booking->status == 'cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($booking->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    @if($booking->remarks)
                        <div class="mt-3 p-3 bg-light rounded border">
                            <span class="text-muted fw-bold d-block mb-1">Remarks:</span>
                            {{ $booking->remarks }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-7 mb-4">
            <div class="card shadow-sm border-0" style="border-radius:15px; height: 100%;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold" style="color:var(--temple-maroon);">
                        <i class="fas fa-users me-2"></i> Attendees ({{ $booking->attendees->count() }})
                    </h5>
                </div>
                <div class="card-body p-0 mt-3">
                    @if($booking->attendees->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background:rgba(128, 0, 0, 0.05);">
                                    <tr>
                                        <th class="ps-4">Name</th>
                                        <th>Relation</th>
                                        <th>Age/Gender</th>
                                        <th>Aadhaar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($booking->attendees as $attendee)
                                        <tr>
                                            <td class="ps-4 fw-bold">{{ $attendee->name }}</td>
                                            <td>
                                                @if($attendee->is_head_of_family)
                                                    <span class="badge bg-primary">Head</span>
                                                @else
                                                    <span class="badge bg-info text-dark">Family Member</span>
                                                @endif
                                            </td>
                                            <td>{{ $attendee->age }} / {{ $attendee->gender }}</td>
                                            <td>{{ $attendee->aadhaar ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">
                            <p class="mb-0"><i class="fas fa-info-circle me-2"></i> No attendees found for this booking.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
