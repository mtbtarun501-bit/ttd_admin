<?php

namespace App\Http\Controllers;

use App\Services\InvestmentService;
use App\Http\Requests\StoreInvestmentRequest;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use App\Models\Investment;

class InvestmentController extends Controller
{
    protected InvestmentService $investmentService;

    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->investmentService->getGroupedInvestments();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('total_amount_formatted', function($row){
                    return '₹' . number_format($row->total_amount, 2);
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('investments.show', ['investment' => urlencode($row->investor_name)]).'" class="btn btn-sm btn-primary" style="background:var(--temple-gold); border:none;"><i class="fas fa-eye"></i> View</a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('investments.index');
    }

    public function show($investorName)
    {
        $investorName = urldecode($investorName);
        $investments = $this->investmentService->getInvestmentsByInvestor($investorName);
        return view('investments.show', compact('investments', 'investorName'));
    }

    public function create()
    {
        return view('investments.create');
    }

    public function store(StoreInvestmentRequest $request)
    {
        $this->investmentService->createInvestment($request->validated());
        return redirect()->route('investments.index')->with('success', 'Investment added successfully.');
    }

    public function destroy($id)
    {
        $this->investmentService->deleteInvestment($id);
        return redirect()->route('investments.index')->with('success', 'Investment removed successfully.');
    }
}
