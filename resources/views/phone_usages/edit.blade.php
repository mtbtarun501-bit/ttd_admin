@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Edit Mobile Number</h2>
            <a href="{{ route('phone-usages.show', $phoneUsage->id) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Details</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('phone-usages.update', $phoneUsage->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Member Name <span class="text-danger">*</span></label>
                        <input type="text" name="member_name" class="form-control @error('member_name') is-invalid @enderror" value="{{ old('member_name', $phoneUsage->member_name) }}" required>
                        @error('member_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number', $phoneUsage->mobile_number) }}" required>
                        @error('mobile_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="Active" {{ old('status', $phoneUsage->status) == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ old('status', $phoneUsage->status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Remarks</label>
                        <input type="text" name="remarks" class="form-control @error('remarks') is-invalid @enderror" value="{{ old('remarks', $phoneUsage->remarks) }}">
                        @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Update Details</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
