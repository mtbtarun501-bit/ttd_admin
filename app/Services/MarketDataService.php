<?php

namespace App\Services;

use App\Models\PortfolioHolding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MarketDataService
{
    public const CACHE_TTL = 60;
    public const LAST_UPDATED_KEY = 'portfolio.prices.updated_at';
    public const LAST_KNOWN_KEY = 'portfolio.prices.last_known';

    /**
     * Curated symbol -> CoinGecko id map for the most common coins.
     */
    public const COINS = [
        'BTC' => ['id' => 'bitcoin', 'name' => 'Bitcoin'],
        'ETH' => ['id' => 'ethereum', 'name' => 'Ethereum'],
        'BNB' => ['id' => 'binancecoin', 'name' => 'BNB'],
        'SOL' => ['id' => 'solana', 'name' => 'Solana'],
        'XRP' => ['id' => 'ripple', 'name' => 'XRP'],
        'USDT' => ['id' => 'tether', 'name' => 'Tether'],
        'USDC' => ['id' => 'usd-coin', 'name' => 'USD Coin'],
        'ADA' => ['id' => 'cardano', 'name' => 'Cardano'],
        'DOGE' => ['id' => 'dogecoin', 'name' => 'Dogecoin'],
        'AVAX' => ['id' => 'avalanche-2', 'name' => 'Avalanche'],
        'TRX' => ['id' => 'tron', 'name' => 'TRON'],
        'DOT' => ['id' => 'polkadot', 'name' => 'Polkadot'],
        'LINK' => ['id' => 'chainlink', 'name' => 'Chainlink'],
        'LTC' => ['id' => 'litecoin', 'name' => 'Litecoin'],
        'NEAR' => ['id' => 'near', 'name' => 'NEAR Protocol'],
        'TON' => ['id' => 'the-open-network', 'name' => 'Toncoin'],
        'SUI' => ['id' => 'sui', 'name' => 'Sui'],
        'APT' => ['id' => 'aptos', 'name' => 'Aptos'],
        'SHIB' => ['id' => 'shiba-inu', 'name' => 'Shiba Inu'],
        'MATIC' => ['id' => 'polygon-ecosystem-token', 'name' => 'Polygon'],
        'POL' => ['id' => 'polygon-ecosystem-token', 'name' => 'Polygon'],
        'ARB' => ['id' => 'arbitrum', 'name' => 'Arbitrum'],
        'FIL' => ['id' => 'filecoin', 'name' => 'Filecoin'],
        'ATOM' => ['id' => 'cosmos', 'name' => 'Cosmos'],
        'PEPE' => ['id' => 'pepe', 'name' => 'Pepe'],
        'UNI' => ['id' => 'uniswap', 'name' => 'Uniswap'],
        'AAVE' => ['id' => 'aave', 'name' => 'Aave'],
        'ETC' => ['id' => 'ethereum-classic', 'name' => 'Ethereum Classic'],
        'XLM' => ['id' => 'stellar', 'name' => 'Stellar'],
        'BCH' => ['id' => 'bitcoin-cash', 'name' => 'Bitcoin Cash'],
        'EOS' => ['id' => 'eos', 'name' => 'EOS'],
        'OP' => ['id' => 'optimism', 'name' => 'Optimism'],
        'SAND' => ['id' => 'the-sandbox', 'name' => 'The Sandbox'],
        'HBAR' => ['id' => 'hedera-hashgraph', 'name' => 'Hedera'],
        'INJ' => ['id' => 'injective', 'name' => 'Injective'],
    ];

    /**
     * Resolve raw symbols (e.g. "BTC", "bitcoin", "BTCUSDT") to (symbol, coingecko id).
     */
    public function resolveCoin(string $rawSymbol): array
    {
        $symbol = strtoupper(trim($rawSymbol));

        if (isset(self::COINS[$symbol])) {
            return ['symbol' => $symbol, 'currency' => $symbol, 'id' => self::COINS[$symbol]['id'], 'name' => self::COINS[$symbol]['name']];
        }

        // Unknown -> resolve against CoinGecko's full coin list (cached 24h).
        $list = $this->coinList();
        $candidates = $list['by_symbol'][$symbol] ?? $list['by_id'][strtolower(trim($rawSymbol))] ?? null;

        if ($candidates) {
            $coin = $candidates[0];
            return ['symbol' => $symbol, 'currency' => $symbol, 'id' => $coin['id'], 'name' => $coin['name']];
        }

        return ['symbol' => $symbol, 'currency' => $symbol, 'id' => null, 'name' => null];
    }

    /**
     * Build the lookup tables from CoinGecko /coins/list (cached).
     */
    protected function coinList(): array
    {
        return Cache::remember('coingecko.coins.list', 86400, function () {
            $byId = [];
            $bySymbol = [];

            try {
                $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/coins/list');
                if ($response->successful()) {
                    foreach ($response->json() as $coin) {
                        if (!empty($coin['id']) && !empty($coin['symbol'])) {
                            $entry = ['id' => $coin['id'], 'symbol' => strtoupper($coin['symbol']), 'name' => $coin['name'] ?? ''];
                            $byId[$coin['id']] = $entry;
                            $bySymbol[strtoupper($coin['symbol'])][] = $entry;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Offline: fall through with empty lists.
            }

            return ['by_id' => $byId, 'by_symbol' => $bySymbol];
        });
    }

    /**
     * Fetch current prices for a set of holdings.
     *
     * Returns: symbol (uppercased) => ['price' => float|null, 'change' => float|null, 'source' => string]
     */
    public function fetchPrices(iterable $holdings, bool $force = false): array
    {
        $crypto = [];
        $stocks = [];

        foreach ($holdings as $holding) {
            $symbol = strtoupper($holding->symbol);
            if ($holding->asset_type === PortfolioHolding::TYPE_STOCK || str_ends_with($symbol, '.NS') || str_ends_with($symbol, '.BO')) {
                $stocks[] = $symbol;
            } else {
                $crypto[$symbol] = $holding->asset_type === PortfolioHolding::TYPE_CRYPTO ? self::COINS[$symbol]['id'] ?? $this->resolveCoin($symbol)['id'] : null;
            }
        }

        $cacheKey = 'portfolio.prices.' . md5(serialize(array_keys($crypto)) . '|' . serialize($stocks));
        if ($force) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($crypto, $stocks) {
            Cache::put(self::LAST_UPDATED_KEY, now()->timestamp, 86400);

            $prices = [];

            if (!empty($crypto)) {
                $prices = array_merge($prices, $this->fetchCryptoPrices($crypto));
            }
            if (!empty($stocks)) {
                $prices = array_merge($prices, $this->fetchStockPrices($stocks));
            }

            // Persist freshly fetched prices as "last known" so transient
            // provider throttling never blanks the dashboard.
            $lastKnown = Cache::get(self::LAST_KNOWN_KEY, []);
            foreach ($prices as $symbol => $quote) {
                if (($quote['price'] ?? null) !== null) {
                    $lastKnown[$symbol . '|' . $quote['source']] = $quote;
                }
            }
            Cache::put(self::LAST_KNOWN_KEY, $lastKnown, 86400);

            // Fill any symbol that failed this round with its last-known price.
            foreach ($prices as $symbol => $quote) {
                if (($quote['price'] ?? null) === null) {
                    $fallback = $lastKnown[$symbol . '|' . $quote['source']] ?? null;
                    if ($fallback) {
                        $prices[$symbol] = $fallback;
                    }
                }
            }

            return $prices;
        });
    }

    protected function fetchCryptoPrices(array $crypto): array
    {
        $symbolToId = [];
        foreach ($crypto as $symbol => $id) {
            if ($id) {
                $symbolToId[$id] = $symbol;
            }
        }

        if (empty($symbolToId)) {
            return array_fill_keys(array_keys($crypto), ['price' => null, 'change' => null, 'source' => 'coingecko']);
        }

        try {
            $ids = implode(',', array_keys($symbolToId));
            $response = Http::timeout(8)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => $ids,
                'vs_currencies' => 'inr',
                'include_24hr_change' => 'true',
            ]);

            $out = [];
            foreach (array_keys($crypto) as $symbol) {
                $out[$symbol] = ['price' => null, 'change' => null, 'source' => 'coingecko'];
            }

            if ($response->successful()) {
                foreach ($response->json() as $id => $data) {
                    $symbol = $symbolToId[$id] ?? null;
                    if (!$symbol) {
                        continue;
                    }
                    $out[$symbol] = [
                        'price' => isset($data['inr']) ? (float) $data['inr'] : null,
                        'change' => isset($data['inr_24h_change']) ? (float) $data['inr_24h_change'] : null,
                        'source' => 'coingecko',
                    ];
                }
            }

            return $out;
        } catch (\Throwable $e) {
            return array_fill_keys(array_keys($crypto), ['price' => null, 'change' => null, 'source' => 'coingecko']);
        }
    }

    protected function fetchStockPrices(array $stocks): array
    {
        $out = [];
        foreach ($stocks as $symbol) {
            $out[$symbol] = ['price' => null, 'change' => null, 'source' => 'yahoo'];
        }

        foreach ($stocks as $symbol) {
            $out[$symbol] = $this->fetchOneStockQuote($symbol);
            usleep(350000); // space requests to avoid Yahoo's rapid-fire throttle
        }

        return $out;
    }

    protected function fetchOneStockQuote(string $symbol): array
    {
        $fail = ['price' => null, 'change' => null, 'source' => 'yahoo'];
        $hosts = ['query1.finance.yahoo.com', 'query2.finance.yahoo.com'];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            if ($attempt > 0) {
                usleep(600000); // pace retries
            }
            try {
                $response = Http::timeout(6)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
                    ])
                    ->get("https://{$hosts[$attempt % count($hosts)]}/v8/finance/chart/{$symbol}?interval=1d&range=1d");

                if ($response->successful()) {
                    $meta = $response->json('chart.result.0.meta');
                    $price = $meta['regularMarketPrice'] ?? null;
                    $prevClose = $meta['chartPreviousClose'] ?? null;

                    if ($price === null) {
                        return $fail;
                    }

                    $quote = ['price' => (float) $price, 'change' => null, 'source' => 'yahoo'];
                    if ($prevClose) {
                        $quote['change'] = round(((float) $price - (float) $prevClose) / (float) $prevClose * 100, 2);
                    }

                    return $quote;
                }
            } catch (\Throwable $e) {
                // Fall through to retry.
            }
        }

        return $fail;
    }
}