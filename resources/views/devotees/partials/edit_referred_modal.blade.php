<!-- Edit Referred / Agent Name Modal -->
<div class="modal fade" id="editReferredModal" tabindex="-1" aria-labelledby="editReferredModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editReferredForm">
                @csrf
                <input type="hidden" name="devotee_id" id="edit_referred_id">
                <div class="modal-header" style="background:var(--garuda-gold); color:white;">
                    <h5 class="modal-title" id="editReferredModalLabel">Edit Referred / Agent Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Devotee</label>
                        <input type="text" class="form-control" id="edit_referred_devotee" readonly>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Referred / Agent Name</label>
                        <input type="text" class="form-control" name="referred_name" id="edit_referred_name"
                               list="referredAgentsList" autocomplete="off">
                        @if(isset($agents) && $agents->count())
                            <datalist id="referredAgentsList">
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->name }}"></option>
                                @endforeach
                            </datalist>
                        @endif
                    </div>
                    <small class="text-muted">Leave blank to make this devotee standalone. If the agent name is new, it is created automatically.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--temple-maroon); border:none;"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $(document).on('click', '.edit-referred-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var referred = $(this).data('referred');

        $('#edit_referred_id').val(id);
        $('#edit_referred_devotee').val(name);
        $('#edit_referred_name').val(referred || '');

        var modal = new bootstrap.Modal(document.getElementById('editReferredModal'));
        modal.show();
    });

    $('#editReferredForm').on('submit', function(e) {
        e.preventDefault();

        var devoteeId = $('#edit_referred_id').val();
        var formData = $(this).serialize() + '&_method=PATCH';

        $.ajax({
            url: '/devotees/' + devoteeId + '/referred',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#editReferredModal').modal('hide');
                    Swal.fire('Success', response.message, 'success');
                    if ($.fn.DataTable.isDataTable('#devoteesTable')) {
                        $('#devoteesTable').DataTable().ajax.reload(null, false);
                    }
                } else {
                    Swal.fire('Error', response.message || 'Failed to update.', 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON ? (xhr.responseJSON.message || 'An error occurred.') : 'An error occurred.', 'error');
            }
        });
    });
});
</script>
@endpush