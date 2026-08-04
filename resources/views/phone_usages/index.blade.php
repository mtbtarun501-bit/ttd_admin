@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="module-art-banner banner-phone">
        <div class="banner-content">
            <h2 class="banner-title"><i class="fas fa-mobile-alt"></i> Phone Usage Management</h2>
            <div>
                <a href="{{ route('phone-usages.create') }}" class="btn text-white" style="background:var(--temple-gold);">
                    <i class="fas fa-plus"></i> Add Mobile Number
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover w-100" id="phoneUsagesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member Name</th>
                            <th>Mobile Number</th>
                            <th>Status</th>
                            <th>Can Book Today</th>
                            <th>Next Eligible Date</th>
                            <th>Actions</th>
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
<!-- DataTables CSS/JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#phoneUsagesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('phone-usages.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'member_name', name: 'member_name'},
            {data: 'mobile_number', name: 'mobile_number'},
            {data: 'status', name: 'status', render: function(data) {
                if (data === 'Active') {
                    return '<span class="badge bg-success">Active</span>';
                }
                return '<span class="badge bg-secondary">' + data + '</span>';
            }},
            {data: 'can_book_today', name: 'can_book_today', orderable: false, searchable: false},
            {data: 'next_eligible_date', name: 'next_eligible_date', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });
});
</script>
@endpush
