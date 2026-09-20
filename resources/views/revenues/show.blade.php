@extends('layouts.admin')

@php
    $roleColor = $agent->agent_type === \App\Models\Agent::TYPE_IN_PARTNER ? '#1e3c72' : '#6b7280';
    $initials = collect(explode(' ', trim($agent->name)))->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    $fmt = fn($v) => '₹' . number_format($v ?? 0, 0);
    $monthLabels = collect(range(1, 12))->map(fn($i) => \Carbon\Carbon::create(null, $i, 1)->format('M'))->values();
    $monthEarned = collect($months)->pluck('earned')->values();
@endphp

@section('content')
<style>
    .agent-hero {
        background: linear-gradient(135deg, #6b0f12 0%, #4a0a0c 55%, #2f0606 100%);
        border-radius: 18px;
        padding: 28px 34px;
        margin-bottom: 24px;
        color: #fff;
        box-shadow: 0 14px 34px rgba(60, 10, 10, 0.22);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    .agent-avatar {
        width: 58px; height: 58px; border-radius: 14px;
        background: var(--temple-gold); color: #4a0a0c;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.3rem;
    }
    .agent-hero .btn-ghost { background: transparent; border: 1px solid rgba(255,255,255,0.35); color: #fff; border-radius: 9px; }
    .agent-hero .btn-ghost:hover { background: rgba(255,255,255,0.1); }
    .agent-hero select { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25); color: #fff; border-radius: 9px; font-weight: 600; }
    .agent-hero select option { color: #1f2937; }
    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #f0ece4; border-radius: 14px; padding: 16px 18px; box-shadow: 0 3px 12px rgba(0,0,0,0.04); }
    .kpi-card .kpi-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: #8a8f98; font-weight: 700; }
    .kpi-card .kpi-value { font-size: 1.4rem; font-weight: 800; margin-top: 6px; font-variant-numeric: tabular-nums; }
    .panel { background: #fff; border: 1px solid #f0ece4; border-radius: 14px; box-shadow: 0 3px 12px rgba(0,0,0,0.04); }
    .panel .panel-head { padding: 16px 20px; border-bottom: 1px solid #f3efe7; }
    .panel .panel-head h5 { margin: 0; font-weight: 800; font-size: 1rem; color: #3f0d0d; }
    .panel .panel-body { padding: 20px; }
</style>

<div class="container-fluid" style="padding: 34px 40px;">
    <!-- HERO -->
    <div class="agent-hero">
        <div class="d-flex align-items-center gap-3">
            <div class="agent-avatar">{{ $initials }}</div>
            <div>
                <h4 class="mb-1 fw-bold" style="letter-spacing:-0.01em;">
                    {{ $agent->name }}
                    <span class="badge ms-2" style="background:{{ $roleColor }};">{{ $agent->agent_type_label }}</span>
                </h4>
                <div class="text-white-50 small"><i class="fas fa-phone me-1"></i>{{ $agent->phone ?: '—' }}</div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" action="{{ route('revenues.show', $agent->id) }}" class="d-flex gap-2">
                <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                    @foreach(range((int) date('Y'), 2020) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('revenues.index') }}" class="btn btn-sm btn-ghost"><i class="fas fa-arrow-left me-1"></i> Revenue Dashboard</a>
            <a href="{{ route('agent-accounts.show', $agent->id) }}?year={{ $year }}" class="btn btn-sm btn-ghost"><i class="fas fa-file-invoice-dollar me-1"></i> Full Ledger</a>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="kpi-grid mb-4">
        <div class="kpi-card" style="border-left:5px solid #1e3c72;">
            <div class="kpi-label">Bookings ({{ $year }})</div>
            <div class="kpi-value text-primary">{{ $yearTotals->bookings_count }}</div>
        </div>
        <div class="kpi-card" style="border-left:5px solid #0dcaf0;">
            <div class="kpi-label">Tickets ({{ $year }})</div>
            <div class="kpi-value">{{ $yearTotals->tickets }}</div>
        </div>
        <div class="kpi-card" style="border-left:5px solid #dc3545;">
            <div class="kpi-label">Total Spent ({{ $year }})</div>
            <div class="kpi-value text-danger">{{ $fmt($yearTotals->spent) }}</div>
        </div>
        <div class="kpi-card" style="border-left:5px solid #198754;">
            <div class="kpi-label">Total Earned ({{ $year }})</div>
            <div class="kpi-value text-success">{{ $fmt($yearTotals->earned) }}</div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 mb-4 mb-lg-0">
            <div class="panel">
                <div class="panel-head"><h5><i class="fas fa-chart-bar text-success me-2"></i> Monthly Earned — {{ $year }}</h5></div>
                <div class="panel-body"><canvas id="agentMonthChart" style="max-height:230px;"></canvas></div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-body">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background:rgba(128,0,0,0.05); color:#3f0d0d;">
                            <tr>
                                <th>Month</th>
                                <th class="text-center">Bookings</th>
                                <th class="text-center">Tickets</th>
                                <th class="text-end">Spent (Rs)</th>
                                <th class="text-end">Earned (Rs)</th>
                                <th class="text-end">Total (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($months as $num => $row)
                                <tr class="{{ (int) date('Y') === $year && (int) date('n') === $num ? 'table-warning' : '' }}">
                                    <td class="fw-bold">{{ \Carbon\Carbon::create(null, $num, 1)->format('M') }}</td>
                                    <td class="text-center">{{ $row->bookings_count }}</td>
                                    <td class="text-center">{{ $row->tickets }}</td>
                                    <td class="text-end text-danger fw-bold">{{ $fmt($row->spent) }}</td>
                                    <td class="text-end text-success fw-bold">{{ $fmt($row->earned) }}</td>
                                    <td class="text-end">{{ $fmt($row->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:rgba(128,0,0,0.08);">
                            <tr class="fw-bold">
                                <td>TOTAL</td>
                                <td class="text-center">{{ $yearTotals->bookings_count }}</td>
                                <td class="text-center">{{ $yearTotals->tickets }}</td>
                                <td class="text-end text-danger">{{ $fmt($yearTotals->spent) }}</td>
                                <td class="text-end text-success">{{ $fmt($yearTotals->earned) }}</td>
                                <td class="text-end">{{ $fmt($yearTotals->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TRANSACTIONS -->
    <div class="panel">
        <div class="panel-head">
            <h5><i class="fas fa-receipt me-2"></i> Transactions — {{ $year }}</h5>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="agentYearBookings">
                    <thead style="color:#3f0d0d;">
                        <tr>
                            <th>Date</th>
                            <th>Booking No</th>
                            <th>Ticket Type</th>
                            <th>Devotee</th>
                            <th class="text-center">Tickets</th>
                            <th class="text-end">Spent (Rs)</th>
                            <th class="text-end">Earned (Rs)</th>
                            <th class="text-end">Total (Rs)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $b)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($b->booking_date)->format('d M Y') }}</td>
                                <td class="fw-bold text-primary">{{ $b->booking_no }}</td>
                                <td>{{ $b->bookingType->name ?? 'N/A' }}</td>
                                <td>{{ $b->devotee->name ?? 'N/A' }}</td>
                                <td class="text-center">{{ $b->ticket_count }}</td>
                                <td class="text-end text-danger fw-bold">{{ $fmt($b->total_amount - $b->service_charge) }}</td>
                                <td class="text-end text-success fw-bold">{{ $fmt($b->service_charge) }}</td>
                                <td class="text-end fw-bold">{{ $fmt($b->total_amount) }}</td>
                                <td>
                                    @if($b->status === 'confirmed')
                                        <span class="badge bg-success">Confirmed</span>
                                    @else
                                        <span class="badge bg-primary">Completed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="4">TOTAL</td>
                            <td class="text-center">{{ $transactions->sum('ticket_count') }}</td>
                            <td class="text-end text-danger">{{ $fmt($transactions->sum(fn($b) => $b->total_amount - $b->service_charge)) }}</td>
                            <td class="text-end text-success">{{ $fmt($transactions->sum('service_charge')) }}</td>
                            <td class="text-end">{{ $fmt($transactions->sum('total_amount')) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if($transactions->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="fas fa-folder-open fs-1 mb-3 opacity-50"></i>
                    <h5 class="fw-bold">No transactions for {{ $year }}</h5>
                    <p>Commission appears here once bookings are confirmed against {{ $agent->name }}.</p>
                </div>
            @endif
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
    $('#agentYearBookings').DataTable({
        pageLength: 25,
        order: [[0, 'desc']]
    });

    const monthLabels = @json($monthLabels);
    const earned = @json($monthEarned);

    new Chart(document.getElementById('agentMonthChart'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                data: earned,
                backgroundColor: earned.map(v => v > 0 ? '#198754' : '#f1ebe0'),
                borderRadius: 5,
                barThickness: 14
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => 'Earned: ₹' + ctx.parsed.y.toLocaleString('en-IN') } }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => '₹' + v.toLocaleString('en-IN') } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush