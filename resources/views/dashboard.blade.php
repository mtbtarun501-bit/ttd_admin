@extends('layouts.admin')

@section('content')
    <!-- Main Dashboard Content -->
    <div class="container-fluid" style="padding: 10px 40px 40px;">
        
        <!-- Quick Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('bookings.index') }}" class="stat-card-link">
                    <div class="stat-card">
                        <div class="info">
                            <h5>Today's Bookings</h5>
                            <h3>{{ number_format($todaysBookings) }}</h3>
                        </div>
                        <div class="icon-box">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('bookings.index') }}" class="stat-card-link">
                    <div class="stat-card">
                        <div class="info">
                            <h5>Pending Approvals</h5>
                            <h3>{{ number_format($pendingApprovals) }}</h3>
                        </div>
                        <div class="icon-box" style="background: rgba(212, 175, 55, 0.2); color: #D4AF37;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('revenues.index') }}" class="stat-card-link">
                    <div class="stat-card">
                        <div class="info">
                            <h5>Total Revenue</h5>
                            <h3>{{ $formattedRevenue }}</h3>
                        </div>
                        <div class="icon-box" style="background: rgba(46, 204, 113, 0.2); color: #2ecc71;">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('devotees.index') }}" class="stat-card-link">
                    <div class="stat-card">
                        <div class="info">
                            <h5>Active Devotees</h5>
                            <h3>{{ number_format($activeDevotees) }}</h3>
                        </div>
                        <div class="icon-box" style="background: rgba(52, 152, 219, 0.2); color: #3498db;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-card">
                    <h5><i class="fas fa-chart-area me-2"></i> Monthly Booking Trends</h5>
                    <canvas id="bookingsChart" height="100"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card">
                    <h5><i class="fas fa-chart-pie me-2"></i> Booking Types</h5>
                    <canvas id="typesChart" height="215"></canvas>
                </div>
            </div>
        </div>


    </div>
@endsection

@push('scripts')
<script>
    // Bookings Area Chart
    const ctx1 = document.getElementById('bookingsChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: {!! json_encode($months) !!},
            datasets: [{
                label: 'Total Bookings',
                data: {!! json_encode($monthlyBookingsData) !!},
                borderColor: '#800000',
                backgroundColor: 'rgba(128, 0, 0, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Booking Types Pie Chart
    const ctx2 = document.getElementById('typesChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($typeLabels) !!},
            datasets: [{
                data: {!! json_encode($typeData) !!},
                backgroundColor: {!! json_encode(array_slice($typeColors, 0, count($typeLabels))) !!},
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>
@endpush
