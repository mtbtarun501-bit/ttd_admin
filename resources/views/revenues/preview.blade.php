@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:var(--temple-dark-brown); font-weight:700;"><i class="fas fa-search-plus me-2"></i> Import Preview</h4>
        <a href="{{ route('revenues.import') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel Import</a>
    </div>

    @if (!empty($errors) && count($errors) > 0)
        <div class="alert alert-danger">
            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Validation Failed</h5>
            <p>The following errors were found in the uploaded file:</p>
            <ul class="mb-0">
                @foreach ($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($duplicateWarning)
        <div class="alert alert-warning">
            <h5 class="alert-heading"><i class="fas fa-exclamation-circle"></i> Possible Duplicate Import</h5>
            <p class="mb-0">A batch for the month of <strong>{{ $importData['batch_month'] }}</strong> has already been imported. Please confirm you are not uploading duplicate data before proceeding.</p>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-light border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted text-uppercase fw-bold">Total Bookings</h6>
                    <h3 style="color:var(--temple-maroon);">{{ $importData['total_bookings'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted text-uppercase fw-bold">Total Members</h6>
                    <h3 style="color:var(--temple-maroon);">{{ $importData['total_members'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted text-uppercase fw-bold">Total Ticket Cost</h6>
                    <h3>₹{{ number_format($importData['total_ticket_cost'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white border-0 shadow-sm" style="background:var(--temple-gold);">
                <div class="card-body text-center">
                    <h6 class="text-white text-uppercase fw-bold">Total Company Revenue</h6>
                    <h3>₹{{ number_format($importData['total_revenue'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    @php
        $groupedBookings = collect($importData['bookings'])->groupBy('agent_name');
    @endphp

    @foreach($groupedBookings as $agentName => $agentBookings)
        @php
            $agentTicketTotal = $agentBookings->sum('ticket_cost');
            $agentCommissionTotal = $agentBookings->sum('service_charge');
            $agentMemberTotal = $agentBookings->sum('member_count');
        @endphp
        
        <div class="card shadow-sm border-0 mb-4" style="border-radius:15px; overflow:hidden;">
            <div class="card-header pt-3 pb-3 d-flex justify-content-between align-items-center" style="background:var(--temple-maroon); color:white;">
                <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i> Agent: {{ $agentName ?: 'Unknown' }}</h5>
                <span class="badge bg-light text-dark">{{ count($agentBookings) }} Bookings | {{ $agentMemberTotal }} Members</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                            <tr>
                                <th>Reference</th>
                                <th>Service</th>
                                <th class="text-center">Members</th>
                                <th class="text-end">Ticket Cost</th>
                                <th class="text-end">Service Charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($agentBookings as $key => $booking)
                            <tr>
                                <td>{{ $booking['booking_reference'] ?? 'N/A' }}</td>
                                <td>{{ $booking['service_name'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill">{{ $booking['member_count'] }}</span>
                                    <button type="button" class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="collapse" data-bs-target="#members-{{ Str::slug($agentName.'-'.$key) }}">
                                        View Members <i class="fas fa-caret-down"></i>
                                    </button>
                                    <div class="collapse mt-2 text-start" id="members-{{ Str::slug($agentName.'-'.$key) }}">
                                        <ul class="list-group list-group-flush" style="font-size:0.9rem;">
                                            @foreach($booking['members'] as $member)
                                                <li class="list-group-item py-1 px-2 border-0 bg-light rounded mb-1"><i class="fas fa-user-circle text-muted me-2"></i> {{ $member }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </td>
                                <td class="text-end">₹{{ number_format($booking['ticket_cost'], 2) }}</td>
                                <td class="text-end fw-bold" style="color:var(--temple-gold);">₹{{ number_format($booking['service_charge'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:#f8f9fa;">
                            <tr>
                                <th colspan="3" class="text-end">Total for {{ $agentName ?: 'Unknown' }}:</th>
                                <th class="text-end">₹{{ number_format($agentTicketTotal, 2) }}</th>
                                <th class="text-end" style="color:var(--temple-gold); font-size:1.1rem;">₹{{ number_format($agentCommissionTotal, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    <div class="text-end">
        <form action="{{ route('revenues.import.confirm') }}" method="POST">
            @csrf
            @if (!empty($errors) && count($errors) > 0)
                <button type="button" class="btn btn-secondary px-5 py-2" disabled>
                    <i class="fas fa-ban me-2"></i> Fix Errors Before Importing
                </button>
            @else
                <button type="submit" class="btn text-white px-5 py-2" style="background:var(--temple-maroon); font-weight:600;" onclick="return confirm('Are you sure you want to finalize this import? This will create {{ $importData['total_bookings'] }} revenue records.')">
                    <i class="fas fa-check-circle me-2"></i> Confirm & Import Records
                </button>
            @endif
        </form>
    </div>
</div>
@endsection
