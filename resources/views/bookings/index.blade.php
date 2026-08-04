@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="module-art-banner banner-bookings">
        <div class="banner-content">
            <h2 class="banner-title"><i class="fas fa-calendar-check"></i> Bookings Management</h2>
            <div>
                <a href="{{ route('bookings.create') }}" class="btn text-white" style="background:var(--temple-gold);"><i class="fas fa-plus"></i> New Booking</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0" style="border-radius:15px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="bookingsTable">
                    <thead style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                        <tr>
                            <th>Booking No</th>
                            <th>Devotee Name</th>
                            <th>Booking Type</th>
                            <th>Booking Date</th>
                            <th>Booked By (Admin)</th>
                            <th>Status</th>
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

<!-- Edit Status Modal -->
<div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editStatusForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header" style="background:rgba(128, 0, 0, 0.05); color:var(--temple-maroon);">
                    <h5 class="modal-title" id="editStatusModalLabel"><i class="fas fa-edit me-2"></i> Update Booking Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="statusSelect" class="form-label fw-bold">Status</label>
                        <select class="form-select" id="statusSelect" name="status" required>
                            <option value="pending">Pending / Not Booked</option>
                            <option value="confirmed">Booked / Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    @hasanyrole('Super Admin|Operator')
                    <div class="mb-3">
                        <label for="createdBySelect" class="form-label fw-bold">Booked By (Admin)</label>
                        <select class="form-select" id="createdBySelect" name="created_by" required>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}">
                                    {{ $admin->name }} ({{ implode(', ', $admin->getRoleNames()->toArray()) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endhasanyrole
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn text-white" style="background:var(--temple-gold);">Save Changes</button>
                </div>
            </form>
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
    $('#bookingsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('bookings.index') }}",
        columns: [
            {data: 'booking_no', name: 'booking_no'},
            {data: 'devotee_name', name: 'devotee.name'},
            {data: 'booking_type', name: 'bookingType.name'},
            {data: 'booking_date', name: 'booking_date'},
            {data: 'booked_by', name: 'creator.name'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    $('#bookingsTable').on('click', '.edit-status-btn', function() {
        var id = $(this).data('id');
        var status = $(this).data('status').toLowerCase();
        var createdBy = $(this).data('created-by');
        
        var formAction = "{{ route('bookings.updateStatus', ':id') }}";
        formAction = formAction.replace(':id', id);
        
        $('#editStatusForm').attr('action', formAction);
        $('#statusSelect').val(status);
        
        if ($('#createdBySelect').length && createdBy) {
            $('#createdBySelect').val(createdBy);
        }
    });
});
</script>
@endpush
