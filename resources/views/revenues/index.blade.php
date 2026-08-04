@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="module-art-banner banner-revenues">
        <div class="banner-content">
            <h2 class="banner-title"><i class="fas fa-chart-line"></i> Revenue Management</h2>
            <div>
                <a href="{{ route('revenues.export') }}" class="btn btn-success me-2"><i class="fas fa-file-csv"></i> Export CSV</a>
                <a href="{{ route('revenues.create') }}" class="btn text-white" style="background:var(--temple-gold);"><i class="fas fa-plus"></i> Record Revenue</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="revenuesTable">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th>Source</th>
                            <th>Amount</th>
                            <th>Date Received</th>
                            <th>Recorded By</th>
                            <th width="100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#revenuesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('revenues.index') }}",
        columns: [
            {data: 'source', name: 'source'},
            {data: 'amount_formatted', name: 'amount'},
            {data: 'revenue_date', name: 'revenue_date'},
            {data: 'created_by', name: 'created_by'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
});
</script>
@endpush
