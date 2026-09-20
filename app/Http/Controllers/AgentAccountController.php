<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\AgentLedgerService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AgentAccountController extends Controller
{
    protected AgentLedgerService $ledger;

    public function __construct(AgentLedgerService $ledger)
    {
        $this->ledger = $ledger;
    }

    public function index(Request $request)
    {
        $month = $request->query('month');

        if ($month && !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        if ($request->ajax()) {
            $query = $this->ledger->aggregatePerAgent($month);

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    $badge = $row->agent_type === Agent::TYPE_IN_PARTNER ? 'bg-primary' : 'bg-secondary';
                    $label = $row->name . ' <span class="badge ' . $badge . '">' . $row->agent_type_label . '</span>';
                    if ($row->phone) {
                        $label .= ' <small class="text-muted d-block">' . e($row->phone) . '</small>';
                    }
                    return $label;
                })
                ->editColumn('bookings_count', function ($row) {
                    return $row->bookings_count ?: 0;
                })
                ->editColumn('tickets', function ($row) {
                    return $row->tickets ?: 0;
                })
                ->editColumn('spent', function ($row) {
                    return '₹' . number_format($row->spent ?? 0, 2);
                })
                ->editColumn('earned', function ($row) {
                    return '₹' . number_format($row->earned ?? 0, 2);
                })
                ->editColumn('total', function ($row) {
                    return '₹' . number_format($row->total ?? 0, 2);
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('agent-accounts.show', $row->id) . '" class="btn btn-sm text-white" style="background:var(--temple-gold);"><i class="fas fa-file-invoice-dollar"></i> View Ledger</a>';
                })
                ->rawColumns(['name', 'action'])
                ->make(true);
        }

        $totals = $this->ledger->totals($month);
        $trend = $this->ledger->monthlyTrend(6);

        $activeAgents = Agent::where('status', true)->count();
        $periodLabel = $month ? \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') : 'All Time';
        $prevMonth = $month ? \Carbon\Carbon::createFromFormat('Y-m', $month)->subMonth()->format('Y-m') : null;
        $nextMonth = $month && \Carbon\Carbon::createFromFormat('Y-m', $month)->isBefore(now()->startOfMonth()) ? \Carbon\Carbon::createFromFormat('Y-m', $month)->addMonth()->format('Y-m') : null;

        return view('agent_accounts.index', compact(
            'month', 'totals', 'trend', 'activeAgents', 'periodLabel', 'prevMonth', 'nextMonth'
        ));
    }

    public function show(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        $year = (int) ($request->query('year') ?: now()->year);
        $month = $request->query('month');

        if ($month && !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = null;
        }

        $months = $this->ledger->perMonthMatrix($agent->id, $year);

        $yearTotals = (object) [
            'bookings' => array_sum(array_column($months, 'bookings_count')),
            'tickets' => array_sum(array_column($months, 'tickets')),
            'spent' => array_sum(array_column($months, 'spent')),
            'earned' => array_sum(array_column($months, 'earned')),
            'total' => array_sum(array_column($months, 'total')),
        ];

        $transactions = $month ? $this->ledger->monthlyBookings($agent->id, $month) : collect();

        return view('agent_accounts.show', compact('agent', 'year', 'months', 'yearTotals', 'month', 'transactions'));
    }

    public function export(Request $request)
    {
        $month = $request->query('month');

        if ($month && !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $agents = $this->ledger->aggregatePerAgent($month)->get();
        $totals = $this->ledger->totals($month);

        $periodLabel = $month ? \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') : 'All Time';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="agent_ledger_' . ($month ?: 'all_time') . '.csv"',
        ];

        $callback = function () use ($agents, $totals, $periodLabel) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Agent Ledger - ' . $periodLabel]);
            fputcsv($out, ['Agent', 'Type', 'Phone', 'Bookings', 'Tickets', 'Spent (Rs)', 'Earned (Rs)', 'Total (Rs)']);

            foreach ($agents as $agent) {
                fputcsv($out, [
                    $agent->name,
                    $agent->agent_type_label,
                    $agent->phone,
                    $agent->bookings_count ?: 0,
                    $agent->tickets ?: 0,
                    number_format($agent->spent ?? 0, 2),
                    number_format($agent->earned ?? 0, 2),
                    number_format($agent->total ?? 0, 2),
                ]);
            }

            fputcsv($out, [
                'TOTALS', '', '',
                $totals->bookings_count,
                $totals->tickets,
                number_format($totals->spent, 2),
                number_format($totals->earned, 2),
                number_format($totals->total, 2),
            ]);

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}