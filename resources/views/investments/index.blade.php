@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="module-art-banner banner-investments">
        <div class="banner-content">
            <h2 class="banner-title"><i class="fas fa-chart-pie"></i> Monthly Personal Funds</h2>
            <div>
                <a href="{{ route('investments.create') }}" class="btn text-white" style="background:var(--temple-gold);"><i class="fas fa-plus"></i> Add Investment</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="investmentsTable">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th>Investor Name</th>
                            <th>Total Invested</th>
                            <th>Last Investment Date</th>
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
    $('#investmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('investments.index') }}",
        columns: [
            {data: 'investor_name', name: 'investor_name'},
            {data: 'total_amount_formatted', name: 'total_amount'},
            {data: 'last_investment_date', name: 'last_investment_date'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
});
</script>
@endpush
