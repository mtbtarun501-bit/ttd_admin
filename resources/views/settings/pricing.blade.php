@extends('layouts.admin')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Pricing Settings</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow mb-4" style="border-radius: 12px; border: none;">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between" style="background: var(--temple-maroon); color: white; border-radius: 12px 12px 0 0;">
            <h6 class="m-0 font-weight-bold">Configure Ticket Prices and Commission</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('settings.pricing.update') }}" method="POST">
                @csrf
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Booking Type</th>
                                <th>Price (Rs)</th>
                                <th>In Partner Commission (Rs)</th>
                                <th>Out Partner Commission (Rs)</th>
                                <th>Phone Usage Seva</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookingTypes as $index => $type)
                            <tr>
                                <td class="align-middle">
                                    <strong>{{ $type->name }}</strong>
                                    <input type="hidden" name="prices[{{ $index }}][id]" value="{{ $type->id }}">
                                </td>
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" class="form-control" name="prices[{{ $index }}][price]" value="{{ $type->price }}" required min="0">
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" class="form-control" name="prices[{{ $index }}][in_partner_commission_rate]" value="{{ $type->in_partner_commission_rate }}" required min="0">
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" class="form-control" name="prices[{{ $index }}][out_partner_commission_rate]" value="{{ $type->out_partner_commission_rate }}" required min="0">
                                    </div>
                                </td>
                                <td>
                                    <select class="form-select" name="prices[{{ $index }}][seva_type_id]">
                                        <option value="">— Not tracked —</option>
                                        @foreach($sevas as $seva)
                                        <option value="{{ $seva->id }}" {{ $type->seva_type_id == $seva->id ? 'selected' : '' }}>{{ $seva->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn text-white" style="background: var(--garuda-gold); padding: 10px 30px; font-weight: 600;">Save Pricing</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
