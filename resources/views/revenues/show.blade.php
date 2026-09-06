@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="module-art-banner banner-revenues mb-4" style="min-height:100px; padding:20px;">
        <div class="banner-content w-100 d-flex justify-content-between align-items-center">
            <h2 class="banner-title mb-0"><i class="fas fa-user-circle"></i> Revenues for: {{ $agent_name ?: 'Unknown' }}</h2>
            <div>
                <a href="{{ route('revenues.index') }}" class="btn btn-secondary me-2"><i class="fas fa-arrow-left"></i> Back to Agents</a>
                <a href="{{ route('revenues.create') }}" class="btn text-white" style="background:var(--temple-gold);"><i class="fas fa-plus"></i> Record Revenue</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-header bg-white pt-3 pb-3">
            <h5 class="mb-0 text-muted"><i class="fas fa-list"></i> Individual Records</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="agentRevenuesTable">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th width="25%">Source / Service</th>
                            <th width="15%">Members</th>
                            <th width="15%">Amount</th>
                            <th width="15%">Date Received</th>
                            <th width="20%">Remarks</th>
                            <th width="10%" class="text-end">Actions</th>
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
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    var agentName = "{{ $agent_name ?: 'Unknown' }}";

    $('#agentRevenuesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ url('revenues') }}/" + encodeURIComponent(agentName),
        columns: [
            {data: 'source', name: 'source'},
            {data: 'members', name: 'members', orderable: false, searchable: false},
            {data: 'amount_formatted', name: 'amount'},
            {data: 'revenue_date', name: 'revenue_date'},
            {data: 'remarks', name: 'remarks'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'},
        ]
    });

    // Pure AJAX Delete for individual records
    $(document).on('click', '.delete-revenue-btn', function() {
        var id = $(this).data('id');
        if (confirm('Are you sure you want to delete this specific revenue record?')) {
            $.ajax({
                url: '/revenues/' + id,
                type: 'POST',
                data: {
                    "_method": "DELETE",
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {
                    if(response.success) {
                        $('#agentRevenuesTable').DataTable().ajax.reload(null, false);
                        if ($('.alert-success').length === 0) {
                            $('.card').before('<div class="alert alert-success alert-dismissible fade show" role="alert">Record removed successfully.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                        }
                    }
                },
                error: function(xhr) {
                    alert('An error occurred while deleting the record.');
                }
            });
        }
    });
});
</script>
@endpush
