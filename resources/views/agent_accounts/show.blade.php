@extends('layouts.admin')

@section('content')
@php
    $monthOptions = collect(range(1, 12))->map(function($m){ return ['n' => $m, 'label' => \Carbon\Carbon::create(null, $m, 1)->format('M')]; });
@endphp

<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 style="color:var(--temple-dark-brown); font-weight:700;">
                <i class="fas fa-file-invoice-dollar me-2"></i> Agent Ledger: {{ $agent->name }}
                <span class="badge {{ $agent->agent_type === \App\Models\Agent::TYPE_IN_PARTNER ? 'bg-primary' : 'bg-secondary' }}" style="vertical-align: middle;">{{ $agent->agent_type_label }}</span>
            </h4>
            <span class="text-muted"><i class="fas fa-phone me-1"></i>{{ $agent->phone }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="GET" action="{{ route('agent-accounts.show', $agent->id) }}" class="d-flex align-items-center gap-2">
                <select name="month" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All Year</option>
                    @foreach($monthOptions as $mo)
                        <option value="{{ sprintf('%04d-%02d', $year, $mo['n']) }}" {{ $month == sprintf('%04d-%02d', $year, $mo['n']) ? 'selected' : '' }}>{{ $mo['label'] }}</option>
                    @endforeach
                </select>
                <select name="year" class="form-select form-select-sm" style="width:auto;">
                    @foreach(range(now()->year, now()->year - 3) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Go</button>
            </form>
            <a href="{{ route('agent-accounts.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Ledger</a>
        </div>
    </div>

    <!-- Year Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius:15px; border-left: 5px solid var(--temple-gold) !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Bookings ({{ $year }})</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ $yearTotals->bookings }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius:15px; border-left: 5px solid #0dcaf0 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Tickets ({{ $year }})</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ $yearTotals->tickets }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius:15px; border-left: 5px solid #dc3545 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Total Spent</h6>
                    <h3 class="fw-bold mb-0 text-danger">₹{{ number_format($yearTotals->spent, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius:15px; border-left: 5px solid #198754 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Total Earned</h6>
                    <h3 class="fw-bold mb-0 text-success">₹{{ number_format($yearTotals->earned, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Matrix -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius:15px;">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold" style="color:var(--temple-maroon);"><i class="fas fa-calendar-alt me-2"></i> Monthly Ledger — {{ $year }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th>Month</th>
                            <th class="text-center">Bookings</th>
                            <th class="text-center">Tickets</th>
                            <th class="text-end">Spent (Rs)</th>
                            <th class="text-end">Earned (Rs)</th>
                            <th class="text-end">Total Amount (Rs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $monthNum => $row)
                            <tr>
                                <td class="fw-bold">{{ \Carbon\Carbon::create(null, $monthNum, 1)->format('M') }}</td>
                                <td class="text-center">{{ $row->bookings_count }}</td>
                                <td class="text-center">{{ $row->tickets }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($row->spent, 0) }}</td>
                                <td class="text-end text-success fw-bold">₹{{ number_format($row->earned, 0) }}</td>
                                <td class="text-end">₹{{ number_format($row->total, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot style="background:rgba(128, 0, 0, 0.08);">
                        <tr class="fw-bold">
                            <td>TOTAL</td>
                            <td class="text-center">{{ $yearTotals->bookings }}</td>
                            <td class="text-center">{{ $yearTotals->tickets }}</td>
                            <td class="text-end text-danger">₹{{ number_format($yearTotals->spent, 0) }}</td>
                            <td class="text-end text-success">₹{{ number_format($yearTotals->earned, 0) }}</td>
                            <td class="text-end">₹{{ number_format($yearTotals->total, 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Transaction Detail -->
    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold" style="color:var(--temple-maroon);">
                <i class="fas fa-list-alt me-2"></i> Transactions {{ $month ? '— ' . \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') : '— ' . $year . ' (All Year)' }}
            </h5>
        </div>
        <div class="card-body">
            @if($transactions->count() > 0)
                <div class="table-responsive mt-2">
                    <table class="table table-hover align-middle">
                        <thead style="background:rgba(128, 0, 0, 0.05);">
                            <tr>
                                <th>Date</th>
                                <th>Booking No</th>
                                <th>Ticket Type</th>
                                <th>Devotee</th>
                                <th class="text-center">Tickets</th>
                                <th class="text-end">Spent (Rs)</th>
                                <th class="text-end">Earned (Rs)</th>
                                <th class="text-end">Total (Rs)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $booking)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($booking->booking_date)->format('d M Y') }}</td>
                                <td class="fw-bold text-primary">{{ $booking->booking_no }}</td>
                                <td>{{ $booking->bookingType->name ?? 'N/A' }}</td>
                                <td>{{ $booking->devotee->name ?? 'N/A' }}</td>
                                <td class="text-center">{{ $booking->ticket_count }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($booking->total_amount - $booking->service_charge, 2) }}</td>
                                <td class="text-end text-success fw-bold">₹{{ number_format($booking->service_charge, 2) }}</td>
                                <td class="text-end fw-bold">₹{{ number_format($booking->total_amount, 2) }}</td>
                                <td>
                                    @if($booking->status == 'confirmed')
                                        <span class="badge bg-success">Confirmed</span>
                                    @elseif($booking->status == 'completed')
                                        <span class="badge bg-primary">Completed</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:rgba(128, 0, 0, 0.08);">
                            <tr class="fw-bold">
                                <td colspan="4">TOTAL</td>
                                <td class="text-center">{{ $transactions->sum('ticket_count') }}</td>
                                <td class="text-end text-danger">₹{{ number_format($transactions->sum(fn($b) => $b->total_amount - $b->service_charge), 2) }}</td>
                                <td class="text-end text-success">₹{{ number_format($transactions->sum('service_charge'), 2) }}</td>
                                <td class="text-end">₹{{ number_format($transactions->sum('total_amount'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-folder-open fs-1 mb-3 opacity-50"></i>
                    <h4>No transactions found</h4>
                    <p>No confirmed/completed bookings recorded for this period.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection