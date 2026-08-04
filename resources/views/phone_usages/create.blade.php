@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Add New Mobile Number</h2>
            <a href="{{ route('phone-usages.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('phone-usages.store') }}" method="POST">
                @csrf
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Member Name <span class="text-danger">*</span></label>
                        <input type="text" name="member_name" class="form-control @error('member_name') is-invalid @enderror" value="{{ old('member_name') }}" required>
                        @error('member_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number') }}" required>
                        @error('mobile_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Remarks</label>
                        <input type="text" name="remarks" class="form-control @error('remarks') is-invalid @enderror" value="{{ old('remarks') }}">
                        @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="mb-4">
                <h4 class="mb-3">Initial Last Booked Dates</h4>
                <p class="text-muted small">Select the last booked date for each seva if this mobile number has history. Leave blank if never booked.</p>

                <div class="row">
                    @foreach($sevas as $seva)
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ $seva->name }} <br><small class="text-muted">Cooldown: {{ $seva->cooldown_months }} Months</small></label>
                        <input type="date" name="seva_dates[{{ $seva->id }}]" class="form-control @error('seva_dates.'.$seva->id) is-invalid @enderror" value="{{ old('seva_dates.'.$seva->id) }}">
                        @error('seva_dates.'.$seva->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Mobile Number</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
