<?php

namespace App\Http\Controllers;

use App\Services\RevenueService;
use App\Http\Requests\StoreRevenueRequest;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use App\Models\Revenue;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RevenueExport;

class RevenueController extends Controller
{
    protected RevenueService $revenueService;

    public function __construct(RevenueService $revenueService)
    {
        $this->revenueService = $revenueService;
    }

    public function export()
    {
        return Excel::download(new RevenueExport, 'revenues_list_' . date('Y_m_d') . '.csv');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Revenue::query()
                ->selectRaw('agent_name, SUM(amount) as total_amount, SUM(CASE WHEN MONTH(revenue_date) = MONTH(CURRENT_DATE()) AND YEAR(revenue_date) = YEAR(CURRENT_DATE()) THEN amount ELSE 0 END) as monthly_amount, MAX(revenue_date) as latest_date')
                ->groupBy('agent_name');
            
            if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
                $query->where('created_by', auth()->id());
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('agent_display', function($row){
                    return $row->agent_name ?: 'Unknown';
                })
                ->addColumn('monthly_amount_formatted', function($row){
                    return '₹' . number_format($row->monthly_amount, 2);
                })
                ->addColumn('amount_formatted', function($row){
                    return '₹' . number_format($row->total_amount, 2);
                })
                ->addColumn('action', function($row){
                    $agentParam = urlencode($row->agent_name ?: 'Unknown');
                    $agentRaw = $row->agent_name ?: 'Unknown';
                    
                    $btn = '<div class="d-flex gap-2 justify-content-end">';
                    $btn .= '<a href="'.route('revenues.show', $agentParam).'" class="btn btn-sm text-white d-flex align-items-center shadow-sm" style="background:var(--temple-gold); border-radius: 6px;"><i class="fas fa-eye me-1"></i> View</a>';
                    $btn .= '<button type="button" class="btn btn-sm btn-danger delete-agent-btn d-flex align-items-center shadow-sm" data-agent="'.htmlspecialchars($agentRaw, ENT_QUOTES, 'UTF-8').'" style="background:var(--temple-maroon); border:none; border-radius: 6px;"><i class="fas fa-trash-alt me-1"></i> Delete</button>';
                    $btn .= '</div>';
                    
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $baseQuery = Revenue::query();
        if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            $baseQuery->where('created_by', auth()->id());
        }

        $totalRevenue = (clone $baseQuery)->sum('amount');
        $monthlyRevenue = (clone $baseQuery)->whereMonth('revenue_date', now()->month)
                                            ->whereYear('revenue_date', now()->year)
                                            ->sum('amount');
        
        $totalAgents = (clone $baseQuery)->whereNotNull('agent_name')->where('agent_name', '!=', '')->distinct('agent_name')->count('agent_name');

        // Monthly Trend (Last 6 months)
        $monthlyTrend = (clone $baseQuery)
            ->selectRaw('DATE_FORMAT(revenue_date, "%Y-%m") as month, SUM(amount) as total')
            ->where('revenue_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');
            
        $trendLabels = [];
        $trendData = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $trendLabels[] = now()->subMonths($i)->format('M Y');
            $trendData[] = $monthlyTrend->has($monthKey) ? $monthlyTrend[$monthKey]->total : 0;
        }

        // Top 5 Agents
        $topAgents = (clone $baseQuery)
            ->selectRaw('agent_name, SUM(amount) as total')
            ->whereNotNull('agent_name')
            ->where('agent_name', '!=', '')
            ->groupBy('agent_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
            
        $agentLabels = $topAgents->pluck('agent_name')->toArray();
        $agentData = $topAgents->pluck('total')->toArray();

        return view('revenues.index', compact('totalRevenue', 'monthlyRevenue', 'totalAgents', 'trendLabels', 'trendData', 'agentLabels', 'agentData'));
    }

    public function show(Request $request, $agent_name)
    {
        if ($agent_name === 'Unknown') {
            $agent_name = null; // or handle empty string
        }

        if ($request->ajax()) {
            $query = Revenue::with('importBooking.members');
            
            if (is_null($agent_name) || $agent_name === 'Unknown') {
                $query->where(function($q) {
                    $q->whereNull('agent_name')->orWhere('agent_name', '')->orWhere('agent_name', 'Unknown');
                });
            } else {
                $query->where('agent_name', $agent_name);
            }
            
            if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
                $query->where('created_by', auth()->id());
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('members', function($row){
                    if ($row->importBooking && $row->importBooking->members->count() > 0) {
                        $count = $row->importBooking->members->count();
                        $html = '<div class="text-start">';
                        $html .= '<button type="button" class="btn btn-link btn-sm text-decoration-none p-0" data-bs-toggle="collapse" data-bs-target="#members-show-'.$row->id.'">';
                        $html .= '<span class="badge bg-secondary rounded-pill me-1">'.$count.'</span> View Members <i class="fas fa-caret-down"></i>';
                        $html .= '</button>';
                        $html .= '<div class="collapse mt-2" id="members-show-'.$row->id.'">';
                        $html .= '<ul class="list-group list-group-flush" style="font-size:0.85rem;">';
                        foreach($row->importBooking->members as $member) {
                            $html .= '<li class="list-group-item py-1 px-2 border-0 bg-light rounded mb-1"><i class="fas fa-user-circle text-muted me-2"></i> '.htmlspecialchars($member->member_name).'</li>';
                        }
                        $html .= '</ul></div></div>';
                        return $html;
                    }
                    return '<span class="text-muted small">N/A</span>';
                })
                ->addColumn('amount_formatted', function($row){
                    return '₹' . number_format($row->amount, 2);
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end">';
                    $btn .= '<button type="button" class="btn btn-sm btn-danger delete-revenue-btn d-flex align-items-center shadow-sm" data-id="'.$row->id.'" style="background:var(--temple-maroon); border:none; border-radius: 6px;"><i class="fas fa-trash-alt me-1"></i> Delete</button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action', 'members'])
                ->make(true);
        }

        return view('revenues.show', compact('agent_name'));
    }

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
            $query->where(function($q) {
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
}
