@extends('layouts.admin')

@php
    $fmtMoney = fn ($v) => $v === null ? '—' : '₹' . number_format($v, 0);
    $fmtPrice = function ($v) {
        if ($v === null) return '—';
        if (abs($v) >= 1000) return '₹' . number_format($v, 0);
        if (abs($v) >= 1) return '₹' . number_format($v, 2);
        return '₹' . rtrim(rtrim(number_format($v, 6, '.', ''), '0'), '.');
    };
    $fmtQty = function ($v) {
        if (abs($v) >= 1000) return number_format($v, 0);
        if (abs($v) >= 1) return number_format($v, 2);
        return rtrim(rtrim(number_format($v, 8, '.', ''), '0'), '.');
    };
    $fmtCompact = function ($v) {
        if ($v === null) return '—';
        $abs = abs($v);
        if ($abs >= 10000000) return number_format($v / 10000000, 2) . ' Cr';
        if ($abs >= 100000) return number_format($v / 100000, 2) . ' L';
        if ($abs >= 1000) return number_format($v / 1000, 1) . ' K';
        return number_format($v, 0);
    };

    $tileColors = [
        'BTC' => '#F7931A', 'ETH' => '#627EEA', 'BNB' => '#F3BA2F', 'SOL' => '#14F195',
        'XRP' => '#00AAE4', 'DOGE' => '#C2A633', 'ADA' => '#2A5ADA', 'TRX' => '#EB0029',
        'SHIB' => '#FFA409', 'LINK' => '#2A5ADA', 'DOT' => '#E6007A', 'LTC' => '#345D9D',
        'MATIC' => '#8247E5', 'POL' => '#8247E5', 'NEAR' => '#00EC97', 'AVAX' => '#E84142',
        'SUI' => '#4DA2FF', 'APT' => '#02C4A5', 'FIL' => '#0090FF', 'ATOM' => '#2E3148',
        'TON' => '#0098EA', 'USDT' => '#26A17B', 'USDC' => '#2775CA', 'PEPE' => '#3FA037',
    ];
    $tileColor = fn ($symbol, $type) => $type === 'crypto' ? ($tileColors[$symbol] ?? '#7C6CF0') : '#1E88A8';

    $topMover = $rows->filter(fn ($r) => $r->pnl_pct !== null)->sortByDesc('pnl_pct')->first();

    $lastUpdatedStr = $lastUpdated ? date('d M Y, H:i', $lastUpdated) . ' IST' : '—';
    $reopen = $errors->any() ? (old('id') ? ['edit', old()] : ['add', old()]) : null;
@endphp

@section('content')
<style>
    :root {
        --inv-bg: #f4f6f9;
        --inv-panel: #ffffff;
        --inv-panel-2: #eef2f7;
        --inv-line: #dde4ee;
        --inv-ink: #1f2c40;
        --inv-muted: #5d6b80;
        --inv-gold: #b7842f;
        --inv-up: #16a34a;
        --inv-down: #dc2626;
        --inv-blue: #2563eb;
    }

    .inv-shell {
        background:
            radial-gradient(1100px 400px at 85% -8%, rgba(37, 99, 235, 0.05), transparent 60%),
            radial-gradient(800px 360px at -10% 0%, rgba(183, 132, 47, 0.05), transparent 55%),
            var(--inv-bg);
        min-height: 100vh;
        padding: 34px 40px 64px;
        color: var(--inv-ink);
        font-variant-numeric: tabular-nums;
    }

    .inv-hero { display: flex; justify-content: space-between; align-items: flex-start; gap: 28px; flex-wrap: wrap; margin-bottom: 28px; }
    .inv-hero .eyebrow { display: flex; align-items: center; gap: 9px; color: var(--inv-muted); font-size: .95rem; }
    .inv-live { display: inline-flex; align-items: center; gap: 7px; font-weight: 700; font-size: .78rem; letter-spacing: .02em; color: var(--inv-up); }
    .inv-live .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--inv-up); box-shadow: 0 0 0 0 rgba(22, 163, 74, .5); animation: invPulse 2s infinite; }
    @keyframes invPulse { 0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, .5); } 70% { box-shadow: 0 0 0 9px rgba(22, 163, 74, 0); } 100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); } }
    .inv-hero h1 { font-size: 1.9rem; font-weight: 800; letter-spacing: -.02em; margin: 10px 0 4px; }
    .inv-hero .sub { color: var(--inv-muted); font-size: .96rem; }

    .inv-hero .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 6px; }
    .btn-inv { border: 1px solid var(--inv-line); background: #ffffff; color: var(--inv-ink); border-radius: 10px; padding: 9px 16px; font-weight: 600; font-size: .9rem; display: inline-flex; align-items: center; gap: 8px; transition: all .15s; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); }
    .btn-inv:hover { border-color: #c4cfdd; background: #f1f4f9; color: var(--inv-ink); }
    .btn-inv-gold { border-color: rgba(183, 132, 47, .5); background: linear-gradient(180deg, #fdf6ea, #f7ecd7); color: #9a6712; }
    .btn-inv-gold:hover { border-color: rgba(183, 132, 47, .7); background: linear-gradient(180deg, #f9eccd, #f0ddb5); color: #7d530d; }

    .inv-hero .net-readout { border-left: 2px solid var(--inv-line); padding-left: 26px; margin-top: 14px; }
    .inv-hero .net-readout .net-label { color: var(--inv-muted); font-size: .85rem; }
    .inv-hero .net-readout .net-value { font-size: 2.4rem; font-weight: 800; letter-spacing: -.02em; line-height: 1; margin: 6px 0 4px; }
    .net-up { color: var(--inv-up); }
    .net-down { color: var(--inv-down); }
    .inv-hero .net-readout .net-foot { color: var(--inv-muted); font-size: .88rem; }

    .inv-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 30px; }
    .inv-kpi { background: linear-gradient(180deg, #ffffff, #f8fafc); border: 1px solid var(--inv-line); border-radius: 14px; padding: 18px 20px; position: relative; overflow: hidden; box-shadow: 0 1px 3px rgba(15, 23, 42, .06); }
    .inv-kpi::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 3px; background: var(--kpi-accent, var(--inv-line)); }
    .inv-kpi .k-label { color: var(--inv-muted); font-size: .82rem; font-weight: 700; letter-spacing: .02em; text-transform: uppercase; }
    .inv-kpi .k-value { font-size: 1.42rem; font-weight: 800; margin-top: 7px; letter-spacing: -.01em; color: #0f172a; }
    .inv-kpi .k-foot { color: var(--inv-muted); font-size: .8rem; margin-top: 6px; }
    .inv-kpi .chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 20px; font-size: .8rem; font-weight: 700; }
    .chip-up { color: var(--inv-up) !important; background: rgba(22, 163, 74, .12); }
    .chip-down { color: var(--inv-down) !important; background: rgba(220, 38, 38, .12); }
    .chip-flat { color: var(--inv-muted) !important; background: rgba(93, 107, 128, .12); }

    .inv-panel { background: linear-gradient(180deg, #ffffff, #fafbfd); border: 1px solid var(--inv-line); border-radius: 14px; box-shadow: 0 1px 3px rgba(15, 23, 42, .05); }
    .inv-panel .p-head { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid var(--inv-line); min-height: 56px; }
    .inv-panel .p-head h5 { margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a; }
    .inv-panel .p-body { padding: 20px; }

    .inv-table-wrap { padding: 0; }
    .inv-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
    .inv-table thead th { font-size: .72rem; font-weight: 700; color: #41506a; text-align: right; padding: 12px 14px; border-bottom: 1px solid var(--inv-line); white-space: nowrap; background: #eef2f7; }
    .inv-table thead th.th-left, .inv-table tbody td.td-left, .inv-table tfoot td.tf-left { text-align: left; }
    .inv-table tbody td { padding: 13px 14px; border-bottom: 1px solid #e3e9f2; text-align: right; white-space: nowrap; }
    .inv-table tbody tr:nth-child(even) td { background: #f8fafc; }
    .inv-table tbody tr:hover td { background: #eef3f9; }
    .inv-table tbody tr.row-win td { border-bottom-color: rgba(22, 163, 74, .22); }
    .inv-table tbody tr.row-lose td { border-bottom-color: rgba(220, 38, 38, .22); }
    .inv-table tfoot td { padding: 14px; text-align: right; font-weight: 800; background: #eef2f7; color: #0f172a; border-top: 1px solid var(--inv-line); }

    .asset-cell { display: flex; align-items: center; gap: 12px; }
    .asset-tile { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .8rem; color: #fff; text-shadow: 0 1px 2px rgba(0, 0, 0, .35); flex: none; }
    .asset-cell .a-name { font-weight: 700; line-height: 1.1; }
    .asset-cell .a-sub { color: var(--inv-muted); font-size: .76rem; margin-top: 3px; }
    .type-pill { font-size: .66rem; font-weight: 700; padding: 2px 7px; border-radius: 20px; margin-left: 7px; vertical-align: 1px; }
    .tp-crypto { color: #7c3aed; background: rgba(124, 58, 237, .10); }
    .tp-stock { color: #0e7490; background: rgba(14, 116, 144, .12); }

    .pos { color: var(--inv-up); }
    .neg { color: var(--inv-down); }
    .stale { color: var(--inv-muted); font-weight: 500; }
    .tabnum { font-variant-numeric: tabular-nums; }

    .inv-empty { text-align: center; padding: 70px 20px; }
    .inv-empty .ic { width: 84px; height: 84px; border-radius: 50%; margin: 0 auto 22px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--inv-gold); background: radial-gradient(circle at 30% 30%, rgba(183, 132, 47, .22), rgba(183, 132, 47, .06)); border: 1px solid rgba(183, 132, 47, .4); }
    .inv-empty h4 { font-weight: 700; margin-bottom: 6px; color: #0f172a; }
    .inv-empty p { color: var(--inv-muted); max-width: 460px; margin: 0 auto 22px; font-size: .95rem; }

    .modal-content-inv { background: #ffffff; border: 1px solid var(--inv-line); border-radius: 16px; color: var(--inv-ink); box-shadow: 0 18px 50px rgba(15, 23, 42, .18); }
    .modal-content-inv .modal-header { border-bottom: 1px solid var(--inv-line); }
    .modal-content-inv .btn-close { opacity: .55; }
    .modal-content-inv .form-label { color: var(--inv-muted); font-size: .8rem; font-weight: 700; }
    .modal-content-inv .form-control, .modal-content-inv .form-select { background: #ffffff; border: 1px solid #ced6e2; color: var(--inv-ink); border-radius: 9px; }
    .modal-content-inv .form-control:focus, .modal-content-inv .form-select:focus { background: #ffffff; color: var(--inv-ink); border-color: rgba(183, 132, 47, .65); box-shadow: 0 0 0 3px rgba(183, 132, 47, .15); }
    .modal-content-inv .form-control::placeholder { color: #9aa7ba; }
    .modal-content-inv .form-text { color: #6b7a92; font-size: .78rem; }
    .modal-content-inv .form-check-input:checked { background-color: var(--inv-gold); border-color: var(--inv-gold); }
    .modal-content-inv .is-invalid { border-color: var(--inv-down) !important; }

    .inv-toast { background: #ffffff; color: var(--inv-ink); border: 1px solid var(--inv-line); border-radius: 12px !important; }

    .flash-inv { display: flex; align-items: center; gap: 12px; background: #e9f9f1; border: 1px solid #bfe9d5; color: #0e7a52; padding: 13px 18px; border-radius: 12px; margin-bottom: 22px; font-weight: 600; }
    .flash-inv.flash-err { background: #fdeef0; border-color: #f5c6cb; color: #b02a37; }

    @media (max-width: 1200px) { .inv-kpis { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .inv-shell { padding: 22px 16px 48px; } .inv-kpis { grid-template-columns: 1fr; } }
</style>

<div class="inv-shell">
    @if (session('success'))
        <div class="flash-inv"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="flash-inv flash-err"><i class="fas fa-exclamation-triangle"></i> Please fix the highlighted fields and try again.</div>
    @endif

    <!-- HERO -->
    <div class="inv-hero">
        <div>
            <div class="eyebrow">
                <span class="inv-live"><span class="dot"></span>LIVE</span>
                <span id="inv-ticker">Prices stream from CoinGecko &amp; Yahoo Finance</span>
            </div>
            <h1>Investment portfolio</h1>
            <div class="sub">Everything you hold — crypto and Indian stocks — priced live in INR, profit and loss tracked instantly.</div>
        </div>
        <div class="d-flex flex-column align-items-end gap-3">
            <div class="actions">
                <button type="button" class="btn btn-inv" id="invRefresh"><i class="fas fa-sync-alt"></i> Refresh prices</button>
                <button type="button" class="btn btn-inv" data-bs-toggle="modal" data-bs-target="#invAddModal"><i class="fas fa-coins"></i> Add crypto</button>
                <button type="button" class="btn btn-inv btn-inv-gold" data-bs-toggle="modal" data-bs-target="#invAddModal" data-preset="stock"><i class="fas fa-chart-line"></i> Add stock</button>
            </div>
            <div class="net-readout" id="netReadout">
                <div class="net-label">Net profit &amp; loss</div>
                <div class="net-value {{ $totals->pnl < 0 ? 'net-down' : 'net-up' }}" id="netValue">{{ $fmtCompact($totals->pnl) }}</div>
                <div class="net-foot">
                    <span id="netPct">{{ $totals->pnl_pct >= 0 ? '+' : '' }}{{ number_format($totals->pnl_pct, 2) }}%</span> on invested ·
                    today <span id="netDay">{{ $totals->day_change >= 0 ? '+' : '' }}{{ $fmtCompact($totals->day_change) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI STRIP -->
    <div class="inv-kpis">
        <div class="inv-kpi" style="--kpi-accent: var(--inv-gold);">
            <div class="k-label">Total invested</div>
            <div class="k-value" id="kpiInvested">{{ $fmtCompact($totals->invested) }}</div>
            <div class="k-foot">{{ $totals->count }} {{ $totals->count === 1 ? 'position' : 'positions' }} held</div>
        </div>
        <div class="inv-kpi" style="--kpi-accent: var(--inv-blue);">
            <div class="k-label">Current value</div>
            <div class="k-value" id="kpiCurrent">{{ $fmtCompact($totals->current) }}</div>
            <div class="k-foot">{{ $totals->live_count }} of {{ $totals->count }} priced live</div>
        </div>
        <div class="inv-kpi" style="--kpi-accent: var(--inv-up);">
            <div class="k-label">Best performer</div>
            <div class="k-value" style="font-size:1.15rem; padding-top:4px;">
                @if ($topMover)
                    {{ $topMover->symbol }}
                    <span class="chip {{ $topMover->pnl_pct < 0 ? 'chip-down' : 'chip-up' }}">{{ $topMover->pnl_pct >= 0 ? '+' : '' }}{{ number_format($topMover->pnl_pct, 1) }}%</span>
                @else
                    — <span class="chip chip-flat">no data</span>
                @endif
            </div>
            <div class="k-foot">{{ $topMover ? $topMover->holding->name : 'Add a position to begin.' }}</div>
        </div>
        <div class="inv-kpi mb-0" style="--kpi-accent: var(--inv-down);">
            <div class="k-label">Today&rsquo;s 24h move</div>
            <div class="k-value {{ $totals->day_change < 0 ? 'neg' : 'pos' }}" id="kpiDay">{{ $totals->day_change >= 0 ? '+' : '' }}{{ $fmtCompact($totals->day_change) }}</div>
            <div class="k-foot">estimated from 24h price change</div>
        </div>
    </div>

    <!-- HOLDINGS -->
    <div class="inv-panel">
        <div class="p-head">
            <h5><i class="fas fa-layer-group me-2" style="color:var(--inv-gold);"></i> Holdings</h5>
            <span class="text-muted small">auto-refreshes every 30s</span>
        </div>
        <div class="inv-table-wrap">
            @if($rows->isEmpty())
                <div class="inv-empty">
                    <div class="ic"><i class="fas fa-coins"></i></div>
                    <h4>Your portfolio is empty</h4>
                    <p>Add the crypto or Indian stocks you hold — along with the price you paid — and live pricing + profit &amp; loss start appearing instantly.</p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-inv" data-bs-toggle="modal" data-bs-target="#invAddModal"><i class="fas fa-coins me-1"></i> Add my first crypto</button>
                        <button type="button" class="btn btn-inv btn-inv-gold" data-bs-toggle="modal" data-bs-target="#invAddModal" data-preset="stock"><i class="fas fa-chart-line me-1"></i> Add my first stock</button>
                    </div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="inv-table table align-middle mb-0" id="invHoldingsTable">
                        <thead>
                            <tr>
                                <th class="th-left">Asset</th>
                                <th>Buy date</th>
                                <th>Qty</th>
                                <th>Buy price</th>
                                <th>Invested</th>
                                <th>Live price</th>
                                <th>Current value</th>
                                <th>P&amp;L (Rs)</th>
                                <th>P&amp;L %</th>
                                <th>24h</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                @php
                                    $holding = $row->holding;
                                    $tl = $tileColor($row->symbol, $holding->asset_type);
                                    $winloss = $row->pnl === null ? '' : ($row->pnl < 0 ? 'row-lose' : 'row-win');
                                @endphp
                                <tr data-id="{{ $holding->id }}" class="{{ $winloss }}">
                                    <td class="td-left">
                                        <div class="asset-cell">
                                            <div class="asset-tile" style="background: {{ $tl }};">{{ substr($row->symbol, 0, 2) }}</div>
                                            <div>
                                                <div class="a-name">{{ $row->symbol }}
                                                    <span class="type-pill {{ $holding->asset_type === 'crypto' ? 'tp-crypto' : 'tp-stock' }}">{{ $holding->asset_type === 'crypto' ? 'CRYPTO' : 'NSE' }}</span>
                                                </div>
                                                <div class="a-sub">{{ $holding->name }}@if($holding->platform) &middot; {{ $holding->platform }} @endif</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tabnum">{{ $holding->buy_date->format('d M y') }}</td>
                                    <td class="tabnum">{{ $fmtQty($holding->quantity) }}</td>
                                    <td class="tabnum">{{ $fmtPrice($holding->buy_price) }}</td>
                                    <td class="tabnum" data-invested="{{ $row->invested }}">{{ $fmtMoney($row->invested) }}</td>
                                    <td class="tabnum live-price" data-price="{{ $row->price }}"><span class="{{ $row->live ? '' : 'stale' }}">{{ $fmtPrice($row->price) }}</span></td>
                                    <td class="tabnum live-current" data-v="{{ $row->current }}"><span>{{ $fmtMoney($row->current) }}</span></td>
                                    <td class="tabnum live-pnl" data-v="{{ $row->pnl }}">
                                        <span class="{{ $row->pnl === null ? 'stale' : ($row->pnl < 0 ? 'neg' : 'pos') }}">{{ $row->pnl === null ? '—' : ($row->pnl >= 0 ? '+' : '') . $fmtMoney($row->pnl) }}</span>
                                    </td>
                                    <td class="tabnum live-pnlpct" data-v="{{ $row->pnl_pct }}">
                                        @if ($row->pnl_pct === null)
                                            <span class="stale">—</span>
                                        @else
                                            <span class="chip {{ $row->pnl_pct < 0 ? 'chip-down' : 'chip-up' }}">{{ $row->pnl_pct >= 0 ? '+' : '' }}{{ number_format($row->pnl_pct, 2) }}%</span>
                                        @endif
                                    </td>
                                    <td class="tabnum live-day" data-v="{{ $row->change }}" data-day="{{ $row->day_change }}">
                                        @if ($row->change === null || $row->price === null)
                                            <span class="stale">—</span>
                                        @else
                                            <span class="chip {{ $row->change < 0 ? 'chip-down' : 'chip-up' }}">{{ $row->change >= 0 ? '+' : '' }}{{ number_format($row->change, 2) }}%</span>
                                        @endif
                                    </td>
                                    <td class="td-right" style="text-align:right;">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button type="button" class="btn btn-sm text-muted inv-ico" data-edit="{{ $holding->id }}" title="Edit"><i class="fas fa-pen"></i></button>
                                            <form method="POST" action="{{ route('investments.destroy', $holding->id) }}" class="inv-del">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm text-muted inv-ico" title="Remove"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="tf-left" colspan="5">Portfolio total</td>
                                <td></td>
                                <td class="tabnum" id="tfCurrent">{{ $fmtMoney($totals->current) }}</td>
                                <td class="tabnum" id="tfPnL" style="color: {{ $totals->pnl < 0 ? 'var(--inv-down)' : 'var(--inv-up)' }};">{{ $totals->pnl >= 0 ? '+' : '' }}{{ $fmtMoney($totals->pnl) }}</td>
                                <td class="tabnum" style="color: {{ $totals->pnl_pct < 0 ? 'var(--inv-down)' : 'var(--inv-up)' }};">{{ $totals->pnl_pct >= 0 ? '+' : '' }}{{ number_format($totals->pnl_pct, 2) }}%</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
    <!-- ADD MODAL -->
    <div class="modal fade" id="invAddModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-content-inv">
                <form method="POST" action="{{ route('investments.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="invAddTitle"><i class="fas fa-coins me-2 text-warning"></i> Add crypto position</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('investments._fields')
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-inv" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-inv btn-inv-gold"><i class="fas fa-plus me-1"></i> Add position</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="invEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-content-inv">
                <form method="POST" action="#" id="invEditForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="eId">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-pen me-2" style="color:var(--inv-gold);"></i> Edit position</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('investments._fields')
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-inv" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-inv btn-inv-gold"><i class="fas fa-save me-1"></i> Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    .inv-table.dataTable { border-collapse: collapse !important; }
    .inv-table.dataTable thead th { background-color: #eef2f7; }
    .inv-table.dataTable thead th.sorting:after, .inv-table.dataTable thead th.sorting_asc:after, .inv-table.dataTable thead th.sorting_desc:after { opacity: .7; color: var(--inv-muted); }
    .dataTables_wrapper .dataTables_length label, .dataTables_wrapper .dataTables_filter input, .dataTables_wrapper .dataTables_info { color: var(--inv-muted) !important; }
    .dataTables_wrapper .dataTables_filter input { background: #ffffff; border: 1px solid #ced6e2; border-radius: 8px; color: var(--inv-ink); padding: 5px 10px; }
    .dataTables_wrapper .dataTables_filter input:focus { border-color: rgba(183, 132, 47, .6); box-shadow: none; }
    .dataTables_wrapper .dataTables_length select { background: #ffffff; border: 1px solid #ced6e2; color: var(--inv-ink); border-radius: 8px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button { color: #41506a !important; background: #ffffff !important; border: 1px solid #ced6e2 !important; border-radius: 8px !important; margin: 0 2px; padding: 4px 10px !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { color: #7d530d !important; background: #f3e4c4 !important; border-color: #d4b46a !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { color: #0f172a !important; background: #eef2f7 !important; }
    .dataTables_processing { background: rgba(244, 246, 249, .95) !important; color: var(--inv-muted) !important; }
</style>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
(function () {
    'use strict';

    const LIVE_URL = {!! json_encode(route('investments.live')) !!};
    const EDIT_URL = {!! json_encode(url('investments')) !!};

    const inr = new Intl.NumberFormat('en-IN');

    function fmtMoney(v) { if (v === null || v === undefined || isNaN(v)) return '\u2014'; return '\u20B9' + inr.format(Math.round(v)); }
    function fmtCompact(v) {
        if (v === null || v === undefined || isNaN(v)) return '\u2014';
        const abs = Math.abs(v);
        if (abs >= 1e7) return (v / 1e7).toFixed(2).replace(/\.00$/, '') + ' Cr';
        if (abs >= 1e5) return (v / 1e5).toFixed(2).replace(/\.00$/, '') + ' L';
        if (abs >= 1000) return (v / 1000).toFixed(1).replace(/\.0$/, '') + ' K';
        return inr.format(Math.round(v));
    }
    function fmtPrice(v) {
        if (v === null || v === undefined || isNaN(v)) return '\u2014';
        if (Math.abs(v) >= 1000) return '\u20B9' + inr.format(Math.round(v));
        if (Math.abs(v) >= 1) return '\u20B9' + v.toFixed(2);
        return '\u20B9' + v.toFixed(6).replace(/0+$/, '').replace(/\.$/, '');
    }
    function fmtQty(v) {
        if (Math.abs(v) >= 1000) return inr.format(Math.round(v));
        if (Math.abs(v) >= 1) return v.toFixed(2);
        return v.toFixed(8).replace(/0+$/, '').replace(/\.$/, '');
    }
    const sign = v => v > 0 ? '+' : '';

    function setCell(tr, cls, value, clsName) {
        const cell = tr.querySelector('td.' + cls);
        if (!cell) return;
        const span = cell.querySelector('span');
        if (!span) return;
        span.textContent = value;
        if (clsName) { span.className = clsName; }
    }

    function applyRows(rows) {
        rows.forEach(function (row) {
            const tr = document.querySelector('#invHoldingsTable tbody tr[data-id="' + row.id + '"]');
            if (!tr) return;

            const live = row.live;
            setCell(tr, 'live-price', fmtPrice(row.price), live ? '' : 'stale');
            setCell(tr, 'live-current', fmtMoney(row.current), '');

            const pnlTd = tr.querySelector('td.live-pnl');
            const pnl = row.pnl;
            if (pnlTd) {
                const span = pnlTd.querySelector('span');
                if (span) { span.textContent = pnl === null ? '\u2014' : sign(pnl) + fmtMoney(pnl); span.className = pnl === null ? 'stale' : (pnl < 0 ? 'neg' : 'pos'); }
            }
            const pnlpTd = tr.querySelector('td.live-pnlpct');
            if (pnlpTd) {
                const span = pnlpTd.querySelector('span');
                if (span) {
                    span.textContent = pnl === null || row.pnl_pct === null ? '\u2014' : sign(row.pnl_pct) + Number(row.pnl_pct).toFixed(2) + '%';
                    span.className = pnl === null || row.pnl_pct === null ? 'stale chip chip-flat' : 'chip ' + (row.pnl_pct < 0 ? 'chip-down' : 'chip-up');
                }
            }
            const dayTd = tr.querySelector('td.live-day');
            if (dayTd) {
                const span = dayTd.querySelector('span');
                const chg = row.change;
                if (span) {
                    span.textContent = (chg === null || !live) ? '\u2014' : sign(chg) + Number(chg).toFixed(2) + '%';
                    span.className = (chg === null || !live) ? 'stale' : 'chip ' + (chg < 0 ? 'chip-down' : 'chip-up');
                    span.textContent = (chg === null || !live) ? '\u2014' : sign(chg) + Number(chg).toFixed(2) + '%';
                }
            }

            tr.classList.toggle('row-win', live && pnl !== null && pnl >= 0);
            tr.classList.toggle('row-lose', live && pnl !== null && pnl < 0);
        });
    }

    function recomputeAll(rows) {
        let invested = 0, current = 0, pnl = 0, day = 0, liveCount = 0;
        const table = document.querySelector('#invHoldingsTable tbody');
        if (table) {
            table.querySelectorAll('tr').forEach(function (tr) {
                const cell = tr.querySelector('td.live-pnl');
                if (!cell) return;
                const inv = tr.querySelector('td[data-invested]');
                const cur = tr.querySelector('td.live-current');
                const dayCell = tr.querySelector('td.live-day');
                invested += parseFloat(inv.getAttribute('data-invested') || '0');
                const curV = parseFloat((cur && cur.getAttribute('data-v')) || '0');
                if (curV > 0) { current += curV; liveCount++; }
                const p = parseFloat((cell.getAttribute('data-v')) || '0');
                if (!isNaN(p)) { pnl += p; }
                const d = parseFloat((dayCell && dayCell.getAttribute('data-day')) || '0');
                day += d;
            });
        }
        const pct = invested > 0 ? (pnl / invested) * 100 : 0;

        document.getElementById('tfCurrent').textContent = fmtMoney(current);
        document.getElementById('tfPnL').textContent = sign(pnl) + fmtMoney(pnl);
        document.getElementById('tfPnL').style.color = pnl < 0 ? 'var(--inv-down)' : 'var(--inv-up)';
        document.querySelector('#invHoldingsTable tfoot tr td:nth-child(9)').textContent = sign(pct) + pct.toFixed(2) + '%';

        document.getElementById('kpiInvested').textContent = fmtCompact(invested);
        document.getElementById('kpiCurrent').textContent = fmtCompact(current);
        document.getElementById('kpiDay').textContent = sign(day) + fmtCompact(day);
        document.getElementById('kpiDay').className = 'k-value ' + (day < 0 ? 'neg' : 'pos');

        const nv = document.getElementById('netValue');
        nv.textContent = fmtCompact(pnl);
        nv.className = 'net-value ' + (pnl < 0 ? 'net-down' : 'net-up');
        document.getElementById('netPct').textContent = sign(pct) + pct.toFixed(2) + '%';
        document.getElementById('netDay').textContent = 'today ' + sign(day) + fmtCompact(day);
    }

    function payloadToRows(payload) {
        return payload.rows || [];
    }

    function showToast(icon, title) {
        Swal.fire({ icon: icon, title: title, toast: true, position: 'top-end', showConfirmButton: false, timer: 2600, timerProgressBar: true, background: '#ffffff', color: '#1f2c40', customClass: { popup: 'inv-toast' } });
    }

    function fetchAndApply(force) {
        const url = force ? LIVE_URL + (LIVE_URL.indexOf('?') > -1 ? '&' : '?') + 'refresh=1' : LIVE_URL;
        return $.getJSON(url).done(function (payload) {
            const rows = payloadToRows(payload);
            applyRows(rows);
            recomputeAll(rows);
            const tick = document.getElementById('inv-ticker');
            if (tick && payload.updated_at) {
                tick.textContent = 'streaming \u00b7 updated ' + payload.updated_at;
            }
        }).fail(function () {
            const tick = document.getElementById('inv-ticker');
            if (tick) tick.textContent = 'price feed unreachable \u2014 retrying\u2026';
        });
    }

    const tableEl = document.getElementById('invHoldingsTable');
    let holdingsTable = null;

    function initDT() {
        if (!tableEl) return;
        holdingsTable = $('#invHoldingsTable').DataTable({
            order: [[7, 'desc']],
            pageLength: 50,
            lengthChange: false,
            dom: '<"d-flex justify-content-between align-items-center mb-2 px-3 pt-3"<"datatable-hint text-muted small"l>f>rtip',
            columnDefs: [
                { targets: [10], orderable: false, searchable: false },
                { targets: [2, 3, 4, 5, 6, 7], type: 'num' },
                { targets: [8, 9], type: 'num-fmt' }
            ],
            language: { search: 'Filter: ' }
        });
    }

    $(function () {
        initDT();

        // Type toggle
        $(document).on('click', '#invTypeToggle .inv-type-opt', function () {
            const type = $(this).data('type');
            $('#invTypeToggle .inv-type-opt').removeClass('selected');
            $(this).addClass('selected');
            $('#fSymbol').attr('list', type === 'crypto' ? 'coinSuggestions' : 'stockSuggestions');
            $('#fSymbol').attr('placeholder', type === 'crypto' ? 'e.g. BTC' : 'e.g. RELIANCE');
            $('#fName').attr('placeholder', type === 'crypto' ? 'e.g. Bitcoin' : (type === 'stock' ? 'e.g. Reliance Industries' : ''));
            $('#invAddTitle').html(type === 'crypto'
                ? '<i class="fas fa-coins me-2 text-warning"></i> Add crypto position'
                : '<i class="fas fa-chart-line me-2" style="color:var(--inv-gold);"></i> Add Indian stock');
        });

        // Add buttons with preset type
        $('[data-preset]').on('click', function () {
            const type = $(this).data('preset');
            $('#invTypeToggle .inv-type-opt[data-type="' + type + '"]').trigger('click');
        });

        function fillForm($form, data) {
            $form.find('[name=asset_type][value="' + (data.asset_type || 'crypto') + '"]').prop('checked', true);
            $('#invTypeToggle .inv-type-opt[data-type="' + (data.asset_type || 'crypto') + '"]').trigger('click');
            $form.find('[name=symbol]').val(data.symbol || '');
            $form.find('[name=name]').val(data.name || '');
            $form.find('[name=quantity]').val(data.quantity !== undefined ? data.quantity : '');
            $form.find('[name=buy_price]').val(data.buy_price !== undefined ? data.buy_price : '');
            $form.find('[name=buy_date]').val(data.buy_date || '');
            $form.find('[name=platform]').val(data.platform || '');
            $form.find('[name=notes]').val(data.notes || '');
        }

        // Edit flow
        $(document).on('click', '[data-edit]', function () {
            const id = $(this).data('edit');
            const $form = $('#invEditForm');
            $.getJSON(EDIT_URL + '/' + id + '/edit').done(function (data) {
                $form.find('[name=id]').val(id);
                $form.attr('action', EDIT_URL + '/' + id);
                fillForm($form, data);
                $('#eId').val(id);
                $('#invEditModal').modal('show');
            }).fail(function () { showToast('error', 'Could not load the position.'); });
        });

        // Reopen modal after a validation error
        const reopen = {!! json_encode($reopen) !!};
        if (reopen) {
            const [mode, data] = reopen;
            if (mode === 'edit') {
                fillForm($('#invEditForm'), data);
                $('#invEditForm').find('[name=id]').val(data.id || '');
                $('#invEditForm').attr('action', EDIT_URL + '/' + (data.id || ''));
                $('#invEditModal').modal('show');
            } else {
                $('#invTypeToggle .inv-type-opt[data-type="' + (data.asset_type || 'crypto') + '"]').trigger('click');
                fillForm($('#invAddModal form'), data);
                $('#invAddModal').modal('show');
            }
        }

        // Delete confirm
        $(document).on('submit', '.inv-del', function (e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Remove this position?',
                text: 'It will be taken out of your portfolio and profit/loss totals.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Remove',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Keep it',
                background: '#ffffff',
                color: '#1f2c40'
            }).then(result => { if (result.isConfirmed) form.submit(); });
        });

        // Refresh
        $('#invRefresh').on('click', function () {
            const btn = this;
            btn.disabled = true;
            fetchAndApply(true).always(function () {
                btn.disabled = false;
                showToast('success', 'Prices refreshed');
            });
        });

        // Poll every 30s
        if (tableEl) setInterval(function () { fetchAndApply(false); }, 30000);
    });
})();
</script>
@endpush