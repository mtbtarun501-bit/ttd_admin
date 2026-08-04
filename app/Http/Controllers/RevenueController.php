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
            $query = Revenue::query();
            
            if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
                $query->where('created_by', auth()->id());
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('amount_formatted', function($row){
                    return '₹' . number_format($row->amount, 2);
                })
                ->addColumn('action', function($row){
                    $btn = '<form action="'.route('revenues.destroy', $row->id).'" method="POST" style="display:inline-block;">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button type="submit" class="btn btn-sm btn-danger" style="background:var(--temple-maroon); border:none;" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-trash"></i> Delete</button>
                              </form>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('revenues.index');
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

    public function destroy($id)
    {
        $revenue = Revenue::findOrFail($id);
        if (auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            if ($revenue->created_by !== auth()->id()) {
                abort(403, 'Unauthorized access to this record.');
            }
        }

        $this->revenueService->deleteRevenue($id);
        return redirect()->route('revenues.index')->with('success', 'Revenue removed successfully.');
    }
}
