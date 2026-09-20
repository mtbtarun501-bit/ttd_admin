<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\AgentLedgerService;
use App\Services\RevenueService;
use App\Http\Requests\StoreRevenueRequest;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use App\Models\Revenue;

class RevenueController extends Controller
{
    protected RevenueService $revenueService;
    protected AgentLedgerService $ledger;

    public function __construct(RevenueService $revenueService, AgentLedgerService $ledger)
    {
        $this->revenueService = $revenueService;
        $this->ledger = $ledger;
    }

    /**
     * Revenue dashboard computed from confirmed/completed bookings (earned commission).
     */
    public function index(Request $request)
    {
        $year = $this->resolveYear($request->query('year'));
        $partner = $this->resolvePartner($request->query('partner'));

        if ($request->ajax()) {
            $query = $this->ledger->aggregatePerYear($year, $partner);
            $maxEarned = (float) max((clone $query)->get()->pluck('earned')->map('floatval')->all(), 0.0);

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    $badge = $row->agent_type === Agent::TYPE_IN_PARTNER ? 'bg-primary' : 'bg-secondary';
                    return $row->name . ' <span class="badge ' . $badge . '">' . $row->agent_type_label . '</span>'
                        . ($row->phone ? ' <small class="text-muted d-block">' . e($row->phone) . '</small>' : '');
                })
                ->editColumn('bookings_count', function ($row) {
                    return $row->bookings_count ?: 0;
                })
                ->editColumn('tickets', function ($row) {
                    return $row->tickets ?: 0;
                })
                ->editColumn('earned', function ($row) use ($maxEarned) {
                    $earned = (float) ($row->earned ?? 0);
                    $pct = $maxEarned > 0 ? round($earned / $maxEarned * 100, 1) : 0;
                    $barColor = $maxEarned > 0 && $pct >= 50
                        ? 'linear-gradient(90deg, #198754, #2fa06b)'
                        : ($maxEarned > 0 && $pct >= 25 ? 'linear-gradient(90deg, #20c997, #7adcbf)' : 'linear-gradient(90deg, #e5e7eb, #cbd5e1)');
                    $html = '<div class="fw-bold text-success">₹' . number_format($earned, 0) . '</div>';
                    $html .= '<div class="earn-bar"><div style="width:' . $pct . '%; background:' . $barColor . ';"></div></div>';
                    return $html;
                })
                ->editColumn('spent', function ($row) {
                    return '₹' . number_format($row->spent ?? 0, 0);
                })
                ->editColumn('total', function ($row) {
                    return '₹' . number_format($row->total ?? 0, 0);
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('revenues.show', $row->id) . '" class="btn btn-sm text-white" style="background:var(--temple-gold);"><i class="fas fa-chart-pie"></i> View</a>'
                        . ' <a href="' . route('agent-accounts.show', $row->id) . '?year=' . request('year', date('Y')) . '" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-invoice-dollar"></i> Ledger</a>';
                })
                ->rawColumns(['name', 'earned', 'action'])
                ->make(true);
        }

        $yearTotals = $this->ledger->yearTotals($year, $partner);
        $prevYearEarned = (float) $this->ledger->yearTotals($year - 1, $partner)->earned;
        $yearDelta = $prevYearEarned > 0 ? round(((float) $yearTotals->earned - $prevYearEarned) / $prevYearEarned * 100, 1) : null;

        $allTimeEarned = (float) $this->ledger->totals(null, $partner)->earned;

        $thisMonthEarned = $year === (int) date('Y') ? (float) $this->ledger->overallMonthly($year, $partner)[(int) date('n')]['data']->earned : 0.0;
        $currentMonthNum = (int) date('n');
        $prevMonthEntry = $currentMonthNum === 1
            ? $this->ledger->overallMonthly($year - 1, $partner)[12]
            : $this->ledger->overallMonthly($year, $partner)[$currentMonthNum - 1];
        $lastMonthEarned = (float) $prevMonthEntry['data']->earned;
        $monthDelta = $lastMonthEarned > 0 ? round(($thisMonthEarned - $lastMonthEarned) / $lastMonthEarned * 100, 1) : null;

        $matrix = $this->ledger->yearlyMatrix($year, $partner);
        $overallMonths = $this->ledger->overallMonthly($year, $partner);
        $trend = $this->ledger->annualTrend($year, $partner);
        $top = $this->ledger->topAgents($year, 5, $partner);
        $split = $this->ledger->partnerSplit($year, $partner);

        $activeAgents = Agent::query()->where('status', true);
        if ($partner) {
            $activeAgents->where('agent_type', $partner);
        }
        $activeAgents = $activeAgents->count();

        $monthLabels = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthLabels[$i] = now()->setMonth($i)->format('M');
        }

        $agentCount = Agent::query()->when($partner, fn ($q) => $q->where('agent_type', $partner))->count();

        return view('revenues.index', compact(
            'year', 'partner', 'yearTotals', 'prevYearEarned', 'yearDelta',
            'allTimeEarned', 'thisMonthEarned', 'lastMonthEarned', 'monthDelta',
            'matrix', 'overallMonths', 'trend', 'top', 'split',
            'activeAgents', 'monthLabels', 'agentCount'
        ));
    }

    /**
     * Per-agent revenue drill-down for a year.
     */
    public function show(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);
        $year = $this->resolveYear($request->query('year'));

        $months = $this->ledger->perMonthMatrix($agent->id, $year);

        $yearTotals = (object) [
            'bookings_count' => array_sum(array_column($months, 'bookings_count')),
            'tickets' => array_sum(array_column($months, 'tickets')),
            'spent' => array_sum(array_column($months, 'spent')),
            'earned' => array_sum(array_column($months, 'earned')),
            'total' => array_sum(array_column($months, 'total')),
        ];

        $transactions = $this->ledger->perYearBookings($agent->id, $year);

        return view('revenues.show', compact('agent', 'year', 'months', 'yearTotals', 'transactions'));
    }

    /**
     * CSV export of the yearly matrix (agents x months + totals).
     */
    public function export(Request $request)
    {
        $year = $this->resolveYear($request->query('year'));
        $partner = $this->resolvePartner($request->query('partner'));

        $matrix = $this->ledger->yearlyMatrix($year, $partner);
        $overallMonths = $this->ledger->overallMonthly($year, $partner);

        $monthLabels = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthLabels[$i] = now()->setMonth($i)->format('M');
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="revenue_' . $year . '.csv"',
        ];

        $callback = function () use ($matrix, $overallMonths, $year, $monthLabels) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Revenue (earned commission) - ' . $year]);
            fputcsv($out, array_merge(['Agent', 'Type', 'Phone'], array_values($monthLabels), ['Total']));

            foreach ($matrix as $entry) {
                $row = [$entry['agent']->name, $entry['agent']->agent_type_label, $entry['agent']->phone];
                foreach ($entry['months'] as $cell) {
                    $row[] = number_format($cell->earned, 2);
                }
                $row[] = number_format($entry['total']->earned, 2);
                fputcsv($out, $row);
            }

            $totRow = ['TOTAL', '', ''];
            foreach ($overallMonths as $entry) {
                $totRow[] = number_format($entry['data']->earned, 2);
            }
            $totRow[] = number_format(array_sum(array_column(array_map(fn ($e) => $e['data'], $overallMonths), 'earned')), 2);
            fputcsv($out, $totRow);

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Manual revenue records are archived: creation remains available via the old route if ever needed.
     */
    public function create()
    {
        return view('revenues.create');
    }

    public function store(StoreRevenueRequest $request)
    {
        $this->revenueService->createRevenue($request->validated());
        return redirect()->route('revenues.index')->with('success', 'Revenue recorded successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $revenue = Revenue::findOrFail($id);
        if (auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            if ($revenue->created_by !== auth()->id()) {
                abort(403, 'Unauthorized access to this record.');
            }
        }

        $this->revenueService->deleteRevenue($id);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Revenue removed successfully.']);
        }

        return redirect()->route('revenues.index')->with('success', 'Revenue removed successfully.');
    }

    public function destroyAgent(Request $request, $agent_name)
    {
        if ($agent_name === 'Unknown') {
            $agent_name = null;
        }

        $query = Revenue::query();
        if (is_null($agent_name)) {
            $query->where(function ($q) {
                $q->whereNull('agent_name')->orWhere('agent_name', '')->orWhere('agent_name', 'Unknown');
            });
        } else {
            $query->where('agent_name', $agent_name);
        }

        if (auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            $query->where('created_by', auth()->id());
        }

        $query->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'All revenues for this agent removed successfully.']);
        }

        return redirect()->route('revenues.index')->with('success', 'All revenues for this agent removed successfully.');
    }

    protected function resolveYear($year): int
    {
        $year = (int) $year;
        if ($year < 2020 || $year > (int) date('Y') + 1) {
            return (int) date('Y');
        }
        return $year;
    }

    protected function resolvePartner($partner): ?string
    {
        if (in_array($partner, [Agent::TYPE_IN_PARTNER, Agent::TYPE_OUT_PARTNER], true)) {
            return $partner;
        }
        return null;
    }
}