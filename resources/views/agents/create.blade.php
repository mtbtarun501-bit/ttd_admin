@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:var(--temple-dark-brown); font-weight:700;"><i class="fas fa-user-plus me-2"></i> Add Agent</h4>
        <a href="{{ route('agents.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body p-4">
            <form action="{{ route('agents.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Agent Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Ram Prasad" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="e.g. 9876543210" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Agent Type <span class="text-danger">*</span></label>
                        <select name="agent_type" class="form-select @error('agent_type') is-invalid @enderror" required>
                            <option value="">-- Select Agent Type --</option>
                            <option value="{{ \App\Models\Agent::TYPE_IN_PARTNER }}" {{ old('agent_type') == \App\Models\Agent::TYPE_IN_PARTNER ? 'selected' : '' }}>In Partner</option>
                            <option value="{{ \App\Models\Agent::TYPE_OUT_PARTNER }}" {{ old('agent_type') == \App\Models\Agent::TYPE_OUT_PARTNER ? 'selected' : '' }}>Out Partner</option>
                        </select>
                        <small class="text-muted">Commission will be calculated based on this partner type.</small>
                        @error('agent_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="1" {{ old('status', 1) ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn text-white px-5 py-2" style="background:var(--temple-gold); font-weight:600;">
                        <i class="fas fa-save me-2"></i> Save Agent
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection