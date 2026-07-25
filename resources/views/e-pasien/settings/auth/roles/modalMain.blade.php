<div class="modal fade access-crud-modal auth-premium-modal auth-roles-modal" id="rolesModal" tabindex="-1"
    aria-labelledby="rolesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="roleForm" class="access-form">
                @csrf
                <div class="modal-header">
                    <div class="access-modal-title">
                        <span class="access-modal-mark"><i class="bi bi-shield-lock"></i></span>
                        <div>
                            <h5 class="modal-title" id="rolesModalLabel"></h5>
                            <small>Identitas kelompok akses</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="auth-modal-context">
                        <span><i class="bi bi-shield-lock"></i></span>
                        <div>
                            <strong>Identitas role</strong>
                            <small>Nama role yang akan digunakan dalam pengaturan akses.</small>
                        </div>
                    </div>
                    <label for="name" class="form-label">Nama Role</label>
                    <div class="access-input-icon">
                        <i class="bi bi-shield"></i>
                        <input id="name" class="form-control" name="name" type="text"
                            placeholder="Contoh: Admin Klinik" autocomplete="off">
                    </div>
                    <div class="invalid-feedback" id="error-name"></div>
                    <input id="roleId" class="form-control" name="roleId" type="hidden">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-access-muted" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                        <span>Batal</span>
                    </button>
                    <button type="submit" id="submitRoleForm" class="btn-access-primary">
                        <i class="bi bi-check2"></i>
                        <span>Simpan Role</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
