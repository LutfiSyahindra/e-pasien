@extends("template.epasien.appPasien")

@section("title", "Role Pendaftaran BPJS | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/auth-premium.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/css/registration-roles.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $configuredRoleIds = $roles
            ->where("registration_enabled", true)
            ->pluck("id")
            ->map(fn ($roleId) => (int) $roleId)
            ->values();
        $selectedRoleIds = collect(old("role_ids", $configuredRoleIds->all()))
            ->map(fn ($roleId) => (int) $roleId)
            ->values()
            ->all();
    @endphp

    <div class="access-page auth-premium-page auth-registration-roles-page">
        <div class="access-breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right"></i>
            <span>Pengaturan Akses</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Role Pendaftaran BPJS</span>
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
                <a class="auth-suite-tab active" href="{{ route("registrationRoleConfiguration.index") }}"
                    aria-current="page">
                    <i class="bi bi-person-check"></i><span>Role Pendaftaran</span>
                </a>
            </nav>
        </div>

        <div class="access-page-header">
            <div class="access-page-heading">
                <span class="access-page-icon"><i class="bi bi-person-check"></i></span>
                <div>
                    <span class="access-eyebrow">Pendaftaran BPJS</span>
                    <h1>Konfigurasi Role Pendaftaran</h1>
                    <p>Tentukan kelompok petugas yang dapat mendaftarkan pasien BPJS dan mengakses riwayat pendaftaran.</p>
                </div>
            </div>
            <div class="auth-header-actions registration-header-actions">
                <span class="registration-live-state">
                    <span class="registration-live-dot"></span>
                    <strong id="headerActiveRoleCount">{{ $configuredCount }}</strong>
                    <span>role aktif</span>
                </span>
                <a href="{{ route("roles.roles") }}" class="registration-manage-link">
                    <i class="bi bi-gear"></i>
                    <span>Kelola Role</span>
                </a>
            </div>
        </div>

        @if (session("status"))
            <div class="registration-alert-success" role="status">
                <span><i class="bi bi-check2"></i></span>
                <div>
                    <strong>Konfigurasi tersimpan</strong>
                    <small>{{ session("status") }}</small>
                </div>
            </div>
        @endif

        <div class="row g-3 access-stats registration-stats">
            <div class="col-6 col-lg-4">
                <div class="access-stat-card tone-indigo">
                    <span class="access-stat-icon purple"><i class="bi bi-collection"></i></span>
                    <span class="access-stat-copy">
                        <small>Role Tersedia</small>
                        <strong>{{ $roles->count() }}</strong>
                        <em>Guard aplikasi web</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="access-stat-card tone-green">
                    <span class="access-stat-icon green"><i class="bi bi-shield-check"></i></span>
                    <span class="access-stat-copy">
                        <small>Role Aktif</small>
                        <strong id="activeRoleCount">{{ $configuredCount }}</strong>
                        <em>Dapat mendaftarkan BPJS</em>
                    </span>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="access-stat-card tone-cyan">
                    <span class="access-stat-icon cyan"><i class="bi bi-people"></i></span>
                    <span class="access-stat-copy">
                        <small>Pengguna Tercakup</small>
                        <strong id="coveredUserCount">{{ $configuredUsersCount }}</strong>
                        <em>Berdasarkan assignment role</em>
                    </span>
                </div>
            </div>
        </div>

        <section class="access-panel registration-config-panel">
            <div class="access-panel-header">
                <div class="access-panel-title">
                    <span class="access-panel-kicker">
                        <i class="bi bi-sliders"></i> Registration Access
                    </span>
                    <h2>Pilih Role Petugas</h2>
                    <p>Aktifkan role yang berhak menjalankan alur pendaftaran pasien BPJS.</p>
                </div>
                <div class="registration-selection-summary" aria-live="polite">
                    <span class="registration-summary-icon"><i class="bi bi-check2-square"></i></span>
                    <span>
                        <small>Role terpilih</small>
                        <strong><span id="selectedRoleCount">{{ $configuredCount }}</span> dari {{ $roles->count() }}</strong>
                    </span>
                </div>
            </div>

            <form id="registrationRoleForm" method="POST"
                action="{{ route("registrationRoleConfiguration.update") }}"
                data-initial-roles="{{ $configuredRoleIds->sort()->implode(",") }}">
                @csrf
                @method("PUT")

                <div class="registration-panel-body">
                    <div class="registration-scope-card">
                        <span class="registration-scope-mark"><i class="bi bi-info-circle"></i></span>
                        <div class="registration-scope-copy">
                            <strong>Cakupan akses yang diberikan</strong>
                            <p>Setiap pengguna dengan role aktif memperoleh akses operasional berikut.</p>
                            <div class="registration-capabilities" aria-label="Cakupan akses role pendaftaran">
                                <span><i class="bi bi-person-vcard"></i> Pilih pasien</span>
                                <span><i class="bi bi-heart-pulse"></i> Penjamin BPJS</span>
                                <span><i class="bi bi-clock-history"></i> Seluruh riwayat</span>
                                <span><i class="bi bi-database-check"></i> Audit tercatat</span>
                            </div>
                        </div>
                    </div>

                    @if ($errors->has("role_ids") || $errors->has("role_ids.*"))
                        <div class="registration-alert-error" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>{{ $errors->first("role_ids") ?: $errors->first("role_ids.*") }}</span>
                        </div>
                    @endif

                    <div class="registration-role-list-heading">
                        <div>
                            <h3>Role yang tersedia</h3>
                            <p>Klik kartu atau sakelar untuk mengubah status akses.</p>
                        </div>
                        <span class="registration-active-legend">
                            <i></i>
                            Aktif untuk pendaftaran
                        </span>
                    </div>

                    <div class="registration-role-grid">
                        @forelse ($roles as $role)
                            @php
                                $isSelected = in_array((int) $role->id, $selectedRoleIds, true);
                            @endphp
                            <label class="registration-role-card" for="registration-role-{{ $role->id }}">
                                <input class="registration-role-checkbox" type="checkbox"
                                    id="registration-role-{{ $role->id }}" name="role_ids[]"
                                    value="{{ $role->id }}" data-users="{{ $role->users_count }}"
                                    @checked($isSelected)>

                                <span class="registration-role-card-content">
                                    <span class="registration-role-card-top">
                                        <span class="registration-role-icon">
                                            <i class="bi bi-shield-check"></i>
                                        </span>
                                        <span class="registration-role-control">
                                            <span @class(["registration-role-state", "is-active" => $isSelected])>
                                                {{ $isSelected ? "Aktif" : "Nonaktif" }}
                                            </span>
                                            <span class="registration-switch" aria-hidden="true">
                                                <span></span>
                                            </span>
                                        </span>
                                    </span>

                                    <span class="registration-role-name">{{ $role->name }}</span>
                                    <span class="registration-role-description">
                                        Akses operasional pendaftaran pasien BPJS
                                    </span>

                                    <span class="registration-role-meta">
                                        <span>
                                            <i class="bi bi-people"></i>
                                            {{ $role->users_count }} pengguna
                                        </span>
                                        <span>
                                            <i class="bi bi-shield"></i>
                                            Guard {{ $role->guard_name }}
                                        </span>
                                    </span>

                                    <span class="registration-role-access">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span class="registration-role-access-text">
                                            {{ $isSelected ? "Akses pendaftaran diberikan" : "Akses pendaftaran belum diberikan" }}
                                        </span>
                                    </span>
                                </span>
                            </label>
                        @empty
                            <div class="registration-empty-state">
                                <span><i class="bi bi-inbox"></i></span>
                                <strong>Belum ada role yang tersedia</strong>
                                <p>Buat role terlebih dahulu sebelum mengatur akses pendaftaran BPJS.</p>
                                <a href="{{ route("roles.roles") }}">
                                    <i class="bi bi-plus-lg"></i>
                                    Buka Manajemen Role
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="registration-action-bar">
                    <div class="registration-change-state">
                        <span id="registrationChangeIcon"><i class="bi bi-check-circle"></i></span>
                        <span>
                            <strong id="registrationChangeTitle">Konfigurasi sudah tersimpan</strong>
                            <small id="registrationChangeHint">Ubah pilihan role untuk mengaktifkan tombol simpan.</small>
                        </span>
                    </div>
                    <div class="registration-form-actions">
                        <button type="button" id="resetRegistrationRoles"
                            class="registration-reset-button d-none">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Batalkan Perubahan</span>
                        </button>
                        <button type="submit" id="saveRegistrationRoles" class="btn-access-primary">
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
            const form = document.getElementById("registrationRoleForm");

            if (!form) {
                return;
            }

            const inputs = [...form.querySelectorAll(".registration-role-checkbox")];
            const selectedRoleCount = document.getElementById("selectedRoleCount");
            const activeRoleCount = document.getElementById("activeRoleCount");
            const headerActiveRoleCount = document.getElementById("headerActiveRoleCount");
            const coveredUserCount = document.getElementById("coveredUserCount");
            const changeIcon = document.getElementById("registrationChangeIcon");
            const changeTitle = document.getElementById("registrationChangeTitle");
            const changeHint = document.getElementById("registrationChangeHint");
            const resetButton = document.getElementById("resetRegistrationRoles");
            const saveButton = document.getElementById("saveRegistrationRoles");
            const saveButtonLabel = saveButton?.querySelector("span");
            const initialRoleIds = (form.dataset.initialRoles || "")
                .split(",")
                .filter(Boolean)
                .sort();

            const currentRoleIds = () => inputs
                .filter((input) => input.checked)
                .map((input) => input.value)
                .sort();

            const refreshRoleCard = (input) => {
                const card = input.nextElementSibling;
                const state = card?.querySelector(".registration-role-state");
                const accessText = card?.querySelector(".registration-role-access-text");

                state?.classList.toggle("is-active", input.checked);

                if (state) {
                    state.textContent = input.checked ? "Aktif" : "Nonaktif";
                }

                if (accessText) {
                    accessText.textContent = input.checked
                        ? "Akses pendaftaran diberikan"
                        : "Akses pendaftaran belum diberikan";
                }
            };

            const refreshSummary = () => {
                const selectedInputs = inputs.filter((input) => input.checked);
                const selectedCount = selectedInputs.length;
                const userCount = selectedInputs.reduce(
                    (total, input) => total + Number(input.dataset.users || 0),
                    0,
                );
                const hasChanges = currentRoleIds().join(",") !== initialRoleIds.join(",");

                selectedRoleCount.textContent = selectedCount;
                activeRoleCount.textContent = selectedCount;
                headerActiveRoleCount.textContent = selectedCount;
                coveredUserCount.textContent = userCount.toLocaleString("id-ID");

                inputs.forEach(refreshRoleCard);
                saveButton.disabled = !hasChanges || inputs.length === 0;
                resetButton.classList.toggle("d-none", !hasChanges);
                changeIcon.classList.toggle("is-dirty", hasChanges);
                changeIcon.innerHTML = hasChanges
                    ? '<i class="bi bi-exclamation-circle"></i>'
                    : '<i class="bi bi-check-circle"></i>';
                changeTitle.textContent = hasChanges
                    ? "Ada perubahan yang belum disimpan"
                    : "Konfigurasi sudah tersimpan";
                changeHint.textContent = hasChanges
                    ? `${selectedCount} role akan memperoleh akses pendaftaran BPJS.`
                    : "Ubah pilihan role untuk mengaktifkan tombol simpan.";
            };

            inputs.forEach((input) => input.addEventListener("change", refreshSummary));

            resetButton?.addEventListener("click", () => {
                inputs.forEach((input) => {
                    input.checked = initialRoleIds.includes(input.value);
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
