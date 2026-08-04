@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:var(--temple-dark-brown); font-weight:700;">
            <i class="fas fa-user-plus me-2"></i> Add Family Member to {{ $head->name }}
        </h4>
        <a href="{{ route('devotees.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> You are adding a family member for the Head of Family: <strong>{{ $head->name }}</strong>. Their address and phone have been pre-filled for convenience.
    </div>

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
            <form action="{{ route('devotees.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="head_devotee_id" value="{{ $head->id }}">
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Age <span class="text-danger">*</span></label>
                        <input type="number" name="age" class="form-control" value="{{ old('age') }}" required min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Aadhaar Number</label>
                        <input type="text" name="aadhaar" class="form-control" value="{{ old('aadhaar') }}" maxlength="12">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $head->phone) }}" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Gothram</label>
                        <input type="text" name="gothram" class="form-control" value="{{ old('gothram', $head->gothram) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">City</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city', $head->city) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">State</label>
                        <input type="text" name="state" class="form-control" value="{{ old('state', $head->state) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">PIN Code</label>
                        <input type="text" name="pin_code" class="form-control" value="{{ old('pin_code', $head->pin_code) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Full Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $head->address) }}</textarea>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn text-white px-5 py-2" style="background:var(--temple-gold); font-weight:600;"><i class="fas fa-save me-2"></i> Save Family Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
