<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PortfolioService
{
    public function __construct(protected MarketDataService $marketData)
    {
    }

    /**
     * Holdings merged with live prices -> one computed row per holding.
     */
    public function buildRows(Collection $holdings, array $prices): Collection
    {
        return $holdings->map(function ($holding) use ($prices) {
            $symbol = strtoupper($holding->symbol);
            $quote = $prices[$symbol] ?? ['price' => null, 'change' => null];

            $invested = round($holding->quantity * $holding->buy_price, 2);
            $current = $quote['price'] !== null ? round($holding->quantity * $quote['price'], 2) : null;
            $pnl = $current !== null ? round($current - $invested, 2) : null;
            $pnlPct = $invested > 0 && $pnl !== null ? round($pnl / $invested * 100, 2) : null;
            $dayChange = $current !== null && $quote['change'] !== null ? round($current * $quote['change'] / 100, 2) : null;

            return (object) [
                'holding' => $holding,
                'symbol' => $symbol,
                'price' => $quote['price'],
                'change' => $quote['change'],
                'invested' => $invested,
                'current' => $current,
                'pnl' => $pnl,
                'pnl_pct' => $pnlPct,
                'day_change' => $dayChange,
                'live' => $quote['price'] !== null,
            ];
        });
    }

    /**
     * Portfolio-wide rollups.
     */
    public function totals(Collection $rows): object
    {
        $invested = 0.0;
        $current = 0.0;
        $pnl = 0.0;
        $dayChange = 0.0;
        $live = 0;
        $count = $rows->count();

        foreach ($rows as $row) {
            $invested += $row->invested;
            if ($row->current !== null) {
                $current += $row->current;
                $pnl += $row->pnl;
                $dayChange += $row->day_change;
                $live++;
            }
        }

        return (object) [
            'invested' => round($invested, 2),
            'current' => round($current, 2),
            'pnl' => round($pnl, 2),
            'pnl_pct' => $invested > 0 ? round($pnl / $invested * 100, 2) : 0.0,
            'day_change' => round($dayChange, 2),
            'live_count' => $live,
            'count' => $count,
        ];
    }

    /**
     * Crypto vs Stock breakdown.
     */
    public function breakdown(Collection $rows): array
    {
        $categories = [
            \App\Models\PortfolioHolding::TYPE_CRYPTO => ['invested' => 0.0, 'current' => 0.0, 'pnl' => 0.0],
            \App\Models\PortfolioHolding::TYPE_STOCK => ['invested' => 0.0, 'current' => 0.0, 'pnl' => 0.0],
        ];

        foreach ($rows as $row) {
            $type = $row->holding->asset_type;
            if (!isset($categories[$type])) {
                continue;
            }
            $categories[$type]['invested'] += $row->invested;
            if ($row->current !== null) {
                $categories[$type]['current'] += $row->current;
                $categories[$type]['pnl'] += $row->pnl;
            }
        }

        foreach ($categories as &$cat) {
            $cat['invested'] = round($cat['invested'], 2);
            $cat['current'] = round($cat['current'], 2);
            $cat['pnl'] = round($cat['pnl'], 2);
            $cat['pnl_pct'] = $cat['invested'] > 0 ? round($cat['pnl'] / $cat['invested'] * 100, 2) : 0.0;
        }
        unset($cat);

        return $categories;
    }
}