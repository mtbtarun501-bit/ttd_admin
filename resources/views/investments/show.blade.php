@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 style="color:var(--temple-dark-brown); font-weight:700; margin-bottom: 5px;">
                <i class="fas fa-user-circle me-2"></i> {{ $investorName }}'s Personal Funds
            </h4>
            <p class="text-muted">Complete Transaction History</p>
        </div>
        <a href="{{ route('investments.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Index</a>
    </div>

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th>Date</th>
                            <th>Amount (₹)</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th width="100px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($investments as $investment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($investment->investment_date)->format('d M Y') }}</td>
                                <td class="fw-bold text-success">₹{{ number_format($investment->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $investment->status == 'active' ? 'success' : ($investment->status == 'completed' ? 'primary' : 'secondary') }}">
                                        {{ ucfirst($investment->status) }}
                                    </span>
                                </td>
                                <td>{{ $investment->remarks ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('investments.destroy', $investment->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" style="background:var(--temple-maroon); border:none;" onclick="return confirm('Are you sure you want to delete this specific record?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No transactions found for this investor.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot style="background: #f8f9fa;">
                        <tr>
                            <td class="fw-bold text-end">Total Funds:</td>
                            <td class="fw-bold text-success fs-5">₹{{ number_format($investments->sum('amount'), 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
