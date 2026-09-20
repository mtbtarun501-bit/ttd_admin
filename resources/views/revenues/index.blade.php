@extends('layouts.admin')

@php
    $fmt = function ($v) { return '₹' . number_format($v ?? 0, 0); };
    $curMonthNum = (int) date('n');
    $isCurrentYear = $year === (int) date('Y');
    $earningAgents = collect($matrix)->filter(fn ($e) => $e['total']->earned > 0)->count();
    $avgPerAgent = $earningAgents > 0 ? $yearTotals->earned / $earningAgents : 0;
    $topAgent = $top->first();
@endphp

@section('content')
<style>
    .rev-hero {
        background: linear-gradient(135deg, #6b0f12 0%, #4a0a0c 55%, #2f0606 100%);
        border-radius: 18px;
        padding: 34px 38px;
        margin-bottom: 28px;
        color: #fff;
        box-shadow: 0 14px 34px rgba(60, 10, 10, 0.22);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    .rev-hero h1 { font-size: 1.7rem; font-weight: 800; margin: 0; letter-spacing: -0.01em; }
    .rev-hero .sub { color: #f3d9a4; font-size: 0.92rem; margin-top: 4px; }
    .rev-hero .controls { display: flex; gap: 10px; flex-wrap: wrap; }
    .rev-hero .form-control-dark { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25); color: #fff; border-radius: 9px; font-weight: 600; }
    .rev-hero .form-control-dark option { color: #1f2937; }
    .rev-hero .btn-gold { background: var(--temple-gold); color: #4a0a0c; font-weight: 700; border-radius: 9px; }
    .rev-hero .btn-ghost { background: transparent; border: 1px solid rgba(255,255,255,0.35); color: #fff; border-radius: 9px; }
    .rev-hero .btn-ghost:hover { background: rgba(255,255,255,0.1); }

    .kpi-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 26px; }
    .kpi-card { background: #fff; border: 1px solid #f0ece4; border-radius: 14px; padding: 16px 18px; position: relative; overflow: hidden; box-shadow: 0 3px 12px rgba(0,0,0,0.04); }
    .kpi-card .kpi-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: #8a8f98; font-weight: 700; }
    .kpi-card .kpi-value { font-size: 1.5rem; font-weight: 800; margin-top: 6px; font-variant-numeric: tabular-nums; line-height: 1.1; }
    .kpi-card .kpi-delta { font-size: 0.78rem; font-weight: 700; margin-top: 6px; display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 20px; }
    .kpi-card .kpi-icon { position: absolute; right: 14px; top: 14px; font-size: 1.1rem; opacity: 0.35; }
    .kpi-up { color: #15803d; background: #ecfdf3; }
    .kpi-down { color: #b91c1c; background: #fef2f2; }
    .kpi-flat { color: #6b7280; background: #f3f4f6; }
    .kpi-card[data-accent="gold"] { border-left: 5px solid var(--temple-gold); }
    .kpi-card[data-accent="green"] { border-left: 5px solid #198754; }
    .kpi-card[data-accent="red"] { border-left: 5px solid #dc3545; }
    .kpi-card[data-accent="navy"] { border-left: 5px solid #1e3c72; }
    .kpi-card[data-accent="maroon"] { border-left: 5px solid var(--temple-maroon); }
    .kpi-card .kpi-value.text-green { color: #15803d; }

    .panel { background: #fff; border: 1px solid #f0ece4; border-radius: 14px; box-shadow: 0 3px 12px rgba(0,0,0,0.04); }
    .panel .panel-head { padding: 16px 20px; border-bottom: 1px solid #f3efe7; }
    .panel .panel-head h5 { margin: 0; font-weight: 800; font-size: 1rem; color: #3f0d0d; }
    .panel .panel-body { padding: 20px; }

    .matrix-table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
    .matrix-table th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: #8a8f98; font-weight: 700; padding: 10px 8px; text-align: center; border-bottom: 2px solid #eee7db; white-space: nowrap; }
    .matrix-table td { padding: 9px 8px; text-align: center; border-bottom: 1px solid #f6f2ea; font-size: 0.88rem; white-space: nowrap; }
    .matrix-table tr:hover td { background: #fdf9f0; }
    .matrix-table .col-agent { text-align: left; position: sticky; left: 0; background: #fff; z-index: 2; min-width: 170px; }
    .matrix-table tr:hover .col-agent { background: #fdf9f0; }
    .matrix-table .col-current { background: #fbf3e1; }
    .matrix-table .col-current th { background: #f7e9c8; }
    .matrix-table .cell-earned a { color: #15803d; font-weight: 700; text-decoration: none; }
    .matrix-table .cell-earned a:hover { text-decoration: underline; }
    .matrix-table .cell-zero { color: #c3c8cf; }
    .matrix-table tfoot td { background: #faf2e4; font-weight: 800; color: #3f0d0d; border-top: 2px solid #e8d9b8; }
    .matrix-table td.row-total { background: #fdf6ea; font-weight: 800; color: #3f0d0d; }

    .mo-panel table { font-variant-numeric: tabular-nums; }
    .mo-panel td.mo-cum { color: #1e3c72; font-weight: 700; }
    .mo-panel td.mo-earned { color: #15803d; font-weight: 700; }
    .mo-highlight { background: #f4fdf8; }
    .rank-bar { height: 6px; background: #f1ebe0; border-radius: 4px; overflow: hidden; margin-top: 5px; }
    .rank-bar > div { height: 100%; border-radius: 4px; }
    .shift-chip { display:inline-flex; align-items:center; gap:5px; font-weight:800; font-size:.8rem; }
</style>

<div class="container-fluid" style="padding: 34px 40px;">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- HERO -->
    <div class="rev-hero">
        <div>
            <h1><i class="fas fa-chart-line me-2"></i> Revenue</h1>
            <div class="sub">Commission earned from <strong>confirmed bookings</strong> — tracked live from the agent relation.</div>
        </div>
        <div class="controls">
            <form method="GET" action="{{ route('revenues.index') }}" class="d-flex gap-2 flex-wrap">
                <select name="year" class="form-select form-select-sm form-control-dark" style="width:auto;" onchange="this.form.submit()">
                    @foreach(range((int) date('Y'), 2020) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <select name="partner" class="form-select form-select-sm form-control-dark" style="width:auto;" onchange="this.form.submit()">
                    <option value="">All Partners</option>
                    <option value="in_partner" {{ $partner === 'in_partner' ? 'selected' : '' }}>In Partner</option>
                    <option value="out_partner" {{ $partner === 'out_partner' ? 'selected' : '' }}>Out Partner</option>
                </select>
                <button type="submit" class="btn btn-sm btn-gold"><i class="fas fa-filter me-1"></i> Apply</button>
            </form>
            <a href="{{ route('revenues.export', ['year' => $year, 'partner' => $partner]) }}" class="btn btn-sm btn-ghost"><i class="fas fa-file-csv me-1"></i> Export CSV</a>
            <a href="{{ route('agent-accounts.index') }}" class="btn btn-sm btn-ghost"><i class="fas fa-file-invoice-dollar me-1"></i> Agent Ledger</a>
        </div>
    </div>

    <!-- KPI STRIP -->
    <div class="kpi-grid">
        <div class="kpi-card" data-accent="navy">
            <span class="kpi-icon"><i class="fas fa-wallet"></i></span>
            <div class="kpi-label">Total Revenue (All Time)</div>
            <div class="kpi-value text-primary">{{ $fmt($allTimeEarned) }}</div>
        </div>
        <div class="kpi-card" data-accent="gold">
            <span class="kpi-icon"><i class="fas fa-calendar-alt"></i></span>
            <div class="kpi-label">{{ $year }} Revenue</div>
            <div class="kpi-value">{{ $fmt($yearTotals->earned) }}</div>
            @if($yearDelta !== null)
                <span class="kpi-delta {{ $yearDelta < 0 ? 'kpi-down' : 'kpi-up' }}">
                    <i class="fas fa-{{ $yearDelta < 0 ? 'arrow-down' : 'arrow-up' }}"></i> {{ abs($yearDelta) }}% vs {{ $year-1 }}
                </span>
            @endif
        </div>
        <div class="kpi-card" data-accent="green">
            <span class="kpi-icon"><i class="fas fa-calendar-check"></i></span>
            <div class="kpi-label">This Month ({{ date('M') }})</div>
            <div class="kpi-value text-green">{{ $fmt($thisMonthEarned) }}</div>
            @if($monthDelta !== null)
                <span class="kpi-delta {{ $monthDelta < 0 ? 'kpi-down' : 'kpi-up' }}">
                    <i class="fas fa-{{ $monthDelta < 0 ? 'arrow-down' : 'arrow-up' }}"></i> {{ abs($monthDelta) }}% vs {{ now()->subMonth()->format('M') }}
                </span>
            @endif
        </div>
        <div class="kpi-card" data-accent="maroon">
            <span class="kpi-icon"><i class="fas fa-user-tie"></i></span>
            <div class="kpi-label">Avg / Earning Agent ({{ $year }})</div>
            <div class="kpi-value">{{ $fmt($avgPerAgent) }}</div>
            <div class="text-muted" style="font-size:.78rem;">{{ $earningAgents }} of {{ $agentCount }} agents active</div>
        </div>
        <div class="kpi-card" data-accent="red">
            <span class="kpi-icon"><i class="fas fa-medal"></i></span>
            <div class="kpi-label">Top Agent ({{ $year }})</div>
            <div class="kpi-value" style="font-size:1.1rem;">{{ $topAgent['name'] ?? '—' }}</div>
            @if($topAgent)
                <div class="text-success fw-bold" style="font-size:.9rem;">{{ $fmt($topAgent['earned']) }}</div>
            @endif
        </div>
    </div>

    <!-- OVERALL EARNED BY MONTH -->
    <div class="panel mo-panel mb-4">
        <div class="panel-head d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-chart-simple me-2"></i> Overall Earned — Month by Month ({{ $year }})</h5>
            <span class="badge bg-success px-3 py-2">Total: {{ $fmt($yearTotals->earned) }}</span>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            <th class="text-end">Earned (Rs)</th>
                            <th class="text-center">Bookings</th>
                            <th class="text-center">Tickets</th>
                            <th class="text-end">Δ vs Prev Month</th>
                            <th class="text-end">Cumulative</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overallMonths as $m => $entry)
                            @php $prev = $m > 1 ? $overallMonths[$m-1]['data']->earned : 0; $diff = $entry['data']->earned - $prev; @endphp
                            <tr class="{{ $isCurrentYear && $m === $curMonthNum ? 'mo-highlight' : '' }}">
                                <td class="fw-bold">{{ $monthLabels[$m] }} @if($isCurrentYear && $m === $curMonthNum)<span class="badge bg-warning text-dark ms-1">now</span>@endif</td>
                                <td class="text-end mo-earned">{{ $fmt($entry['data']->earned) }}</td>
                                <td class="text-center">{{ $entry['data']->bookings_count }}</td>
                                <td class="text-center">{{ $entry['data']->tickets }}</td>
                                <td class="text-end">
                                    @if($entry['data']->earned > 0)
                                        <span class="shift-chip {{ $diff < 0 ? 'text-danger' : 'text-success' }}">
                                            <i class="fas fa-{{ $diff < 0 ? 'arrow-down' : 'arrow-up' }}"></i> {{ number_format(abs($diff), 0) }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end mo-cum">{{ $fmt($entry['cumulative']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td>TOTAL</td>
                            <td class="text-end text-success">{{ $fmt($yearTotals->earned) }}</td>
                            <td class="text-center">{{ $yearTotals->bookings_count }}</td>
                            <td class="text-center">{{ $yearTotals->tickets }}</td>
                            <td></td>
                            <td class="text-end" style="color:#1e3c72;">{{ $fmt($yearTotals->earned) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="row mb-4">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="panel h-100">
                <div class="panel-head"><h5><i class="fas fa-chart-area text-success me-2"></i> Trend — {{ $year }}</h5></div>
                <div class="panel-body"><canvas id="revTrendChart" style="max-height:230px;"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3 mb-4 mb-lg-0">
            <div class="panel h-100">
                <div class="panel-head"><h5><i class="fas fa-chart-pie text-primary me-2"></i> Partner Split</h5></div>
                <div class="panel-body d-flex align-items-center justify-content-center"><canvas id="revSplitChart" style="max-height:210px;"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel h-100">
                <div class="panel-head"><h5><i class="fas fa-medal text-warning me-2"></i> Top Agents — {{ $year }}</h5></div>
                <div class="panel-body"><canvas id="revTopChart" style="max-height:230px;"></canvas></div>
            </div>
        </div>
    </div>

    <!-- MONTHLY MATRIX (STAR) -->
    <div class="panel mb-4">
        <div class="panel-head d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-th-large me-2"></i> Monthly Earned — Agent × Month ({{ $year }})</h5>
            <small class="text-muted">Click a month to open that agent's ledger.</small>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th class="col-agent">Agent</th>
                            @foreach($monthLabels as $i => $label)
                                <th class="{{ $isCurrentYear && $i === $curMonthNum ? 'col-current' : '' }}">{{ $label }}</th>
                            @endforeach
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($matrix as $entry)
                            <tr>
                                <td class="col-agent">
                                    {{ $entry['agent']->name }}
                                    <span class="badge {{ $entry['agent']->agent_type === \App\Models\Agent::TYPE_IN_PARTNER ? 'bg-primary' : 'bg-secondary' }} ms-1">{{ $entry['agent']->agent_type_label }}</span>
                                </td>
                                @foreach($entry['months'] as $i => $cell)
                                    <td class="cell-{{ $cell->earned > 0 ? 'earned' : 'zero' }} {{ $isCurrentYear && $i === $curMonthNum ? 'col-current' : '' }}"
                                        @if($cell->earned > 0) title="{{ $monthLabels[$i] }} {{ $year }} · {{ $cell->bookings_count }} bookings · {{ $cell->tickets }} tickets · Spent {{ $fmt($cell->spent) }}" @endif>
                                        @if($cell->earned > 0)
                                            <a href="{{ route('agent-accounts.show', $entry['agent']->id) . '?year=' . $year . '&month=' . sprintf('%04d-%02d', $year, $i) }}">{{ $fmt($cell->earned) }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endforeach
                                <td class="row-total text-success">{{ $fmt($entry['total']->earned) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="col-agent" style="background:#faf2e4;">TOTAL</th>
                            @foreach($overallMonths as $m => $entry)
                                <th>{{ $fmt($entry['data']->earned) }}</th>
                            @endforeach
                            <th class="text-success">{{ $fmt($yearTotals->earned) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if($yearTotals->earned == 0)
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-2x mb-3 opacity-50"></i>
                    <h5 class="fw-bold">No revenue yet for {{ $year }}</h5>
                    <p>Commission appears here as soon as a booking is <strong>confirmed</strong> against an agent.</p>
                    <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-primary">Go to Bookings</a>
                </div>
            @endif
        </div>
    </div>

    <!-- RANKING -->
    <div class="panel">
        <div class="panel-head d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-ranking-star me-2"></i> Agent Ranking — {{ $year }}</h5>
            <span class="text-muted small">{{ $yearTotals->bookings_count }} bookings · {{ $yearTotals->tickets }} tickets</span>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="revenueTable">
                    <thead style="color:#3f0d0d;">
                        <tr>
                            <th>#</th>
                            <th>Agent</th>
                            <th>Bookings</th>
                            <th>Tickets</th>
                            <th>Earned (Rs)</th>
                            <th>Spent (Rs)</th>
                            <th>Total (Rs)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
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
    $('#revenueTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('revenues.index') }}",
            data: function(d) {
                d.year = @json($year);
                d.partner = @json($partner);
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'agents.name', className: 'fw-bold'},
            {data: 'bookings_count', name: 'bookings_count', searchable: false, className: 'text-center'},
            {data: 'tickets', name: 'tickets', searchable: false, className: 'text-center'},
            {data: 'earned', name: 'earned', searchable: false},
            {data: 'spent', name: 'spent', searchable: false},
            {data: 'total', name: 'total', searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'},
        ],
        order: [[4, 'desc']],
        pageLength: 25
    });

    const y = @json($year);
    const trendLabels = @json($trend['labels']);
    const trendEarned = @json($trend['earned']);
    const trendSpent = @json($trend['spent']);

    new Chart(document.getElementById('revTrendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [
                { label: 'Earned (₹)', data: trendEarned, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.12)', borderWidth: 2.5, fill: true, tension: 0.4, pointBackgroundColor: '#198754' },
                { label: 'Spent (₹)', data: trendSpent, borderColor: '#c0a36b', backgroundColor: 'rgba(192,163,107,0.10)', borderWidth: 2, fill: true, tension: 0.4, pointBackgroundColor: '#c0a36b' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ₹' + ctx.parsed.y.toLocaleString('en-IN') } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '₹' + v.toLocaleString('en-IN') }, grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false } }
            }
        }
    });

    const splitIn = @json($split['in']);
    const splitOut = @json($split['out']);
    new Chart(document.getElementById('revSplitChart'), {
        type: 'doughnut',
        data: {
            labels: ['In Partner', 'Out Partner'],
            datasets: [{ data: [splitIn, splitOut], backgroundColor: ['#1e3c72', '#cda153'], borderWidth: 0, hoverOffset: 5 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '68%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12 } },
                tooltip: { callbacks: { label: ctx => ctx.label + ': ₹' + ctx.parsed.toLocaleString('en-IN') } }
            }
        }
    });

    const topLabels = @json($top->pluck('name')->values());
    const topData = @json($top->pluck('earned')->values());
    new Chart(document.getElementById('revTopChart'), {
        type: 'bar',
        data: {
            labels: topLabels,
            datasets: [{ data: topData, backgroundColor: '#cda153', borderRadius: 6, barThickness: 18 }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => '₹' + ctx.parsed.x.toLocaleString('en-IN') } }
            },
            scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => '₹' + v.toLocaleString('en-IN') } },
                y: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush