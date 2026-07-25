<div class="modal fade access-crud-modal auth-premium-modal auth-roles-modal" id="assignPermissionsModal"
    tabindex="-1"
    aria-labelledby="assignPermissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="assignPermissionsForm" class="access-form">
                @csrf
                <div class="modal-header">
                    <div class="access-modal-title">
                        <span class="access-modal-mark"><i class="bi bi-key"></i></span>
                        <div>
                            <h5 class="modal-title" id="assignPermissionsModalLabel"></h5>
                            <small>Pemetaan kapabilitas role</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="auth-modal-context">
                        <span><i class="bi bi-key"></i></span>
                        <div>
                            <strong>Permission role</strong>
                            <small>Pilih kapabilitas yang tersedia untuk role ini.</small>
                        </div>
                    </div>
                    <div class="auth-field-heading">
                        <label class="form-label" for="permissionsSelect">Permissions</label>
                        <span id="selectedPermissionsCount" class="auth-selection-count">0 dipilih</span>
                    </div>
                    <div class="auth-select-shell">
                        <select name="permissions_id[]" id="permissionsSelect"
                            class="js-example-basic-multiple form-select" multiple="multiple"
                            data-width="100%"></select>
                    </div>
                    <div class="invalid-feedback" id="error-permissions_id"></div>
                    <input id="assignRoleId" class="form-control" name="roleId" type="hidden">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-access-muted" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                        <span>Batal</span>
                    </button>
                    <button type="submit" id="assignPermissionsButton" class="btn-access-primary">
                        <i class="bi bi-check2"></i>
                        <span>Simpan Permission</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
