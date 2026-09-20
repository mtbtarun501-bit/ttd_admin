@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="module-art-banner" style="background: linear-gradient(135deg, var(--temple-maroon) 0%, #4a0000 100%); padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); color: white; display: flex; justify-content: space-between; align-items: center;">
        <div class="banner-content">
            <h2 class="banner-title m-0" style="font-weight: 700;"><i class="fas fa-file-invoice-dollar me-2"></i> Agent Accounts Ledger</h2>
            <p class="m-0 mt-2 text-white-50">Monthly spend &amp; earnings for all partner agents — {{ $periodLabel }}</p>
        </div>
        <div>
            <a href="{{ route('agent-accounts.export', ['month' => $month]) }}" class="btn btn-success me-2"><i class="fas fa-file-csv"></i> Export CSV</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Period Picker -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius:15px;">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <form method="GET" action="{{ route('agent-accounts.index') }}" class="d-flex align-items-center gap-2">
                <label class="fw-bold text-muted mb-0">Period:</label>
                <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" style="width:auto;">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
            </form>
            <a href="{{ route('agent-accounts.index') }}" class="btn btn-sm btn-outline-secondary {{ $month ? '' : 'active' }}"><i class="fas fa-infinity"></i> All Time</a>
            @if($prevMonth)
                <a href="{{ route('agent-accounts.index', ['month' => $prevMonth]) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-left"></i> Prev</a>
            @endif
            @if($nextMonth)
                <a href="{{ route('agent-accounts.index', ['month' => $nextMonth]) }}" class="btn btn-sm btn-outline-secondary">Next <i class="fas fa-chevron-right"></i></a>
            @endif
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-2 col-6 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100 text-center" style="border-radius:15px; border-left: 5px solid var(--temple-gold) !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Active Agents</h6>
                    <h3 class="fw-bold mb-0">{{ $activeAgents }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100 text-center" style="border-radius:15px; border-left: 5px solid #0dcaf0 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Bookings</h6>
                    <h3 class="fw-bold mb-0">{{ $totals->bookings_count }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100 text-center" style="border-radius:15px; border-left: 5px solid #6f42c1 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Tickets</h6>
                    <h3 class="fw-bold mb-0">{{ $totals->tickets }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100 text-center" style="border-radius:15px; border-left: 5px solid #dc3545 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Total Spent</h6>
                    <h3 class="fw-bold mb-0 text-danger">₹{{ number_format($totals->spent, 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100 text-center" style="border-radius:15px; border-left: 5px solid #198754 !important;">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-1">Total Earned</h6>
                    <h3 class="fw-bold mb-0 text-success">₹{{ number_format($totals->earned, 0) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Trend Chart -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius:15px;">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-chart-area text-primary me-2"></i>Spend vs Earned — Last 6 Months</h5>
        </div>
        <div class="card-body p-4">
            <canvas id="ledgerTrendChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="agentAccountsTable">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th>No</th>
                            <th>Agent</th>
                            <th>Bookings</th>
                            <th>Tickets</th>
                            <th>Spent (Rs)</th>
                            <th>Earned (Rs)</th>
                            <th>Total Amount (Rs)</th>
                            <th width="150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
var CURRENT_MONTH = @json($month);
$(document).ready(function() {
    $('#agentAccountsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('agent-accounts.index') }}",
            data: function(d) {
                d.month = CURRENT_MONTH;
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'agents.name', className: 'fw-bold'},
            {data: 'bookings_count', name: 'bookings_count', searchable: false, className: 'text-center'},
            {data: 'tickets', name: 'tickets', searchable: false, className: 'text-center'},
            {data: 'spent', name: 'spent', className: 'text-danger fw-bold', searchable: false},
            {data: 'earned', name: 'earned', className: 'text-success fw-bold', searchable: false},
            {data: 'total', name: 'total', className: 'text-dark fw-bold', searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[4, 'desc']],
        pageLength: 25
    });

    const trendLabels = @json($trend['labels']);
    const trendSpent = @json($trend['spent']);
    const trendEarned = @json($trend['earned']);

    const trendCtx = document.getElementById('ledgerTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [
                {
                    label: 'Spent (₹)',
                    data: trendSpent,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Earned (₹)',
                    data: trendEarned,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) { label += ': '; }
                            label += '₹' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: function(value) { return '₹' + value.toLocaleString(); } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                }
            }
        }
    });
});
</script>
@endpush