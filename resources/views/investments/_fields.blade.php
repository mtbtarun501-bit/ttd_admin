{{-- Shared add/edit fields for the holding forms. Values are driven by JS. --}}
<div class="row g-3">
    <div class="col-12">
        <div class="d-flex gap-2" id="invTypeToggle">
            <label class="inv-type-opt selected" data-type="crypto">
                <input type="radio" name="asset_type" value="crypto" checked>
                <i class="fas fa-coins me-1"></i> Crypto
            </label>
            <label class="inv-type-opt" data-type="stock">
                <input type="radio" name="asset_type" value="stock">
                <i class="fas fa-chart-line me-1"></i> Indian stock
            </label>
        </div>
        <div class="form-text mt-1">Crypto is priced via CoinGecko; stocks via Yahoo Finance (NSE / BSE).</div>
    </div>

    <div class="col-md-6">
        <div class="form-label">Symbol</div>
        <input type="text" name="symbol" id="fSymbol" class="form-control" list="coinSuggestions" placeholder="e.g. BTC">
        <datalist id="coinSuggestions">
            @foreach(\App\Services\MarketDataService::COINS as $sym => $coin)
                <option value="{{ $sym }}">{{ $coin['name'] }}</option>
            @endforeach
        </datalist>
        <datalist id="stockSuggestions">
            @foreach(['RELIANCE','TCS','INFY','HDFCBANK','ICICIBANK','KOTAKBANK','AXISBANK','SBIN','BHARTIARTL','ITC','LT','TATAMOTORS','WIPRO','HCLTECH','ASIANPAINT','MARUTI','SUNPHARMA','TATASTEEL','BAJFINANCE','TECHM'] as $stk)
                <option value="{{ $stk }}">NSE {{ $stk }}</option>
            @endforeach
        </datalist>
        <div class="form-text">Use the ticker as on the exchange, e.g. BTC, ETH or RELIANCE, TCS.</div>
    </div>
    <div class="col-md-6">
        <div class="form-label">Asset name</div>
        <input type="text" name="name" id="fName" class="form-control" placeholder="e.g. Bitcoin">
    </div>

    <div class="col-md-4">
        <div class="form-label">Quantity</div>
        <input type="number" name="quantity" id="fQty" class="form-control" min="0.00000001" step="any" placeholder="0.5">
    </div>
    <div class="col-md-4">
        <div class="form-label">Buy price (Rs)</div>
        <input type="number" name="buy_price" id="fPrice" class="form-control" min="0" step="any" placeholder="4800000">
    </div>
    <div class="col-md-4">
        <div class="form-label">Buy date</div>
        <input type="date" name="buy_date" id="fDate" class="form-control" value="{{ now()->toDateString() }}">
    </div>

    <div class="col-md-6">
        <div class="form-label">Platform / exchange <span class="text-muted fw-normal">(optional)</span></div>
        <input type="text" name="platform" id="fPlatform" class="form-control" list="platformSuggestions" placeholder="e.g. Binance, Zerodha">
        <datalist id="platformSuggestions">
            @foreach(['Binance','Coinbase','CoinDCX','CoinSwitch','Zerodha','Groww','Angel One','Upstox','Kuber','WazirX'] as $plat)
                <option value="{{ $plat }}">
            @endforeach
        </datalist>
    </div>
    <div class="col-md-6">
        <div class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></div>
        <input type="text" name="notes" id="fNotes" class="form-control" placeholder="Anything worth remembering">
    </div>
</div>

<style>
    .inv-type-opt { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border: 1px solid var(--inv-line); border-radius: 10px; cursor: pointer; color: var(--inv-muted); font-weight: 600; font-size: .9rem; user-select: none; transition: all .15s; }
    .inv-type-opt input { display: none; }
    .inv-type-opt.selected { border-color: rgba(183, 132, 47, .6); color: #9a6712; background: #f7ecd7; }
</style>