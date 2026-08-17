@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-chart-line text-primary"></i> Indian Stocks Portfolio</h2>
            <div>
                <a href="{{ route('indian-stocks.export') }}" class="btn btn-success text-white me-2"><i class="fas fa-file-csv"></i> Export CSV</a>
                <a href="{{ route('indian-stocks.create') }}" class="btn text-white" style="background:var(--temple-gold);"><i class="fas fa-plus"></i> Add Stock</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body table-responsive">
            <table class="table table-hover table-bordered" id="stocksTable" width="100%">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Symbol</th>
                        <th>Stock Name</th>
                        <th>Buy Date</th>
                        <th>Quantity</th>
                        <th>Buy Price (₹)</th>
                        <th>Total Invested (₹)</th>
                        <th>Live Price (₹)</th>
                        <th>P&L (₹)</th>
                        <th>P&L (%)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<!-- DataTables CSS/JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('#stocksTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('indian-stocks.index') }}",
            columns: [
                {data: 'id', name: 'id'},
                {data: 'stock_symbol', name: 'stock_symbol', render: function(data) {
                    return '<span class="badge bg-secondary">' + data + '</span>';
                }},
                {data: 'stock_name', name: 'stock_name', className: 'fw-bold'},
                {data: 'buy_date', name: 'buy_date'},
                {data: 'quantity', name: 'quantity', className: 'text-end'},
                {data: 'buy_price', name: 'buy_price', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '₹')},
                {data: 'total_investment', name: 'total_investment', className: 'text-end fw-bold', render: $.fn.dataTable.render.number(',', '.', 2, '₹')},
                {data: null, name: 'live_price', searchable: false, orderable: false, className: 'text-end fw-bold live-price', defaultContent: '<i class="fas fa-spinner fa-spin text-muted"></i>'},
                {data: null, name: 'pnl', searchable: false, orderable: false, className: 'text-end fw-bold pnl-amt', defaultContent: '-'},
                {data: null, name: 'pnl_pct', searchable: false, orderable: false, className: 'text-end fw-bold pnl-pct', defaultContent: '-'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            order: [[0, 'desc']],
            drawCallback: function() {
                fetchLivePrices();
            }
        });

        function fetchLivePrices() {
            $.ajax({
                url: "{{ route('api.indian-stocks.live') }}",
                method: "GET",
                success: function(prices) {
                    $('#stocksTable tbody tr').each(function() {
                        var row = table.row(this).data();
                        if (!row) return;

                        var symbol = row.stock_symbol;
                        var buyPrice = parseFloat(row.buy_price);
                        var qty = parseInt(row.quantity);
                        
                        var livePrice = prices[symbol];
                        
                        if (livePrice) {
                            var currentVal = livePrice * qty;
                            var invested = buyPrice * qty;
                            var pnl = currentVal - invested;
                            var pnlPct = (pnl / invested) * 100;
                            
                            var colorClass = pnl >= 0 ? 'text-success' : 'text-danger';
                            var icon = pnl >= 0 ? '<i class="fas fa-caret-up"></i> ' : '<i class="fas fa-caret-down"></i> ';
                            
                            $(this).find('.live-price').html('<span class="text-primary">₹' + livePrice.toLocaleString('en-IN', {minimumFractionDigits: 2}) + '</span>');
                            $(this).find('.pnl-amt').html('<span class="' + colorClass + '">' + icon + '₹' + Math.abs(pnl).toLocaleString('en-IN', {minimumFractionDigits: 2}) + '</span>');
                            $(this).find('.pnl-pct').html('<span class="' + colorClass + '">' + pnlPct.toFixed(2) + '%</span>');
                        } else {
                            $(this).find('.live-price').html('<span class="text-muted small">N/A</span>');
                        }
                    });
                }
            });
        }

        // Poll every 10 seconds for live market updates
        setInterval(fetchLivePrices, 10000);
    });
</script>
@endpush
@endsection
