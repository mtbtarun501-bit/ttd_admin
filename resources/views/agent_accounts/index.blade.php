@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="module-art-banner" style="background: linear-gradient(135deg, var(--temple-maroon) 0%, #4a0000 100%); padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); color: white; display: flex; justify-content: space-between; align-items: center;">
        <div class="banner-content">
            <h2 class="banner-title m-0" style="font-weight: 700;"><i class="fas fa-file-invoice-dollar me-2"></i> Agent Accounts Ledger</h2>
            <p class="m-0 mt-2 text-white-50">Financial summary of tickets, commissions, and total amounts grouped by Referred Agents.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="agentAccountsTable">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th>No</th>
                            <th>Agent Name</th>
                            <th>Total Bookings</th>
                            <th>Total Tickets</th>
                            <th>Total Commission</th>
                            <th>Total Amount</th>
                            <th width="150px">Actions</th>
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
    $('#agentAccountsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('agent-accounts.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'name', className: 'fw-bold'},
            {data: 'total_bookings', name: 'total_bookings', searchable: false},
            {data: 'total_tickets', name: 'total_tickets', searchable: false},
            {data: 'total_commission', name: 'total_commission', className: 'text-danger fw-bold', searchable: false},
            {data: 'total_amount', name: 'total_amount', className: 'text-success fw-bold', searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'asc']]
    });
});
</script>
@endpush
