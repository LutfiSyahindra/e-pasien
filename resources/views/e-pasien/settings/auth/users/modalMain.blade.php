<div class="modal fade access-crud-modal" id="usersModal" tabindex="-1" aria-labelledby="usersModalLabel"
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
                            <small>Account profile</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name</label>
                            <div class="access-input-icon">
                                <i class="bi bi-person"></i>
                                <input id="name" class="form-control" name="name" type="text">
                            </div>
                            <div class="invalid-feedback" id="error-name"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <div class="access-input-icon">
                                <i class="bi bi-envelope"></i>
                                <input id="email" class="form-control" name="email" type="email">
                            </div>
                            <div class="invalid-feedback" id="error-email"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <div class="access-input-icon">
                                <i class="bi bi-lock"></i>
                                <input id="password" class="form-control" name="password" type="password">
                            </div>
                            <div class="invalid-feedback" id="error-password"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="access-input-icon">
                                <i class="bi bi-shield-check"></i>
                                <input id="confirm_password" class="form-control" name="password_confirmation"
                                    type="password">
                            </div>
                            <div class="invalid-feedback" id="error-password_confirmation"></div>
                        </div>
                    </div>
                    <input id="userId" class="form-control" name="userId" type="hidden">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-access-muted" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                        <span>Close</span>
                    </button>
                    <button type="submit" id="submitForm" class="btn-access-primary">
                        <i class="bi bi-check2"></i>
                        <span>Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
