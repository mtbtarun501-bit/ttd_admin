@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:var(--temple-dark-brown); font-weight:700;"><i class="fas fa-calendar-plus me-2"></i> Create Booking</h4>
        <a href="{{ route('bookings.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    @if (session('error'))
        <div class="alert alert-danger fw-bold shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body p-4">
            <form action="{{ route('bookings.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Select Devotee <span class="text-danger">*</span></label>
                        <select name="devotee_id" class="form-select" required>
                            <option value="">-- Choose Devotee --</option>
                            @foreach($devotees as $devotee)
                                <option value="{{ $devotee->id }}" {{ old('devotee_id') == $devotee->id ? 'selected' : '' }}>
                                    {{ $devotee->name }} ({{ $devotee->phone }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">If devotee is not listed, <a href="{{ route('devotees.create') }}">register them first</a>.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Booking Category <span class="text-danger">*</span></label>
                        <select name="booking_type_id" class="form-select" required>
                            <option value="">-- Choose Category --</option>
                            @foreach($bookingTypes as $type)
                                <option value="{{ $type->id }}" {{ old('booking_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }} (Wait: {{ $type->waiting_days }} days)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Booking Date <span class="text-danger">*</span></label>
                        <input type="date" name="booking_date" class="form-control" value="{{ old('booking_date', date('Y-m-d')) }}" required min="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Preferred Darshan/Seva Date</label>
                        <input type="date" name="preferred_date" class="form-control" value="{{ old('preferred_date') }}" min="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-12 mt-3" id="attendees-container" style="display:none;">
                        <label class="form-label fw-bold text-primary border-bottom pb-2 w-100">Select Attendees (Family Members)</label>
                        <div id="attendees-list" class="row mt-2">
                            <!-- Checkboxes injected here -->
                        </div>
                    </div>

                    @hasanyrole('Super Admin|Operator')
                    <div class="col-md-12 mt-3">
                        <label class="form-label fw-bold">Booked By (Admin) <span class="text-danger">*</span></label>
                        <select name="created_by" class="form-select" required>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" {{ (old('created_by') ?? auth()->id()) == $admin->id ? 'selected' : '' }}>
                                    {{ $admin->name }} ({{ implode(', ', $admin->getRoleNames()->toArray()) }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select the administrator who is officially processing this booking.</small>
                    </div>
                    @endhasanyrole

                    <div class="col-12 mt-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn text-white px-5 py-2" style="background:var(--temple-gold); font-weight:600;">
                        <i class="fas fa-check-circle me-2"></i> Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const devoteeSelect = document.querySelector('select[name="devotee_id"]');
    const attendeesContainer = document.getElementById('attendees-container');
    const attendeesList = document.getElementById('attendees-list');

    function fetchFamilyMembers(devoteeId) {
        if (!devoteeId) {
            attendeesContainer.style.display = 'none';
            attendeesList.innerHTML = '';
            return;
        }

        fetch(`/api/devotees/${devoteeId}/family`)
            .then(response => response.json())
            .then(data => {
                attendeesList.innerHTML = '';
                if (data.length > 0) {
                    attendeesContainer.style.display = 'block';
                    data.forEach(member => {
                        const pref = member.preferred_booking_type ? `<span class="badge bg-info ms-2">${member.preferred_booking_type.name}</span>` : '';
                        const headBadge = member.is_head_of_family ? `<span class="badge bg-primary ms-1">Head</span>` : '';
                        const html = `
                            <div class="col-md-4 mb-3">
                                <div class="form-check fs-5">
                                    <input class="form-check-input" type="checkbox" name="attendee_ids[]" value="${member.id}" id="attendee_${member.id}" checked>
                                    <label class="form-check-label" for="attendee_${member.id}">
                                        ${member.name} ${headBadge} <br><small class="text-muted fs-6">${member.phone || 'No Phone'}</small> ${pref}
                                    </label>
                                </div>
                            </div>
                        `;
                        attendeesList.insertAdjacentHTML('beforeend', html);
                    });
                } else {
                    attendeesContainer.style.display = 'none';
                }
            })
            .catch(err => console.error(err));
    }

    devoteeSelect.addEventListener('change', function() {
        fetchFamilyMembers(this.value);
    });

    // On load if old value exists
    if (devoteeSelect.value) {
        fetchFamilyMembers(devoteeSelect.value);
    }
});
</script>
@endpush
