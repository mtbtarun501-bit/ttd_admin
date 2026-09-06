@extends('layouts.admin')

@section('content')
<div class="container-fluid" style="padding: 40px;">
    <div class="module-art-banner banner-devotees">
        <div class="banner-content">
            <h2 class="banner-title"><i class="fas fa-users"></i> Devotees Management</h2>
            <div>
                <a href="#" id="exportCsvBtn" class="btn btn-success me-2"><i class="fas fa-file-csv"></i> Export CSV</a>
                <button type="button" data-bs-toggle="modal" data-bs-target="#exportJsonModal" class="btn btn-warning text-dark me-2" style="background:var(--garuda-gold); color:white!important; border:none;"><i class="fas fa-file-code"></i> Export JSON</button>
                <button type="button" data-bs-toggle="modal" data-bs-target="#importExcelModal" class="btn btn-primary"><i class="fas fa-file-excel"></i> Import Excel</button>
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

<!-- Manage Booking Modal -->
<div class="modal fade" id="manageBookingModal" tabindex="-1" aria-labelledby="manageBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="quickBookingForm">
                @csrf
                <input type="hidden" name="devotee_id" id="quick_devotee_id">
                <div class="modal-header" style="background:var(--garuda-gold); color:white;">
                    <h5 class="modal-title" id="manageBookingModalLabel">Manage Booking for <span id="quick_devotee_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Booked By (User)</label>
                        <select class="form-select" name="booked_by_name" id="booked_by_name" required>
                            <option value="">Select User...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->name }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ticket Type</label>
                        <select class="form-select" name="booking_type_id" id="quick_booking_type_id" required>
                            <option value="">Select Ticket Type...</option>
                            @foreach($bookingTypes as $type)
                                <option value="{{ $type->id }}" data-price="{{ $type->price }}" data-commission="{{ $type->commission_rate }}">{{ $type->name }} (₹{{ $type->price }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ticket Count</label>
                        <input type="number" class="form-control" name="ticket_count" id="quick_ticket_count" value="1" min="1" required>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-6">
                            <label class="form-label text-muted">Commission (Service Charge)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" class="form-control" id="quick_service_charge" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-success fw-bold">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">₹</span>
                                <input type="text" class="form-control fw-bold" id="quick_total_amount" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--temple-maroon); border:none;">Save Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export JSON Modal -->
<div class="modal fade" id="exportJsonModal" tabindex="-1" aria-labelledby="exportJsonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--garuda-gold); color:white;">
                <h5 class="modal-title" id="exportJsonModalLabel">Export JSON by Agent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Agent (Optional)</label>
                    <select class="form-select" id="export_agent_id">
                        <option value="">All Devotees (No Filter)</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">If you select an agent, the export will only contain customers referred by this agent.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning text-dark" id="confirmExportJsonBtn" style="background:var(--garuda-gold); border:none; font-weight:600;"><i class="fas fa-download"></i> Download JSON</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Excel Modal -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('devotees.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="background:var(--garuda-gold); color:white;">
                    <h5 class="modal-title" id="importExcelModalLabel">Import Devotees from Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please ensure your file matches the exact format of the template.
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('devotees.import_template') }}" class="btn btn-sm btn-outline-success"><i class="fas fa-download"></i> Download Template</a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Excel File (.xlsx, .csv)</label>
                        <input class="form-control" type="file" name="import_file" required accept=".xlsx,.xls,.csv">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload & Import</button>
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

    $('#confirmExportJsonBtn').click(function(e) {
        e.preventDefault();
        var search = $('#devoteesTable').DataTable().search();
        var agentId = $('#export_agent_id').val();
        var url = "{{ route('devotees.exportJson') }}";
        var params = [];
        
        if (search) {
            params.push("search=" + encodeURIComponent(search));
        }
        if (agentId) {
            params.push("agent_id=" + encodeURIComponent(agentId));
        }
        
        if (params.length > 0) {
            url += "?" + params.join("&");
        }
        
        $('#exportJsonModal').modal('hide');
        window.location.href = url;
    });
    $('#devoteesTable').on('click', '.manage-booking-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#quick_devotee_id').val(id);
        $('#quick_devotee_name').text(name);
        $('#quickBookingForm')[0].reset();
        $('#quick_service_charge').val('');
        $('#quick_total_amount').val('');
        
        var modal = new bootstrap.Modal(document.getElementById('manageBookingModal'));
        modal.show();
    });

    function calculatePrices() {
        var selectedOption = $('#quick_booking_type_id').find('option:selected');
        var count = parseInt($('#quick_ticket_count').val()) || 1;
        
        if (selectedOption.val()) {
            var price = parseFloat(selectedOption.data('price')) || 0;
            var commission = parseFloat(selectedOption.data('commission')) || 0;
            
            var totalCommission = commission * count;
            var totalAmount = (price * count) + totalCommission;
            
            $('#quick_service_charge').val(totalCommission.toFixed(2));
            $('#quick_total_amount').val(totalAmount.toFixed(2));
        } else {
            $('#quick_service_charge').val('');
            $('#quick_total_amount').val('');
        }
    }

    $('#quick_booking_type_id, #quick_ticket_count').on('change keyup', calculatePrices);

    $('#quickBookingForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var devoteeId = $('#quick_devotee_id').val();
        
        $.ajax({
            url: '/devotees/' + devoteeId + '/quick-booking',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#manageBookingModal').modal('hide');
                    Swal.fire('Success', response.message, 'success');
                } else {
                    Swal.fire('Error', 'Failed to save booking.', 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON.message || 'An error occurred.', 'error');
            }
        });
    });
});
</script>
@endpush
