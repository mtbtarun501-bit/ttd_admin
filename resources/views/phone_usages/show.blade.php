@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2>Phone Usage Details</h2>
                <a href="{{ route('phone-usages.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>
            <div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookingModal">
                    <i class="fas fa-plus"></i> Add Booking
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- Phone Details -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Member Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Member Name:</strong> {{ $phoneUsage->member_name }}</p>
                    <p><strong>Mobile Number:</strong> <span class="badge bg-primary fs-6">{{ $phoneUsage->mobile_number }}</span></p>
                    <p><strong>Status:</strong> 
                        @if($phoneUsage->status == 'Active')
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </p>
                    <p><strong>Remarks:</strong> {{ $phoneUsage->remarks ?: 'None' }}</p>
                    <p><strong>Added On:</strong> {{ $phoneUsage->created_at->format('d M Y') }}</p>
                    
                    <a href="{{ route('phone-usages.edit', $phoneUsage->id) }}" class="btn btn-outline-primary btn-sm mt-3"><i class="fas fa-edit"></i> Edit Details</a>
                </div>
            </div>
        </div>

        <!-- Eligibility Matrix -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Eligibility Matrix</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th>Last Booked</th>
                                    <th>Next Eligible</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($phoneUsage->serviceStatuses->sortBy('sevaType.display_order') as $status)
                                <tr>
                                    <td><strong>{{ $status->sevaType->name }}</strong> <br><small class="text-muted">{{ $status->sevaType->cooldown_months }}m cooldown</small></td>
                                    <td>{{ $status->last_booked_date ? $status->last_booked_date->format('d M Y') : 'Never' }}</td>
                                    <td>{{ $status->next_eligible_date ? $status->next_eligible_date->format('d M Y') : '-' }}</td>
                                    <td>
                                        @if(!$status->next_eligible_date || \Carbon\Carbon::today()->greaterThanOrEqualTo($status->next_eligible_date))
                                            <span class="badge bg-success">Eligible</span>
                                        @else
                                            @php
                                                $daysLeft = \Carbon\Carbon::today()->diffInDays($status->next_eligible_date, false);
                                            @endphp
                                            @if($daysLeft <= 15)
                                                <span class="badge bg-warning text-dark">Becomes Eligible Soon</span>
                                            @else
                                                <span class="badge bg-danger">In Cooldown</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking History -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Booking History</h5>
                </div>
                <div class="card-body">
                    @if($phoneUsage->bookingHistories->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Booking Date</th>
                                    <th>Service</th>
                                    <th>Remarks</th>
                                    <th>Added By</th>
                                    <th>Recorded On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($phoneUsage->bookingHistories as $history)
                                <tr>
                                    <td>{{ $history->booking_date->format('d M Y') }}</td>
                                    <td><span class="badge bg-info">{{ $history->sevaType->name }}</span></td>
                                    <td>{{ $history->remarks ?: '-' }}</td>
                                    <td>{{ $history->creator ? $history->creator->name : 'System' }}</td>
                                    <td>{{ $history->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">No booking history recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Booking Modal -->
<div class="modal fade" id="addBookingModal" tabindex="-1" aria-labelledby="addBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBookingModalLabel">Record New Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('phone-usages.bookings.store', $phoneUsage->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Service / Seva <span class="text-danger">*</span></label>
                        <select name="seva_type_id" class="form-select" required>
                            <option value="">Select Service...</option>
                            @foreach($sevas as $seva)
                            <option value="{{ $seva->id }}">{{ $seva->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Booking Date <span class="text-danger">*</span></label>
                        <input type="date" name="booking_date" class="form-control" required value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                        <small class="text-muted">You can only record past or present bookings.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
