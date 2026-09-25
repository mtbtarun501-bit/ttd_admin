@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2>Phone Usage Details</h2>
                <a href="{{ route('phone-usages.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>
            <div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookingModal">
                    <i class="fas fa-plus"></i> Add Booking
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- Phone Details -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Member Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Member Name:</strong> {{ $phoneUsage->member_name }}</p>
                    <p><strong>Mobile Number:</strong> <span class="badge bg-primary fs-6">{{ $phoneUsage->mobile_number }}</span></p>
                    <p><strong>Status:</strong> 
                        @if($phoneUsage->status == 'Active')
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </p>
                    <p><strong>Remarks:</strong> {{ $phoneUsage->remarks ?: 'None' }}</p>
                    <p><strong>Added On:</strong> {{ $phoneUsage->created_at->format('d M Y') }}</p>
                    
                    <a href="{{ route('phone-usages.edit', $phoneUsage->id) }}" class="btn btn-outline-primary btn-sm mt-3"><i class="fas fa-edit"></i> Edit Details</a>
                </div>
            </div>
        </div>

        <!-- Eligibility Matrix -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Eligibility Matrix</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th>Last Booked</th>
                                    <th>Next Eligible</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($phoneUsage->serviceStatuses->sortBy('sevaType.display_order') as $status)
                                <tr data-seva-id="{{ $status->seva_type_id }}">
                                    <td><strong>{{ $status->sevaType->name }}</strong> <br><small class="text-muted">{{ $status->sevaType->cooldown_months }}m + 5d cooldown</small></td>
                                    <td class="cell-last-booked">{{ $status->last_booked_date ? $status->last_booked_date->format('d M Y') : 'Never' }}</td>
                                    <td class="cell-next-eligible">{{ $status->next_eligible_date ? $status->next_eligible_date->format('d M Y') : '-' }}</td>
                                    <td class="cell-status">
                                        @if(!$status->next_eligible_date || \Carbon\Carbon::today()->greaterThanOrEqualTo($status->next_eligible_date))
                                            <span class="badge bg-success">Eligible</span>
                                        @else
                                            @php
                                                $daysLeft = \Carbon\Carbon::today()->diffInDays($status->next_eligible_date, false);
                                            @endphp
                                            @if($daysLeft <= 15)
                                                <span class="badge bg-warning text-dark">Becomes Eligible Soon</span>
                                            @else
                                                <span class="badge bg-danger">In Cooldown</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking History -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Booking History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="bookingHistoryTable" class="table table-bordered" @if($phoneUsage->bookingHistories->count() === 0) style="display: none;" @endif>
                            <thead class="table-light">
                                <tr>
                                    <th>Booking Date</th>
                                    <th>Service</th>
                                    <th>Booking / Remarks</th>
                                    <th>Added By</th>
                                    <th>Recorded On</th>
                                </tr>
                            </thead>
                            <tbody id="bookingHistoryBody">
                                @foreach($phoneUsage->bookingHistories as $history)
                                <tr>
                                    <td>{{ $history->booking_date->format('d M Y') }}</td>
                                    <td><span class="badge bg-info">{{ $history->sevaType->name }}</span></td>
                                    <td>
                                        @if($history->booking)
                                            <strong>{{ $history->booking->booking_no }}</strong><br>
                                            <small class="text-muted">{{ $history->booking->agent ? 'Agent: ' . $history->booking->agent->name : '' }}</small>
                                        @else
                                            {{ $history->remarks ?: '-' }}
                                        @endif
                                    </td>
                                    <td>{{ $history->creator ? $history->creator->name : 'System' }}</td>
                                    <td>{{ $history->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p id="noHistoryMsg" class="text-muted mb-0" @if($phoneUsage->bookingHistories->count() > 0) style="display: none;" @endif>
                        No booking history recorded yet.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Booking Modal -->
<div class="modal fade" id="addBookingModal" tabindex="-1" aria-labelledby="addBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBookingModalLabel">Record New Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addBookingForm" action="{{ route('phone-usages.bookings.store', $phoneUsage->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Service / Seva <span class="text-danger">*</span></label>
                        <select name="seva_type_id" class="form-select" required>
                            <option value="">Select Service...</option>
                            @foreach($sevas as $seva)
                            <option value="{{ $seva->id }}">{{ $seva->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Booking Date <span class="text-danger">*</span></label>
                        <input type="date" name="booking_date" class="form-control" required value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                        <small class="text-muted">You can only record past or present bookings.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Zero-Latency Optimistic Booking Engine with 3 Reliability Safeguards
 *
 * 1. 0ms Immediate UI: Modal hides instantly (0ms), table prepends row (0ms),
 *    and matrix updates to temporary syncing state without waiting for network.
 * 2. Safeguard 1 (Tab-Close Guard): window.beforeunload blocks closing the tab
 *    while a background sync is in progress.
 * 3. Safeguard 2 (Syncing State): Row displays a distinct "Saving..." badge until
 *    Supabase returns 200 OK, then transitions to confirmed recorded timestamp.
 * 4. Safeguard 3 (Auto-Rollback & Backup): If Supabase rejects (cooldown, validation,
 *    or network drop), table and matrix cleanly revert to snapshot, backup is saved
 *    to sessionStorage, and modal re-opens with form inputs intact.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('addBookingForm');
    if (!form) return;

    var modalEl = document.getElementById('addBookingModal');
    var modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

    // Active sync counter for Safeguard 1
    window._activeSyncCount = 0;

    function handleBeforeUnload(e) {
        if (window._activeSyncCount > 0) {
            e.preventDefault();
            e.returnValue = 'A booking is currently being saved to the database. Leaving now may cause data loss!';
            return e.returnValue;
        }
    }

    window.addEventListener('beforeunload', handleBeforeUnload);

    function escapeHtml(str) {
        if (!str) return '-';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatDateDMY(dateStr) {
        if (!dateStr) return '';
        var parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var day = parts[2];
        var monthIdx = parseInt(parts[1], 10) - 1;
        var year = parts[0];
        return day + ' ' + (months[monthIdx] || parts[1]) + ' ' + year;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var sevaSelect = form.querySelector('select[name="seva_type_id"]');
        var dateInput = form.querySelector('input[name="booking_date"]');
        var remarksInput = form.querySelector('textarea[name="remarks"]');

        var sevaId = sevaSelect.value;
        var sevaName = sevaSelect.options[sevaSelect.selectedIndex] ? sevaSelect.options[sevaSelect.selectedIndex].text : 'Seva';
        var bookingDateStr = dateInput.value;
        var formattedDate = formatDateDMY(bookingDateStr);
        var remarks = remarksInput.value.trim();
        var currentUser = "{{ auth()->user()->name ?? 'System' }}";

        if (!sevaId || !bookingDateStr) {
            return;
        }

        // ── 1. Create a Snapshot of Eligibility Row in case we need to Rollback ─
        var matrixRow = document.querySelector('tr[data-seva-id="' + sevaId + '"]');
        var rowSnapshot = null;
        if (matrixRow) {
            rowSnapshot = {
                lastBooked: matrixRow.querySelector('.cell-last-booked').innerHTML,
                nextEligible: matrixRow.querySelector('.cell-next-eligible').innerHTML,
                statusBadge: matrixRow.querySelector('.cell-status').innerHTML
            };

            // 0ms Optimistic Update on Eligibility Row
            matrixRow.querySelector('.cell-last-booked').textContent = formattedDate;
            matrixRow.querySelector('.cell-next-eligible').innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i> Updating...</span>';
            matrixRow.querySelector('.cell-status').innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin me-1"></i> Syncing...</span>';
        }

        // ── 2. Prepend Optimistic Row to Booking History Table (0ms) ───────────
        var tempRowId = 'opt-booking-' + Date.now();
        var table = document.getElementById('bookingHistoryTable');
        var noHistory = document.getElementById('noHistoryMsg');
        var tbody = document.getElementById('bookingHistoryBody');

        if (table) table.style.display = '';
        if (noHistory) noHistory.style.display = 'none';

        if (tbody) {
            tbody.insertAdjacentHTML('afterbegin',
                '<tr id="' + tempRowId + '" class="table-warning-subtle">' +
                '<td>' + escapeHtml(formattedDate) + '</td>' +
                '<td><span class="badge bg-info">' + escapeHtml(sevaName) + '</span></td>' +
                '<td>' + escapeHtml(remarks || '-') + '</td>' +
                '<td>' + escapeHtml(currentUser) + '</td>' +
                '<td class="cell-recorded-sync"><span class="badge bg-warning text-dark"><i class="fas fa-spinner fa-spin me-1"></i> Saving...</span></td>' +
                '</tr>'
            );
        }

        // ── 3. Close Modal IMMEDIATELY (0ms — Zero Waiting!) ──────────────────
        modalInstance.hide();

        // Prepare form data for background fetch
        var formData = new FormData(form);

        // Reset form and set default date to today
        form.reset();
        dateInput.value = new Date().toISOString().split('T')[0];

        // ── 4. Safeguard 1: Increment Active Sync Count ────────────────────────
        window._activeSyncCount++;

        // Non-intrusive toast informing user of background sync
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Booking Queued',
                text: 'Saving to database in background...',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        // ── 5. Background Sync to Supabase ─────────────────────────────────────
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (response) {
            var contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                return response.json().then(function (data) {
                    return { ok: response.ok, status: response.status, data: data };
                });
            } else {
                return { ok: false, status: response.status, data: { message: 'Server responded with status ' + response.status } };
            }
        })
        .then(function (result) {
            window._activeSyncCount = Math.max(0, window._activeSyncCount - 1);

            if (result.ok && result.data && result.data.success) {
                // ── SUCCESS: Confirm Row & Apply Final Server Data ─────────────
                var optRow = document.getElementById(tempRowId);
                if (optRow) {
                    optRow.classList.remove('table-warning-subtle');
                    var syncCell = optRow.querySelector('.cell-recorded-sync');
                    if (syncCell) {
                        syncCell.innerHTML = escapeHtml(result.data.history.recorded_on);
                    }
                }

                // Update Eligibility Matrix with exact server-verified calculations
                if (matrixRow && result.data.updated_status) {
                    var upd = result.data.updated_status;
                    matrixRow.querySelector('.cell-last-booked').textContent = upd.last_booked_date;
                    matrixRow.querySelector('.cell-next-eligible').textContent = upd.next_eligible_date;
                    matrixRow.querySelector('.cell-status').innerHTML = '<span class="badge ' + upd.status_class + '">' + upd.status_label + '</span>';
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Booking confirmed and recorded in database.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            } else {
                // ── ERROR / REJECTION: Trigger Safeguard 3 (Auto-Rollback) ──────
                var errorMsg = 'Failed to save booking.';
                if (result.status === 422 && result.data && result.data.errors) {
                    errorMsg = Object.values(result.data.errors).flat().join('<br>');
                } else if (result.data && result.data.message) {
                    errorMsg = result.data.message;
                }

                rollbackBooking(tempRowId, matrixRow, rowSnapshot, sevaId, bookingDateStr, remarks, errorMsg);
            }
        })
        .catch(function () {
            window._activeSyncCount = Math.max(0, window._activeSyncCount - 1);
            rollbackBooking(tempRowId, matrixRow, rowSnapshot, sevaId, bookingDateStr, remarks, 'Network connection dropped. Could not reach server.');
        });
    });

    function rollbackBooking(tempRowId, matrixRow, rowSnapshot, sevaId, bookingDateStr, remarks, errorMsg) {
        // 1. Remove optimistic row from history table
        var optRow = document.getElementById(tempRowId);
        if (optRow) {
            optRow.remove();
        }

        // Check if table is now empty
        var tbody = document.getElementById('bookingHistoryBody');
        var table = document.getElementById('bookingHistoryTable');
        var noHistory = document.getElementById('noHistoryMsg');
        if (tbody && tbody.children.length === 0) {
            if (table) table.style.display = 'none';
            if (noHistory) noHistory.style.display = '';
        }

        // 2. Revert eligibility matrix row to snapshot
        if (matrixRow && rowSnapshot) {
            matrixRow.querySelector('.cell-last-booked').innerHTML = rowSnapshot.lastBooked;
            matrixRow.querySelector('.cell-next-eligible').innerHTML = rowSnapshot.nextEligible;
            matrixRow.querySelector('.cell-status').innerHTML = rowSnapshot.statusBadge;
        }

        // 3. Save backup in sessionStorage so data is never lost
        try {
            sessionStorage.setItem('failed_booking_backup', JSON.stringify({
                seva_type_id: sevaId,
                booking_date: bookingDateStr,
                remarks: remarks,
                failed_at: new Date().toISOString()
            }));
        } catch(e) {}

        // 4. Alert operator and provide 1-click reopen to edit/retry
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Booking Not Saved',
                html: '<div class="text-danger mb-2"><b>' + errorMsg + '</b></div><small class="text-muted">The database could not accept this booking. Table state has been safely reverted.</small>',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-redo me-1"></i> Reopen & Edit Form',
                cancelButtonText: 'Dismiss'
            }).then(function (res) {
                if (res.isConfirmed) {
                    form.querySelector('select[name="seva_type_id"]').value = sevaId;
                    form.querySelector('input[name="booking_date"]').value = bookingDateStr;
                    form.querySelector('textarea[name="remarks"]').value = remarks;
                    modalInstance.show();
                }
            });
        } else {
            alert('Booking failed: ' + errorMsg);
        }
    }
});
</script>
@endpush
