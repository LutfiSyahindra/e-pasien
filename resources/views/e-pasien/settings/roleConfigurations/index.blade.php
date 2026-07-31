@extends("template.epasien.appPasien")

@section("title", "Konfigurasi Roles | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/auth-premium.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/css/role-configurations.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $registrationConfiguredIds = $roles
            ->where("registration_enabled", true)
            ->pluck("id")
            ->map(fn ($roleId) => (int) $roleId)
            ->values();
        $emailOnboardingConfiguredIds = $roles
            ->where("email_onboarding_enabled", true)
            ->pluck("id")
            ->map(fn ($roleId) => (int) $roleId)
            ->values();
        $selectedRegistrationIds = collect(old("registration_role_ids", $registrationConfiguredIds->all()))
            ->map(fn ($roleId) => (int) $roleId)
            ->values()
            ->all();
        $selectedEmailOnboardingIds = collect(old("email_onboarding_role_ids", $emailOnboardingConfiguredIds->all()))
            ->map(fn ($roleId) => (int) $roleId)
            ->values()
            ->all();
    @endphp

    <div class="access-page auth-premium-page auth-role-configurations-page">
        <div class="access-breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right"></i>
            <span>Pengaturan Akses</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Konfigurasi Roles</span>
        </div>

        <div class="auth-suite-bar">
            <div class="auth-suite-identity">
                <span class="auth-suite-mark"><i class="bi bi-shield-check"></i></span>
                <span>
                    <strong>Access Suite</strong>
                    <small>Identity & authorization</small>
                </span>
            </div>
            <nav class="auth-suite-tabs" aria-label="Navigasi pengaturan akses">
                <a class="auth-suite-tab" href="{{ route("users.users") }}">
                    <i class="bi bi-people"></i><span>Users</span>
                </a>
                <a class="auth-suite-tab" href="{{ route("roles.roles") }}">
                    <i class="bi bi-shield-lock"></i><span>Roles</span>
                </a>
                <a class="auth-suite-tab" href="{{ route("permissions.permissions") }}">
                    <i class="bi bi-key"></i><span>Permissions</span>
                </a>
                <a class="auth-suite-tab active" href="{{ route("roleConfiguration.index") }}"
                    aria-current="page">
                    <i class="bi bi-sliders"></i><span>Konfigurasi Roles</span>
                </a>
            </nav>
        </div>

        <div class="access-page-header">
            <div class="access-page-heading">
                <span class="access-page-icon"><i class="bi bi-sliders"></i></span>
                <div>
                    <span class="access-eyebrow">Role Feature Governance</span>
                    <h1>Konfigurasi Roles</h1>
                    <p>Atur fitur aplikasi yang aktif untuk setiap role dari satu tempat.</p>
                </div>
            </div>
            <div class="auth-header-actions role-config-header-actions">
                <span class="role-config-live-state">
                    <span class="role-config-live-dot"></span>
                    <strong>2</strong>
                    <span>fitur tersedia</span>
                </span>
                <a href="{{ route("roles.roles") }}" class="role-config-manage-link">
                    <i class="bi bi-gear"></i>
                    <span>Kelola Role</span>
                </a>
            </div>
        </div>

        @if (session("status"))
            <div class="role-config-alert-success" role="status">
                <span><i class="bi bi-check2"></i></span>
                <div>
                    <strong>Konfigurasi tersimpan</strong>
                    <small>{{ session("status") }}</small>
                </div>
            </div>
        @endif

        <div class="row g-3 access-stats role-config-stats">
            <div class="col-12 col-md-4">
                <div class="access-stat-card tone-indigo">
                    <span class="access-stat-icon purple"><i class="bi bi-collection"></i></span>
                    <span class="access-stat-copy">
                        <small>Role Tersedia</small>
                        <strong>{{ $roles->count() }}</strong>
                        <em>Guard aplikasi web</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="access-stat-card tone-green">
                    <span class="access-stat-icon green"><i class="bi bi-heart-pulse"></i></span>
                    <span class="access-stat-copy">
                        <small>Pendaftaran BPJS</small>
                        <strong id="registrationRoleCount">{{ $registrationRoleCount }}</strong>
                        <em><span id="registrationUserCount">{{ $registrationUserCount }}</span> pengguna tercakup</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="access-stat-card tone-cyan">
                    <span class="access-stat-icon cyan"><i class="bi bi-envelope-check"></i></span>
                    <span class="access-stat-copy">
                        <small>Onboarding Email</small>
                        <strong id="emailOnboardingRoleCount">{{ $emailOnboardingRoleCount }}</strong>
                        <em><span id="emailOnboardingUserCount">{{ $emailOnboardingUserCount }}</span> pengguna tercakup</em>
                    </span>
                </div>
            </div>
        </div>

        <div class="role-config-feature-grid" aria-label="Fitur yang dapat dikonfigurasi">
            <article class="role-config-feature-card tone-bpjs">
                <span class="role-config-feature-icon"><i class="bi bi-heart-pulse"></i></span>
                <div>
                    <span class="role-config-feature-kicker">Akses Operasional</span>
                    <h2>Pendaftaran BPJS</h2>
                    <p>Memberi akses memilih pasien, mendaftarkan BPJS, melihat seluruh riwayat, dan mengirim data Antrol.</p>
                </div>
                <span class="role-config-feature-count">
                    <strong data-registration-summary>{{ $registrationRoleCount }}</strong>
                    <small>role aktif</small>
                </span>
            </article>

            <article class="role-config-feature-card tone-email">
                <span class="role-config-feature-icon"><i class="bi bi-envelope-check"></i></span>
                <div>
                    <span class="role-config-feature-kicker">Pengalaman Login</span>
                    <h2>Animasi Onboarding Email</h2>
                    <p>Mengarahkan pengguna yang masih memakai email sementara untuk langsung mengisi email pribadi setelah login.</p>
                </div>
                <span class="role-config-feature-count">
                    <strong data-email-summary>{{ $emailOnboardingRoleCount }}</strong>
                    <small>role aktif</small>
                </span>
            </article>
        </div>

        <section class="access-panel role-config-panel">
            <div class="access-panel-header">
                <div class="access-panel-title">
                    <span class="access-panel-kicker">
                        <i class="bi bi-grid-3x3-gap"></i> Configuration Matrix
                    </span>
                    <h2>Matriks Konfigurasi Role</h2>
                    <p>Aktifkan fitur yang diperlukan pada masing-masing role.</p>
                </div>
                <div class="role-config-selection-summary" aria-live="polite">
                    <span><i class="bi bi-toggles"></i></span>
                    <div>
                        <small>Konfigurasi aktif</small>
                        <strong><span id="activeConfigurationCount">{{ $registrationRoleCount + $emailOnboardingRoleCount }}</span> assignment</strong>
                    </div>
                </div>
            </div>

            <form id="roleConfigurationForm" method="POST"
                action="{{ route("roleConfiguration.update") }}"
                data-initial-registration="{{ $registrationConfiguredIds->sort()->implode(",") }}"
                data-initial-email="{{ $emailOnboardingConfiguredIds->sort()->implode(",") }}">
                @csrf
                @method("PUT")

                @if (
                    $errors->has("registration_role_ids") ||
                    $errors->has("registration_role_ids.*") ||
                    $errors->has("email_onboarding_role_ids") ||
                    $errors->has("email_onboarding_role_ids.*")
                )
                    <div class="role-config-alert-error" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>
                            {{ $errors->first("registration_role_ids") ?: $errors->first("registration_role_ids.*") ?: $errors->first("email_onboarding_role_ids") ?: $errors->first("email_onboarding_role_ids.*") }}
                        </span>
                    </div>
                @endif

                <div class="role-config-matrix-wrap">
                    <div class="role-config-matrix" role="table" aria-label="Konfigurasi fitur berdasarkan role">
                        <div class="role-config-matrix-header" role="row">
                            <span role="columnheader">Role</span>
                            <span role="columnheader">Pengguna</span>
                            <span role="columnheader">
                                <i class="bi bi-heart-pulse"></i>
                                Pendaftaran BPJS
                            </span>
                            <span role="columnheader">
                                <i class="bi bi-envelope-check"></i>
                                Animasi Email
                            </span>
                        </div>

                        @forelse ($roles as $role)
                            @php
                                $registrationSelected = in_array((int) $role->id, $selectedRegistrationIds, true);
                                $emailSelected = in_array((int) $role->id, $selectedEmailOnboardingIds, true);
                            @endphp
                            <div class="role-config-matrix-row" role="row">
                                <div class="role-config-role" role="cell">
                                    <span class="role-config-role-icon">
                                        <i class="bi bi-shield-check"></i>
                                    </span>
                                    <span>
                                        <strong>{{ $role->name }}</strong>
                                        <small>Guard {{ $role->guard_name }}</small>
                                    </span>
                                </div>

                                <div class="role-config-users" role="cell">
                                    <i class="bi bi-people"></i>
                                    <strong>{{ $role->users_count }}</strong>
                                    <span>pengguna</span>
                                </div>

                                <div class="role-config-cell" role="cell">
                                    <span class="role-config-mobile-label">Pendaftaran BPJS</span>
                                    <label class="role-config-toggle" for="registration-role-{{ $role->id }}">
                                        <input class="role-config-checkbox" type="checkbox"
                                            id="registration-role-{{ $role->id }}"
                                            name="registration_role_ids[]" value="{{ $role->id }}"
                                            data-feature="registration" data-users="{{ $role->users_count }}"
                                            @checked($registrationSelected)>
                                        <span class="role-config-switch" aria-hidden="true"><span></span></span>
                                        <span class="role-config-toggle-copy">
                                            <strong data-toggle-state>{{ $registrationSelected ? "Aktif" : "Nonaktif" }}</strong>
                                            <small>Akses operasional BPJS</small>
                                        </span>
                                    </label>
                                </div>

                                <div class="role-config-cell" role="cell">
                                    <span class="role-config-mobile-label">Animasi Onboarding Email</span>
                                    <label class="role-config-toggle" for="email-role-{{ $role->id }}">
                                        <input class="role-config-checkbox" type="checkbox"
                                            id="email-role-{{ $role->id }}"
                                            name="email_onboarding_role_ids[]" value="{{ $role->id }}"
                                            data-feature="email" data-users="{{ $role->users_count }}"
                                            @checked($emailSelected)>
                                        <span class="role-config-switch" aria-hidden="true"><span></span></span>
                                        <span class="role-config-toggle-copy">
                                            <strong data-toggle-state>{{ $emailSelected ? "Aktif" : "Nonaktif" }}</strong>
                                            <small>Panduan email saat login</small>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        @empty
                            <div class="role-config-empty">
                                <span><i class="bi bi-inbox"></i></span>
                                <strong>Belum ada role yang tersedia</strong>
                                <p>Buat role terlebih dahulu sebelum mengatur fitur aplikasi.</p>
                                <a href="{{ route("roles.roles") }}">
                                    <i class="bi bi-plus-lg"></i>
                                    Buka Manajemen Role
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="role-config-action-bar">
                    <div class="role-config-change-state">
                        <span id="roleConfigurationChangeIcon"><i class="bi bi-check-circle"></i></span>
                        <span>
                            <strong id="roleConfigurationChangeTitle">Konfigurasi sudah tersimpan</strong>
                            <small id="roleConfigurationChangeHint">Ubah sakelar untuk mengaktifkan tombol simpan.</small>
                        </span>
                    </div>
                    <div class="role-config-form-actions">
                        <button type="button" id="resetRoleConfiguration"
                            class="role-config-reset-button d-none">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Batalkan Perubahan</span>
                        </button>
                        <button type="submit" id="saveRoleConfiguration" class="btn-access-primary">
                            <i class="bi bi-check2-circle"></i>
                            <span>Simpan Konfigurasi</span>
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endsection

@push("script")
    <script>
        (() => {
            const form = document.getElementById("roleConfigurationForm");

            if (!form) {
                return;
            }

            const inputs = [...form.querySelectorAll(".role-config-checkbox")];
            const resetButton = document.getElementById("resetRoleConfiguration");
            const saveButton = document.getElementById("saveRoleConfiguration");
            const saveButtonLabel = saveButton?.querySelector("span");
            const changeIcon = document.getElementById("roleConfigurationChangeIcon");
            const changeTitle = document.getElementById("roleConfigurationChangeTitle");
            const changeHint = document.getElementById("roleConfigurationChangeHint");
            const initial = {
                registration: (form.dataset.initialRegistration || "").split(",").filter(Boolean).sort(),
                email: (form.dataset.initialEmail || "").split(",").filter(Boolean).sort(),
            };

            const featureInputs = (feature) => inputs.filter((input) => input.dataset.feature === feature);
            const selectedIds = (feature) => featureInputs(feature)
                .filter((input) => input.checked)
                .map((input) => input.value)
                .sort();

            const refreshToggle = (input) => {
                const toggle = input.closest(".role-config-toggle");
                const state = toggle?.querySelector("[data-toggle-state]");

                toggle?.classList.toggle("is-active", input.checked);

                if (state) {
                    state.textContent = input.checked ? "Aktif" : "Nonaktif";
                }
            };

            const countUsers = (feature) => featureInputs(feature)
                .filter((input) => input.checked)
                .reduce((total, input) => total + Number(input.dataset.users || 0), 0);

            const refreshSummary = () => {
                const registrationIds = selectedIds("registration");
                const emailIds = selectedIds("email");
                const registrationChanged = registrationIds.join(",") !== initial.registration.join(",");
                const emailChanged = emailIds.join(",") !== initial.email.join(",");
                const hasChanges = registrationChanged || emailChanged;
                const assignmentCount = registrationIds.length + emailIds.length;

                inputs.forEach(refreshToggle);

                document.getElementById("registrationRoleCount").textContent = registrationIds.length;
                document.getElementById("emailOnboardingRoleCount").textContent = emailIds.length;
                document.getElementById("registrationUserCount").textContent =
                    countUsers("registration").toLocaleString("id-ID");
                document.getElementById("emailOnboardingUserCount").textContent =
                    countUsers("email").toLocaleString("id-ID");
                document.getElementById("activeConfigurationCount").textContent = assignmentCount;
                document.querySelector("[data-registration-summary]").textContent = registrationIds.length;
                document.querySelector("[data-email-summary]").textContent = emailIds.length;

                saveButton.disabled = !hasChanges || inputs.length === 0;
                resetButton?.classList.toggle("d-none", !hasChanges);
                changeIcon?.classList.toggle("is-dirty", hasChanges);

                if (changeIcon) {
                    changeIcon.innerHTML = hasChanges
                        ? '<i class="bi bi-exclamation-circle"></i>'
                        : '<i class="bi bi-check-circle"></i>';
                }

                changeTitle.textContent = hasChanges
                    ? "Ada perubahan yang belum disimpan"
                    : "Konfigurasi sudah tersimpan";
                changeHint.textContent = hasChanges
                    ? `${registrationIds.length} role BPJS dan ${emailIds.length} role onboarding email akan disimpan.`
                    : "Ubah sakelar untuk mengaktifkan tombol simpan.";
            };

            inputs.forEach((input) => input.addEventListener("change", refreshSummary));

            resetButton?.addEventListener("click", () => {
                inputs.forEach((input) => {
                    input.checked = initial[input.dataset.feature].includes(input.value);
                });
                refreshSummary();
            });

            form.addEventListener("submit", () => {
                saveButton.disabled = true;
                saveButton.classList.add("is-loading");

                if (saveButtonLabel) {
                    saveButtonLabel.textContent = "Menyimpan...";
                }
            });

            refreshSummary();
        })();
    </script>
@endpush
