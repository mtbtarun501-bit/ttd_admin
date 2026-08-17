<?php

namespace App\Http\Controllers;

use App\Models\IndianStock;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use App\Exports\IndianStocksExport;
use Maatwebsite\Excel\Facades\Excel;

class IndianStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = IndianStock::select('*');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('total_investment', function ($row) {
                    return number_format($row->quantity * $row->buy_price, 2);
                })
                ->addColumn('action', function ($row) {
                    $editUrl = route('indian-stocks.edit', $row->id);
                    $deleteUrl = route('indian-stocks.destroy', $row->id);
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    return "
                        <div class='d-flex'>
                            <a href='{$editUrl}' class='btn btn-sm btn-primary me-1'><i class='fas fa-edit'></i></a>
                            <form action='{$deleteUrl}' method='POST' onsubmit=\"return confirm('Delete this stock?');\">
                                {$csrf}
                                {$method}
                                <button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>
                            </form>
                        </div>
                    ";
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('indian_stocks.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('indian_stocks.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'stock_symbol' => 'required|string|max:50',
            'stock_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'buy_price' => 'required|numeric|min:0',
            'buy_date' => 'required|date',
        ]);

        // Append .NS if it's not present for Yahoo Finance
        if (!str_ends_with(strtoupper($validated['stock_symbol']), '.NS') && !str_ends_with(strtoupper($validated['stock_symbol']), '.BO')) {
            $validated['stock_symbol'] = strtoupper($validated['stock_symbol']) . '.NS';
        } else {
            $validated['stock_symbol'] = strtoupper($validated['stock_symbol']);
        }

        IndianStock::create($validated);

        return redirect()->route('indian-stocks.index')->with('success', 'Stock added successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IndianStock $indianStock)
    {
        return view('indian_stocks.edit', compact('indianStock'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IndianStock $indianStock)
    {
        $validated = $request->validate([
            'stock_symbol' => 'required|string|max:50',
            'stock_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'buy_price' => 'required|numeric|min:0',
            'buy_date' => 'required|date',
        ]);

        if (!str_ends_with(strtoupper($validated['stock_symbol']), '.NS') && !str_ends_with(strtoupper($validated['stock_symbol']), '.BO')) {
            $validated['stock_symbol'] = strtoupper($validated['stock_symbol']) . '.NS';
        } else {
            $validated['stock_symbol'] = strtoupper($validated['stock_symbol']);
        }

        $indianStock->update($validated);

        return redirect()->route('indian-stocks.index')->with('success', 'Stock updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IndianStock $indianStock)
    {
        $indianStock->delete();
        return redirect()->route('indian-stocks.index')->with('success', 'Stock deleted successfully!');
    }

    /**
     * Export to CSV
     */
    public function export()
    {
        return Excel::download(new IndianStocksExport, 'indian_stocks_'.date('Ymd_His').'.csv');
    }

    /**
     * Fetch Live Prices from Yahoo Finance
     */
    public function getLivePrices()
    {
        $symbols = IndianStock::distinct()->pluck('stock_symbol')->toArray();
        if (empty($symbols)) {
            return response()->json([]);
        }

        $symbolsString = implode(',', $symbols);
        $url = "https://query1.finance.yahoo.com/v7/finance/quote?symbols={$symbolsString}";

        try {
            $response = Http::timeout(5)->get($url);
            if ($response->successful()) {
                $results = $response->json('quoteResponse.result', []);
                $prices = [];
                foreach ($results as $stock) {
                    $prices[$stock['symbol']] = $stock['regularMarketPrice'] ?? 0;
                }
                return response()->json($prices);
            }
        } catch (\Exception $e) {
            // Silently fail and return empty if Yahoo API is unreachable
            return response()->json([]);
        }

        return response()->json([]);
    }
}
