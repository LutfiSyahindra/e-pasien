<div class="modal fade access-crud-modal" id="permissionsModal" tabindex="-1"
    aria-labelledby="permissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="permissionForm" class="access-form">
                @csrf
                <div class="modal-header">
                    <div class="access-modal-title">
                        <span class="access-modal-mark"><i class="bi bi-key"></i></span>
                        <div>
                            <h5 class="modal-title" id="permissionsModalLabel"></h5>
                            <small>Permission identity</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="name" class="form-label">Permission Name</label>
                    <div class="access-input-icon">
                        <i class="bi bi-key"></i>
                        <input id="name" class="form-control" name="name" type="text">
                    </div>
                    <div class="invalid-feedback" id="error-name"></div>
                    <input id="permissionId" class="form-control" name="permissionId" type="hidden">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-access-muted" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                        <span>Close</span>
                    </button>
                    <button type="submit" id="submitPermissionForm" class="btn-access-primary">
                        <i class="bi bi-check2"></i>
                        <span>Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
