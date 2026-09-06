@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 style="color:var(--temple-dark-brown); font-weight:700;"><i class="fas fa-file-excel me-2"></i> Import Monthly Revenue</h4>
        <a href="{{ route('revenues.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Revenues</a>
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
            <div class="alert alert-info mb-4">
                <strong><i class="fas fa-info-circle"></i> Instructions:</strong>
                <p class="mb-0">Upload the monthly Excel file from agents containing columns like: <code>Date, Agent, Booking Reference, Service, Member Name, Ticket Cost, Service Charge</code>. The system will group members by booking reference and calculate the total company revenue (sum of Service Charges).</p>
            </div>

            <form action="{{ route('revenues.import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row g-4 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Batch Month <span class="text-danger">*</span></label>
                        <select name="batch_month" class="form-select" required>
                            <option value="">Select Month</option>
                            @for($i = 0; $i < 12; $i++)
                                @php
                                    $date = \Carbon\Carbon::now()->subMonths($i);
                                    $value = $date->format('F Y');
                                @endphp
                                <option value="{{ $value }}">{{ $value }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold">Excel File (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx, .xls, .csv" required>
                    </div>

                    <div class="col-md-2 text-end">
                        <button type="submit" class="btn btn-primary w-100" style="background:#0056b3; border:none; padding:10px;">
                            <i class="fas fa-eye me-2"></i> Preview Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
