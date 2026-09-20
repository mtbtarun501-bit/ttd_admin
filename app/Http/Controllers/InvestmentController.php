<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioHoldingRequest;
use App\Models\PortfolioHolding;
use App\Services\MarketDataService;
use App\Services\PortfolioService;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    protected MarketDataService $marketData;
    protected PortfolioService $portfolio;

    public function __construct(MarketDataService $marketData, PortfolioService $portfolio)
    {
        $this->marketData = $marketData;
        $this->portfolio = $portfolio;
    }

    /**
     * Investment portfolio dashboard (dark live-terminal).
     */
    public function index(Request $request)
    {
        $holdings = $this->holdings();
        $prices = $this->marketData->fetchPrices($holdings);
        $rows = $this->portfolio->buildRows($holdings, $prices);
        $totals = $this->portfolio->totals($rows);
        $lastUpdated = \Illuminate\Support\Facades\Cache::get(MarketDataService::LAST_UPDATED_KEY);

        if ($request->ajax()) {
            return response()->json([
                'updated_at' => now()->format('H:i:s'),
                'rows' => $rows->map(fn ($row) => [
                    'id' => $row->holding->id,
                    'price' => $row->price,
                    'change' => $row->change,
                    'current' => $row->current,
                    'pnl' => $row->pnl,
                    'pnl_pct' => $row->pnl_pct,
                    'day_change' => $row->day_change,
                    'live' => $row->live,
                ])->all(),
            ]);
        }

        return view('investments.index', compact('rows', 'totals', 'lastUpdated'));
    }

    /**
     * Store a new holding (create modal).
     */
    public function store(PortfolioHoldingRequest $request)
    {
        $data = $this->normalize($request->validated());
        $data['created_by'] = auth()->id();

        PortfolioHolding::create($data);

        return redirect()->route('investments.index')
            ->with('success', $data['name'] . ' (' . $data['symbol'] . ') added to your portfolio.');
    }

    /**
     * Return a holding as JSON to prefill the edit modal.
     */
    public function edit($id)
    {
        $holding = PortfolioHolding::findOrFail($id);

        $data = $holding->only([
            'id', 'asset_type', 'symbol', 'name', 'quantity', 'buy_price',
            'platform', 'notes',
        ]);
        $data['buy_date'] = $holding->buy_date ? $holding->buy_date->format('Y-m-d') : null;

        return response()->json($data);
    }

    /**
     * Update an existing holding.
     */
    public function update(PortfolioHoldingRequest $request, $id)
    {
        $holding = PortfolioHolding::findOrFail($id);
        $holding->update($this->normalize($request->validated()));

        return redirect()->route('investments.index')
            ->with('success', $holding->name . ' (' . $holding->symbol . ') updated.');
    }

    /**
     * Delete a holding.
     */
    public function destroy($id)
    {
        $holding = PortfolioHolding::findOrFail($id);
        $holding->delete();

        return redirect()->route('investments.index')
            ->with('success', $holding->name . ' (' . $holding->symbol . ') removed from your portfolio.');
    }

    /**
     * Live prices endpoint (forced refresh via ?refresh=1).
     */
    public function live(Request $request)
    {
        $holdings = $this->holdings();
        $prices = $this->marketData->fetchPrices($holdings, $request->boolean('refresh'));
        $rows = $this->portfolio->buildRows($holdings, $prices);

        return response()->json([
            'updated_at' => now()->format('H:i:s'),
            'prices' => $prices,
            'rows' => $rows->map(fn ($row) => [
                'id' => $row->holding->id,
                'price' => $row->price,
                'change' => $row->change,
                'current' => $row->current,
                'pnl' => $row->pnl,
                'pnl_pct' => $row->pnl_pct,
                'day_change' => $row->day_change,
                'live' => $row->live,
            ])->all(),
        ]);
    }

    protected function holdings()
    {
        return PortfolioHolding::query()->orderByDesc('buy_date')->get();
    }

    /**
     * Normalize symbols and quality-of-life fields before persisting.
     */
    protected function normalize(array $data): array
    {
        $data['symbol'] = strtoupper(trim($data['symbol']));

        if ($data['asset_type'] === PortfolioHolding::TYPE_STOCK) {
            if (!str_ends_with($data['symbol'], '.NS') && !str_ends_with($data['symbol'], '.BO')) {
                $data['symbol'] .= '.NS';
            }
        }

        return $data;
    }
}