@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:var(--temple-dark-brown); font-weight:700;"><i class="fas fa-plus-circle me-2"></i> Add New Investment</h4>
        <a href="{{ route('investments.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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
            <form action="{{ route('investments.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Investor Name <span class="text-danger">*</span></label>
                        <input type="text" name="investor_name" class="form-control" value="{{ old('investor_name') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required min="0">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Investment Date <span class="text-danger">*</span></label>
                        <input type="date" name="investment_date" class="form-control" value="{{ old('investment_date') ?? date('Y-m-d') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="withdrawn">Withdrawn</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn text-white px-5 py-2" style="background:var(--temple-gold); font-weight:600;">
                        <i class="fas fa-save me-2"></i> Save Investment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
