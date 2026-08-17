@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-edit text-primary"></i> Edit Indian Stock</h2>
            <a href="{{ route('indian-stocks.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Portfolio</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('indian-stocks.update', $indianStock->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stock Symbol (NSE/BSE) <span class="text-danger">*</span></label>
                        <input type="text" name="stock_symbol" class="form-control @error('stock_symbol') is-invalid @enderror" value="{{ old('stock_symbol', str_replace('.NS', '', $indianStock->stock_symbol)) }}" required>
                        @error('stock_symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company/Stock Name <span class="text-danger">*</span></label>
                        <input type="text" name="stock_name" class="form-control @error('stock_name') is-invalid @enderror" value="{{ old('stock_name', $indianStock->stock_name) }}" required>
                        @error('stock_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $indianStock->quantity) }}" min="1" required>
                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Buy Price (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="buy_price" class="form-control @error('buy_price') is-invalid @enderror" value="{{ old('buy_price', $indianStock->buy_price) }}" min="0" required>
                        @error('buy_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Buy Date <span class="text-danger">*</span></label>
                        <input type="date" name="buy_date" class="form-control @error('buy_date') is-invalid @enderror" value="{{ old('buy_date', $indianStock->buy_date->format('Y-m-d')) }}" required>
                        @error('buy_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Investment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
