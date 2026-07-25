<div class="modal fade access-crud-modal auth-premium-modal auth-users-modal" id="usersModal" tabindex="-1"
    aria-labelledby="usersModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="signupForm" class="access-form">
                @csrf
                <div class="modal-header">
                    <div class="access-modal-title">
                        <span class="access-modal-mark"><i class="bi bi-person-plus"></i></span>
                        <div>
                            <h5 class="modal-title" id="usersModalLabel"></h5>
                            <small>Identitas dan kredensial akun</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="auth-modal-context">
                        <span><i class="bi bi-person-vcard"></i></span>
                        <div>
                            <strong>Profil pengguna</strong>
                            <small>Informasi utama untuk autentikasi pengguna.</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <div class="access-input-icon">
                                <i class="bi bi-person"></i>
                                <input id="name" class="form-control" name="name" type="text"
                                    placeholder="Nama pengguna" autocomplete="name">
                            </div>
                            <div class="invalid-feedback" id="error-name"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <div class="access-input-icon">
                                <i class="bi bi-envelope"></i>
                                <input id="email" class="form-control" name="email" type="email"
                                    placeholder="nama@domain.com" autocomplete="email">
                            </div>
                            <div class="invalid-feedback" id="error-email"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <div class="access-input-icon">
                                <i class="bi bi-lock"></i>
                                <input id="password" class="form-control" name="password" type="password"
                                    placeholder="Minimal 6 karakter" autocomplete="new-password">
                            </div>
                            <div class="invalid-feedback" id="error-password"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                            <div class="access-input-icon">
                                <i class="bi bi-shield-check"></i>
                                <input id="confirm_password" class="form-control" name="password_confirmation"
                                    type="password" placeholder="Ulangi password" autocomplete="new-password">
                            </div>
                            <div class="invalid-feedback" id="error-password_confirmation"></div>
                        </div>
                    </div>
                    <input id="userId" class="form-control" name="userId" type="hidden">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-access-muted" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                        <span>Batal</span>
                    </button>
                    <button type="submit" id="submitForm" class="btn-access-primary">
                        <i class="bi bi-check2"></i>
                        <span>Simpan User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
