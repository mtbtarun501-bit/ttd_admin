@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:var(--temple-dark-brown); font-weight:700;"><i class="fas fa-file-invoice-dollar me-2"></i> Agent Ledger: {{ $agent->name }}</h4>
        <a href="{{ route('agent-accounts.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Ledger</a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius:15px; border-left: 5px solid var(--temple-gold) !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Total Bookings</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ $bookings->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius:15px; border-left: 5px solid #0dcaf0 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Total Tickets</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalTickets }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius:15px; border-left: 5px solid #dc3545 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Total Commission</h6>
                    <h3 class="fw-bold mb-0 text-danger">₹{{ number_format($totalCommission, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius:15px; border-left: 5px solid #198754 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Total Amount</h6>
                    <h3 class="fw-bold mb-0 text-success">₹{{ number_format($totalAmount, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold" style="color:var(--temple-maroon);">
                <i class="fas fa-list-alt me-2"></i> Transaction History
            </h5>
        </div>
        <div class="card-body">
            @if($bookings->count() > 0)
                <div class="table-responsive mt-2">
                    <table class="table table-hover align-middle">
                        <thead style="background:rgba(128, 0, 0, 0.05);">
                            <tr>
                                <th>Date</th>
                                <th>Booking No</th>
                                <th>Ticket Type</th>
                                <th>Tickets</th>
                                <th>Commission</th>
                                <th>Total Amount</th>
                                <th>Admin Account (Booked By)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $booking)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($booking->created_at)->format('d M Y, h:i A') }}</td>
                                    <td class="fw-bold text-primary">{{ $booking->booking_no }}</td>
                                    <td>{{ $booking->bookingType->name ?? 'N/A' }}</td>
                                    <td>{{ $booking->ticket_count }}</td>
                                    <td class="text-danger fw-bold">₹{{ number_format($booking->service_charge, 2) }}</td>
                                    <td class="text-success fw-bold">₹{{ number_format($booking->total_amount, 2) }}</td>
                                    <td><span class="badge bg-secondary"><i class="fas fa-user-shield me-1"></i> {{ $booking->booked_by_name ?? 'N/A' }}</span></td>
                                    <td>
                                        @if($booking->status == 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($booking->status == 'confirmed')
                                            <span class="badge bg-success">Confirmed</span>
                                        @elseif($booking->status == 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @elseif($booking->status == 'completed')
                                            <span class="badge bg-primary">Completed</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($booking->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-folder-open fs-1 mb-3 opacity-50"></i>
                    <h4>No transactions found</h4>
                    <p>This agent hasn't booked any tickets yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
