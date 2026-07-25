<div class="modal fade access-crud-modal auth-premium-modal auth-users-modal" id="assignRolesModal" tabindex="-1"
    aria-labelledby="assignRolesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="assignRolesForm" class="access-form">
                @csrf
                <div class="modal-header">
                    <div class="access-modal-title">
                        <span class="access-modal-mark"><i class="bi bi-person-badge"></i></span>
                        <div>
                            <h5 class="modal-title" id="assignRolesModalLabel"></h5>
                            <small>Pemetaan akses pengguna</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="auth-modal-context">
                        <span><i class="bi bi-diagram-3"></i></span>
                        <div>
                            <strong>Role pengguna</strong>
                            <small>Pilih satu atau beberapa role untuk akun ini.</small>
                        </div>
                    </div>
                    <div class="auth-field-heading">
                        <label class="form-label" for="rolesSelect">Roles</label>
                        <span id="selectedRolesCount" class="auth-selection-count">0 dipilih</span>
                    </div>
                    <div class="auth-select-shell">
                        <select name="roles_id[]" id="rolesSelect" class="js-example-basic-multiple form-select"
                            multiple="multiple" data-width="100%"></select>
                    </div>
                    <div class="invalid-feedback" id="error-roles_id"></div>
                    <input id="userssId" class="form-control" name="userssId" type="hidden">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-access-muted" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                        <span>Batal</span>
                    </button>
                    <button type="submit" id="assignRoles" class="btn-access-primary">
                        <i class="bi bi-check2"></i>
                        <span>Simpan Role</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
