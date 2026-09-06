@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="module-art-banner banner-revenues">
        <div class="banner-content">
            <h2 class="banner-title"><i class="fas fa-chart-line"></i> Revenue Management</h2>
            <div>
                <a href="{{ route('revenues.export') }}" class="btn btn-success me-2"><i class="fas fa-file-csv"></i> Export CSV</a>
                <a href="{{ route('revenues.import') }}" class="btn btn-primary me-2" style="background:#0056b3; border:none;"><i class="fas fa-file-import"></i> Import Excel</a>
                <a href="{{ route('revenues.create') }}" class="btn text-white" style="background:var(--temple-gold);"><i class="fas fa-plus"></i> Record Revenue</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Dashboard Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100" style="border-radius:15px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color:white;">
                <div class="card-body p-4 text-center">
                    <h6 class="text-uppercase fw-bold opacity-75 mb-2"><i class="fas fa-wallet me-2"></i>Total Revenue</h6>
                    <h2 class="fw-bolder mb-0">₹{{ number_format($totalRevenue, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100" style="border-radius:15px; background: linear-gradient(135deg, var(--temple-gold) 0%, #cda153 100%); color:white;">
                <div class="card-body p-4 text-center">
                    <h6 class="text-uppercase fw-bold opacity-75 mb-2"><i class="fas fa-calendar-check me-2"></i>This Month</h6>
                    <h2 class="fw-bolder mb-0">₹{{ number_format($monthlyRevenue, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:15px; background: linear-gradient(135deg, var(--temple-maroon) 0%, #630000 100%); color:white;">
                <div class="card-body p-4 text-center">
                    <h6 class="text-uppercase fw-bold opacity-75 mb-2"><i class="fas fa-users-cog me-2"></i>Total Agents</h6>
                    <h2 class="fw-bolder mb-0">{{ number_format($totalAgents) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm h-100" style="border-radius:15px;">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-chart-area text-primary me-2"></i>Revenue Trend</h5>
                </div>
                <div class="card-body p-4">
                    <canvas id="monthlyTrendChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:15px;">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-medal text-warning me-2"></i>Top Agents</h5>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                    <canvas id="topAgentsChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="revenuesTable">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th width="30%">Agent / Person</th>
                            <th width="20%">Monthly Total</th>
                            <th width="20%">Overall Total</th>
                            <th width="15%">Latest Record Date</th>
                            <th width="15%" class="text-end">Actions</th>
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
$(document).ready(function() {
    $('#revenuesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('revenues.index') }}",
        columns: [
            {data: 'agent_display', name: 'agent_name'},
            {data: 'monthly_amount_formatted', name: 'monthly_amount', searchable: false},
            {data: 'amount_formatted', name: 'total_amount', searchable: false},
            {data: 'latest_date', name: 'latest_date', searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'},
        ]
    });

    // Pure AJAX Delete
    $(document).on('click', '.delete-revenue-btn', function() {
        var id = $(this).data('id');
        if (confirm('Are you sure you want to delete this revenue record?')) {
            $.ajax({
                url: '/revenues/' + id,
                type: 'POST',
                data: {
                    "_method": "DELETE",
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {
                    if(response.success) {
                        $('#revenuesTable').DataTable().ajax.reload(null, false); // Keep current pagination
                        
                        // Optional: show a quick temporary success message
                        if ($('.alert-success').length === 0) {
                            $('.card').before('<div class="alert alert-success alert-dismissible fade show" role="alert">Revenue removed successfully.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                        }
                    }
                },
                error: function(xhr) {
                    alert('An error occurred while deleting the record.');
                }
            });
        }
    });

    // Pure AJAX Delete All for Agent
    $(document).on('click', '.delete-agent-btn', function() {
        var agent = $(this).data('agent');
        if (confirm('WARNING: Are you sure you want to delete ALL revenue records for ' + agent + '? This action cannot be undone.')) {
            $.ajax({
                url: '/revenues/agent/' + encodeURIComponent(agent),
                type: 'POST',
                data: {
                    "_method": "DELETE",
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {
                    if(response.success) {
                        $('#revenuesTable').DataTable().ajax.reload(null, false);
                        if ($('.alert-success').length === 0) {
                            $('.card').before('<div class="alert alert-success alert-dismissible fade show" role="alert">All records for the agent were removed successfully.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                        }
                    }
                },
                error: function(xhr) {
                    alert('An error occurred while deleting the records.');
                }
            });
        }
    });

    // Chart.js Initialization
    const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    const agentsCtx = document.getElementById('topAgentsChart').getContext('2d');

    const trendLabels = @json($trendLabels);
    const trendData = @json($trendData);

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Revenue (₹)',
                data: trendData,
                borderColor: '#1e3c72',
                backgroundColor: 'rgba(30, 60, 114, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#cda153',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) { label += ': '; }
                            if (context.parsed.y !== null) {
                                label += '₹' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    const agentLabels = @json($agentLabels);
    const agentData = @json($agentData);
    
    // Generate beautiful colors based on temple theme
    const bgColors = [
        '#1e3c72', '#cda153', '#800000', '#2a5298', '#e3b968'
    ];

    new Chart(agentsCtx, {
        type: 'doughnut',
        data: {
            labels: agentLabels,
            datasets: [{
                data: agentData,
                backgroundColor: bgColors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: { family: 'Inter', size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) { label += ': '; }
                            if (context.parsed !== null) {
                                label += '₹' + context.parsed.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
