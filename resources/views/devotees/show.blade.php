@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:var(--temple-dark-brown); font-weight:700;">
            <i class="fas fa-user-circle me-2"></i> Devotee Details
        </h4>
        <div>
            @if($devotee->is_head_of_family)
                <a href="{{ route('devotees.create_family_member', $devotee->id) }}" class="btn btn-success me-2"><i class="fas fa-user-plus"></i> Add Family Member</a>
            @endif
            <a href="{{ route('devotees.edit', $devotee->id) }}" class="btn text-white me-2" style="background:var(--temple-gold);"><i class="fas fa-edit"></i> Edit</a>
            <a href="{{ route('devotees.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="row">
        <!-- Devotee Details Card -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm border-0" style="border-radius:15px;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold text-primary mb-0">
                        {{ $devotee->name }} 
                        @if($devotee->is_head_of_family)
                            <span class="badge bg-primary ms-2 fs-6"><i class="fas fa-crown"></i> Head of Family</span>
                        @elseif($devotee->head_devotee_id && $devotee->headFamilyMember)
                            <span class="badge bg-info ms-2 fs-6">Family of: {{ $devotee->headFamilyMember->name }}</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mt-3">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted fw-bold mb-1">Phone Number</label>
                            <div>{{ $devotee->phone ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted fw-bold mb-1">Email</label>
                            <div>{{ $devotee->email ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted fw-bold mb-1">Aadhaar</label>
                            <div>{{ $devotee->aadhaar ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="text-muted fw-bold mb-1">Age / Gender</label>
                            <div>{{ $devotee->age }} Yrs / {{ $devotee->gender }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted fw-bold mb-1">Gothram</label>
                            <div>{{ $devotee->gothram ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted fw-bold mb-1">Preferred Ticket</label>
                            <div>
                                @if($devotee->preferredBookingType)
                                    <span class="badge bg-secondary">{{ $devotee->preferredBookingType->name }}</span>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="text-muted fw-bold mb-1">Address</label>
                            <div>
                                {{ $devotee->address }}<br>
                                {{ $devotee->city ? $devotee->city . ', ' : '' }} {{ $devotee->state }} {{ $devotee->pin_code }}
                            </div>
                        </div>
                        
                        @if($devotee->remarks)
                        <div class="col-md-12">
                            <label class="text-muted fw-bold mb-1">Remarks</label>
                            <div>{{ $devotee->remarks }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Family Members Section (Only show if this is the Head) -->
        @if($devotee->is_head_of_family)
            <div class="col-md-12">
                <div class="card shadow-sm border-0" style="border-radius:15px;">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-3">
                        <h5 class="fw-bold" style="color:var(--temple-maroon);">
                            <i class="fas fa-users me-2"></i> Family Members
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($devotee->familyMembers->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead style="background:rgba(128, 0, 0, 0.05);">
                                        <tr>
                                            <th class="ps-4">Name</th>
                                            <th>Relation</th>
                                            <th>Age/Gender</th>
                                            <th>Pref. Ticket</th>
                                            <th class="pe-4 text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($devotee->familyMembers as $member)
                                            <tr>
                                                <td class="ps-4 fw-bold">{{ $member->name }}</td>
                                                <td><span class="badge bg-info text-dark">Family Member</span></td>
                                                <td>{{ $member->age }} / {{ $member->gender }}</td>
                                                <td>
                                                    @if($member->preferredBookingType)
                                                        <span class="badge bg-secondary">{{ $member->preferredBookingType->name }}</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <a href="{{ route('devotees.show', $member->id) }}" class="btn btn-sm btn-light border"><i class="fas fa-eye text-primary"></i></a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-4 text-center text-muted">
                                <p class="mb-0"><i class="fas fa-info-circle me-2"></i> No family members added yet.</p>
                                <a href="{{ route('devotees.create_family_member', $devotee->id) }}" class="btn btn-sm btn-outline-success mt-2">Add First Member</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
