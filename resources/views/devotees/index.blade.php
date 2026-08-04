@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="module-art-banner banner-devotees">
        <div class="banner-content">
            <h2 class="banner-title"><i class="fas fa-users"></i> Devotees Management</h2>
            <div>
                <a href="#" id="exportCsvBtn" class="btn btn-success me-2"><i class="fas fa-file-csv"></i> Export CSV</a>
                <a href="#" id="exportJsonBtn" class="btn btn-warning text-dark me-2" style="background:var(--garuda-gold); color:white!important; border:none;"><i class="fas fa-file-code"></i> Export JSON</a>
                <a href="{{ route('devotees.create') }}" class="btn text-white" style="background:var(--temple-gold);"><i class="fas fa-plus"></i> Add Devotee</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="devoteesTable">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th>No</th>
                            <th>Name</th>
                            <th>Family Status</th>
                            <th>Aadhaar</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>State</th>
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
    $('#devoteesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('devotees.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'family_status', name: 'family_status', orderable: false},
            {data: 'aadhaar', name: 'aadhaar'},
            {data: 'phone', name: 'phone'},
            {data: 'city', name: 'city'},
            {data: 'state', name: 'state'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    $('#exportCsvBtn').click(function(e) {
        e.preventDefault();
        var search = $('#devoteesTable').DataTable().search();
        var url = "{{ route('devotees.export') }}";
        if (search) {
            url += "?search=" + encodeURIComponent(search);
        }
        window.location.href = url;
    });

    $('#exportJsonBtn').click(function(e) {
        e.preventDefault();
        var search = $('#devoteesTable').DataTable().search();
        var url = "{{ route('devotees.exportJson') }}";
        if (search) {
            url += "?search=" + encodeURIComponent(search);
        }
        window.location.href = url;
    });
});
</script>
@endpush
