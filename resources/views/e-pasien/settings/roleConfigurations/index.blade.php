@extends("template.epasien.appPasien")

@section("title", "Konfigurasi Roles | E-Pasien")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/auth-premium.css") }}" rel="stylesheet" />
    <link href="{{ versioned_asset("epasien/assets/css/role-configurations.css") }}" rel="stylesheet" />
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
        $promotionNotificationConfiguredIds = $roles
            ->where("promotion_notifications_enabled", true)
            ->pluck("id")
            ->map(fn ($roleId) => (int) $roleId)
            ->values();
        $doctorArrivalNotificationConfiguredIds = $roles
            ->where("doctor_arrival_notifications_enabled", true)
            ->pluck("id")
            ->map(fn ($roleId) => (int) $roleId)
            ->values();
        $promotionManagementConfiguredIds = $roles
            ->where("promotion_management_enabled", true)
            ->pluck("id")
            ->map(fn ($roleId) => (int) $roleId)
            ->values();
        $patientServiceConfiguredIds = $roles
            ->where("patient_service_enabled", true)
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
        $selectedPromotionNotificationIds = collect(old("promotion_notification_role_ids", $promotionNotificationConfiguredIds->all()))
            ->map(fn ($roleId) => (int) $roleId)
            ->values()
            ->all();
        $selectedDoctorArrivalNotificationIds = collect(old("doctor_arrival_notification_role_ids", $doctorArrivalNotificationConfiguredIds->all()))
            ->map(fn ($roleId) => (int) $roleId)
            ->values()
            ->all();
        $selectedPromotionManagementIds = collect(old("promotion_management_role_ids", $promotionManagementConfiguredIds->all()))
            ->map(fn ($roleId) => (int) $roleId)
            ->values()
            ->all();
        $selectedPatientServiceIds = collect(old("patient_service_role_ids", $patientServiceConfiguredIds->all()))
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
                    <strong>6</strong>
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
            <div class="col-12 col-md-6 col-xl-3">
                <div class="access-stat-card tone-indigo">
                    <span class="access-stat-icon purple"><i class="bi bi-collection"></i></span>
                    <span class="access-stat-copy">
                        <small>Role Tersedia</small>
                        <strong>{{ $roles->count() }}</strong>
                        <em>Guard aplikasi web</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-md-6 col-xl-3">
                <div class="access-stat-card tone-green">
                    <span class="access-stat-icon green"><i class="bi bi-heart-pulse"></i></span>
                    <span class="access-stat-copy">
                        <small>Pendaftaran BPJS</small>
                        <strong id="registrationRoleCount">{{ $registrationRoleCount }}</strong>
                        <em><span id="registrationUserCount">{{ $registrationUserCount }}</span> pengguna tercakup</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-md-6 col-xl-3">
                <div class="access-stat-card tone-cyan">
                    <span class="access-stat-icon cyan"><i class="bi bi-envelope-check"></i></span>
                    <span class="access-stat-copy">
                        <small>Onboarding Email</small>
                        <strong id="emailOnboardingRoleCount">{{ $emailOnboardingRoleCount }}</strong>
                        <em><span id="emailOnboardingUserCount">{{ $emailOnboardingUserCount }}</span> pengguna tercakup</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-md-6 col-xl-3">
                <div class="access-stat-card tone-orange">
                    <span class="access-stat-icon orange"><i class="bi bi-megaphone"></i></span>
                    <span class="access-stat-copy">
                        <small>Penerima Konten</small>
                        <strong id="promotionRecipientCount" data-saved-count="{{ $promotionNotificationRecipientCount }}">{{ $promotionNotificationRecipientCount }}</strong>
                        <em><span id="promotionDirectUserCount">{{ $promotionNotificationUserCount }}</span> user dipilih langsung</em>
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

            <article class="role-config-feature-card tone-promotion">
                <span class="role-config-feature-icon"><i class="bi bi-megaphone"></i></span>
                <div>
                    <span class="role-config-feature-kicker">Target Komunikasi</span>
                    <h2>Notifikasi Promosi &amp; Informasi</h2>
                    <p>Kirim promosi dan informasi hanya kepada role yang dipilih atau akun uji tertentu agar antrean tetap ringkas.</p>
                </div>
                <span class="role-config-feature-count">
                    <strong data-promotion-summary>{{ $promotionNotificationRoleCount }}</strong>
                    <small>role aktif</small>
                </span>
            </article>

            <article class="role-config-feature-card tone-doctor-arrival">
                <span class="role-config-feature-icon"><i class="bi bi-person-check-fill"></i></span>
                <div>
                    <span class="role-config-feature-kicker">Informasi Antrean</span>
                    <h2>Notifikasi Dokter Datang</h2>
                    <p>Pasien pada jadwal yang sama menerima notifikasi saat satu pasien memiliki minimal dua data pemeriksaan rawat jalan.</p>
                </div>
                <span class="role-config-feature-count">
                    <strong data-doctor-arrival-summary>{{ $doctorArrivalNotificationRoleCount }}</strong>
                    <small>role aktif</small>
                </span>
            </article>

            <article class="role-config-feature-card tone-promotion-management">
                <span class="role-config-feature-icon"><i class="bi bi-pencil-square"></i></span>
                <div>
                    <span class="role-config-feature-kicker">Hak Pengelolaan</span>
                    <h2>Pengelola Promosi &amp; Informasi</h2>
                    <p>Role terpilih dapat membuat, menjadwalkan, mengedit, mengarsipkan, menghapus, serta melihat daftar pembaca konten.</p>
                </div>
                <span class="role-config-feature-count">
                    <strong data-promotion-management-summary>{{ $promotionManagementRoleCount }}</strong>
                    <small>role aktif</small>
                </span>
            </article>

            <article class="role-config-feature-card tone-patient-service">
                <span class="role-config-feature-icon"><i class="bi bi-headset"></i></span>
                <div>
                    <span class="role-config-feature-kicker">Layanan Percakapan</span>
                    <h2>Admin Pasien Service</h2>
                    <p>Seluruh pengguna aktif dari role terpilih menjadi Tim Pasien Service serta dapat menerima notifikasi, membalas, dan menyelesaikan percakapan.</p>
                </div>
                <span class="role-config-feature-count">
                    <strong data-patient-service-summary>{{ $patientServiceRoleCount }}</strong>
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
                        <strong><span id="activeConfigurationCount">{{ $registrationRoleCount + $emailOnboardingRoleCount + $promotionNotificationRoleCount + $promotionNotificationUserCount + $doctorArrivalNotificationRoleCount + $promotionManagementRoleCount + $patientServiceRoleCount }}</span> assignment</strong>
                    </div>
                </div>
            </div>

            <form id="roleConfigurationForm" method="POST"
                action="{{ route("roleConfiguration.update") }}"
                data-initial-registration="{{ $registrationConfiguredIds->sort()->implode(",") }}"
                data-initial-email="{{ $emailOnboardingConfiguredIds->sort()->implode(",") }}"
                data-initial-promotion-roles="{{ $promotionNotificationConfiguredIds->sort()->implode(",") }}"
                data-initial-doctor-arrival="{{ $doctorArrivalNotificationConfiguredIds->sort()->implode(",") }}"
                data-initial-promotion-management="{{ $promotionManagementConfiguredIds->sort()->implode(",") }}"
                data-initial-patient-service="{{ $patientServiceConfiguredIds->sort()->implode(",") }}"
                data-initial-promotion-users="{{ $configuredPromotionUserIds->sort()->implode(",") }}">
                @csrf
                @method("PUT")

                @if (
                    $errors->has("registration_role_ids") ||
                    $errors->has("registration_role_ids.*") ||
                    $errors->has("email_onboarding_role_ids") ||
                    $errors->has("email_onboarding_role_ids.*") ||
                    $errors->has("promotion_notification_role_ids") ||
                    $errors->has("promotion_notification_role_ids.*") ||
                    $errors->has("doctor_arrival_notification_role_ids") ||
                    $errors->has("doctor_arrival_notification_role_ids.*") ||
                    $errors->has("promotion_management_role_ids") ||
                    $errors->has("promotion_management_role_ids.*") ||
                    $errors->has("patient_service_role_ids") ||
                    $errors->has("patient_service_role_ids.*") ||
                    $errors->has("promotion_notification_user_ids") ||
                    $errors->has("promotion_notification_user_ids.*")
                )
                    <div class="role-config-alert-error" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>
                            {{ $errors->first("registration_role_ids") ?: $errors->first("registration_role_ids.*") ?: $errors->first("email_onboarding_role_ids") ?: $errors->first("email_onboarding_role_ids.*") ?: $errors->first("promotion_notification_role_ids") ?: $errors->first("promotion_notification_role_ids.*") ?: $errors->first("doctor_arrival_notification_role_ids") ?: $errors->first("doctor_arrival_notification_role_ids.*") ?: $errors->first("promotion_management_role_ids") ?: $errors->first("promotion_management_role_ids.*") ?: $errors->first("patient_service_role_ids") ?: $errors->first("patient_service_role_ids.*") ?: $errors->first("promotion_notification_user_ids") ?: $errors->first("promotion_notification_user_ids.*") }}
                        </span>
                    </div>
                @endif

                @if ($roles->isNotEmpty())
                    <div class="role-config-mobile-tools">
                        <label class="role-config-role-search" for="roleConfigurationSearch">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="roleConfigurationSearch" type="search"
                                placeholder="Cari role..." autocomplete="off">
                        </label>
                        <span class="role-config-visible-count" aria-live="polite">
                            <strong id="visibleRoleCount">{{ $roles->count() }}</strong> role
                        </span>
                        <small><i class="bi bi-hand-index-thumb"></i> Cari lalu ketuk role untuk mengatur fiturnya.</small>
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
                            <span role="columnheader">
                                <i class="bi bi-megaphone"></i>
                                Notifikasi Konten
                            </span>
                            <span role="columnheader">
                                <i class="bi bi-person-check-fill"></i>
                                Dokter Datang
                            </span>
                            <span role="columnheader">
                                <i class="bi bi-pencil-square"></i>
                                Pengelola Konten
                            </span>
                            <span role="columnheader">
                                <i class="bi bi-headset"></i>
                                Admin Pasien Service
                            </span>
                        </div>

                        @forelse ($roles as $role)
                            @php
                                $registrationSelected = in_array((int) $role->id, $selectedRegistrationIds, true);
                                $emailSelected = in_array((int) $role->id, $selectedEmailOnboardingIds, true);
                                $promotionSelected = in_array((int) $role->id, $selectedPromotionNotificationIds, true);
                                $doctorArrivalSelected = in_array((int) $role->id, $selectedDoctorArrivalNotificationIds, true);
                                $promotionManagementSelected = in_array((int) $role->id, $selectedPromotionManagementIds, true);
                                $patientServiceSelected = in_array((int) $role->id, $selectedPatientServiceIds, true);
                                $roleActiveFeatureCount = collect([
                                    $registrationSelected,
                                    $emailSelected,
                                    $promotionSelected,
                                    $doctorArrivalSelected,
                                    $promotionManagementSelected,
                                    $patientServiceSelected && ! $role->patient_service_patient_role,
                                ])->filter()->count();
                            @endphp
                            <div class="role-config-matrix-row" role="row"
                                data-role-row data-role-name="{{ Str::lower($role->name) }}">
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

                                <button class="role-config-row-toggle" type="button"
                                    data-role-toggle aria-expanded="false"
                                    aria-label="Buka konfigurasi role {{ $role->name }}">
                                    <span><strong data-role-active-count>{{ $roleActiveFeatureCount }}</strong> fitur aktif</span>
                                    <span data-role-toggle-label>Atur <i class="bi bi-chevron-down"></i></span>
                                </button>

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

                                <div class="role-config-cell" role="cell">
                                    <span class="role-config-mobile-label">Notifikasi Promosi &amp; Informasi</span>
                                    <label class="role-config-toggle" for="promotion-role-{{ $role->id }}">
                                        <input class="role-config-checkbox" type="checkbox"
                                            id="promotion-role-{{ $role->id }}"
                                            name="promotion_notification_role_ids[]" value="{{ $role->id }}"
                                            data-feature="promotion" data-users="{{ $role->users_count }}"
                                            @checked($promotionSelected)>
                                        <span class="role-config-switch" aria-hidden="true"><span></span></span>
                                        <span class="role-config-toggle-copy">
                                            <strong data-toggle-state>{{ $promotionSelected ? "Aktif" : "Nonaktif" }}</strong>
                                            <small>Semua user aktif pada role ini</small>
                                        </span>
                                    </label>
                                </div>

                                <div class="role-config-cell" role="cell">
                                    <span class="role-config-mobile-label">Notifikasi Dokter Datang</span>
                                    <label class="role-config-toggle" for="doctor-arrival-role-{{ $role->id }}">
                                        <input class="role-config-checkbox" type="checkbox"
                                            id="doctor-arrival-role-{{ $role->id }}"
                                            name="doctor_arrival_notification_role_ids[]" value="{{ $role->id }}"
                                            data-feature="doctorArrival" data-users="{{ $role->users_count }}"
                                            @checked($doctorArrivalSelected)>
                                        <span class="role-config-switch" aria-hidden="true"><span></span></span>
                                        <span class="role-config-toggle-copy">
                                            <strong data-toggle-state>{{ $doctorArrivalSelected ? "Aktif" : "Nonaktif" }}</strong>
                                            <small>Pasien sesuai jadwal dokter &amp; poli</small>
                                        </span>
                                    </label>
                                </div>

                                <div class="role-config-cell" role="cell">
                                    <span class="role-config-mobile-label">Pengelola Promosi &amp; Informasi</span>
                                    <label class="role-config-toggle {{ $role->promotion_management_locked ? "is-locked" : "" }}" for="promotion-management-role-{{ $role->id }}">
                                        <input class="role-config-checkbox" type="checkbox"
                                            id="promotion-management-role-{{ $role->id }}"
                                            name="promotion_management_role_ids[]" value="{{ $role->id }}"
                                            data-feature="promotionManagement" data-users="{{ $role->users_count }}"
                                            @checked($promotionManagementSelected)
                                            @disabled($role->promotion_management_locked)>
                                        <span class="role-config-switch" aria-hidden="true"><span></span></span>
                                        <span class="role-config-toggle-copy">
                                            @if ($role->promotion_management_locked)
                                                <strong data-toggle-state>Akses permanen</strong>
                                                <small>Super Admin selalu dapat mengelola</small>
                                            @else
                                                <strong data-toggle-state>{{ $promotionManagementSelected ? "Aktif" : "Nonaktif" }}</strong>
                                                <small>Buat, edit, hapus &amp; lihat pembaca</small>
                                            @endif
                                        </span>
                                    </label>
                                </div>

                                <div class="role-config-cell" role="cell">
                                    <span class="role-config-mobile-label">Admin Pasien Service</span>
                                    <label class="role-config-toggle {{ $role->patient_service_locked || $role->patient_service_patient_role ? "is-locked" : "" }}" for="patient-service-role-{{ $role->id }}">
                                        <input class="role-config-checkbox" type="checkbox"
                                            id="patient-service-role-{{ $role->id }}"
                                            name="patient_service_role_ids[]" value="{{ $role->id }}"
                                            data-feature="patientService" data-users="{{ $role->users_count }}"
                                            @checked($patientServiceSelected && ! $role->patient_service_patient_role)
                                            @disabled($role->patient_service_locked || $role->patient_service_patient_role)>
                                        <span class="role-config-switch" aria-hidden="true"><span></span></span>
                                        <span class="role-config-toggle-copy">
                                            @if ($role->patient_service_locked)
                                                <strong data-toggle-state>Akses permanen</strong>
                                                <small>Super Admin selalu dapat mengelola</small>
                                            @elseif ($role->patient_service_patient_role)
                                                <strong data-toggle-state>Khusus pasien</strong>
                                                <small>Tidak dapat menerima chat pasien lain</small>
                                            @else
                                                <strong data-toggle-state>{{ $patientServiceSelected ? "Aktif" : "Nonaktif" }}</strong>
                                                <small>Masuk tim, terima notifikasi &amp; balas</small>
                                            @endif
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
                        <div id="roleConfigurationSearchEmpty" class="role-config-search-empty" hidden>
                            <span><i class="bi bi-search"></i></span>
                            <strong>Role tidak ditemukan</strong>
                            <small>Coba gunakan kata pencarian yang berbeda.</small>
                        </div>
                    </div>
                </div>

                <section class="role-config-recipient-section" aria-labelledby="promotionRecipientTitle">
                    <div class="role-config-recipient-heading">
                        <span class="role-config-recipient-icon"><i class="bi bi-person-check"></i></span>
                        <div>
                            <span class="access-panel-kicker">Target Individual</span>
                            <h3 id="promotionRecipientTitle">Pilih Pasien/User Tertentu</h3>
                            <p>Pilihan ini digabungkan dengan role di atas. Untuk testing satu akun, nonaktifkan seluruh role konten lalu pilih akun uji di sini.</p>
                        </div>
                        <span class="role-config-direct-count">
                            <strong data-direct-user-summary>{{ $selectedPromotionUserIds->count() }}</strong>
                            <small>user dipilih</small>
                        </span>
                    </div>

                    <label class="role-config-user-label" for="promotionNotificationUsers">
                        Cari berdasarkan nama, username, atau email
                    </label>
                    <select id="promotionNotificationUsers"
                        class="role-config-user-select"
                        name="promotion_notification_user_ids[]"
                        multiple
                        data-search-url="{{ route("roleConfiguration.users") }}"
                        data-placeholder="Ketik minimal beberapa huruf untuk mencari user...">
                        @foreach ($selectedPromotionUsers as $selectedUser)
                            <option value="{{ $selectedUser->id }}" @selected($selectedPromotionUserIds->contains((int) $selectedUser->id))>
                                {{ $selectedUser->name }}{{ $selectedUser->username ? " ({$selectedUser->username})" : ($selectedUser->email ? " ({$selectedUser->email})" : "") }}
                            </option>
                        @endforeach
                    </select>
                    <small class="role-config-user-help">
                        <i class="bi bi-info-circle"></i>
                        User nonaktif tetap tidak akan menerima notifikasi meskipun pernah dipilih.
                    </small>
                </section>

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
            const userSelect = document.getElementById("promotionNotificationUsers");
            const promotionRecipientCount = document.getElementById("promotionRecipientCount");
            const roleSearch = document.getElementById("roleConfigurationSearch");
            const roleRows = [...form.querySelectorAll("[data-role-row]")];
            const visibleRoleCount = document.getElementById("visibleRoleCount");
            const roleSearchEmpty = document.getElementById("roleConfigurationSearchEmpty");
            const initial = {
                registration: (form.dataset.initialRegistration || "").split(",").filter(Boolean).sort(),
                email: (form.dataset.initialEmail || "").split(",").filter(Boolean).sort(),
                promotion: (form.dataset.initialPromotionRoles || "").split(",").filter(Boolean).sort(),
                doctorArrival: (form.dataset.initialDoctorArrival || "").split(",").filter(Boolean).sort(),
                promotionManagement: (form.dataset.initialPromotionManagement || "").split(",").filter(Boolean).sort(),
                patientService: (form.dataset.initialPatientService || "").split(",").filter(Boolean).sort(),
                promotionUsers: (form.dataset.initialPromotionUsers || "").split(",").filter(Boolean).sort(),
            };

            const featureInputs = (feature) => inputs.filter((input) => input.dataset.feature === feature);
            const selectedIds = (feature) => featureInputs(feature)
                .filter((input) => input.checked)
                .map((input) => input.value)
                .sort();
            const selectedUserIds = () => [...(userSelect?.selectedOptions || [])]
                .map((option) => option.value)
                .filter(Boolean)
                .sort();

            const refreshToggle = (input) => {
                if (input.disabled) {
                    return;
                }

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

            const setRoleExpanded = (row, expanded) => {
                const toggle = row.querySelector("[data-role-toggle]");
                const label = row.querySelector("[data-role-toggle-label]");

                row.classList.toggle("is-expanded", expanded);
                toggle?.setAttribute("aria-expanded", String(expanded));

                if (label) {
                    label.innerHTML = expanded
                        ? 'Tutup <i class="bi bi-chevron-up"></i>'
                        : 'Atur <i class="bi bi-chevron-down"></i>';
                }
            };

            const refreshRoleRows = () => {
                roleRows.forEach((row) => {
                    const activeCount = [...row.querySelectorAll(".role-config-checkbox")]
                        .filter((input) => input.checked).length;
                    const count = row.querySelector("[data-role-active-count]");

                    if (count) {
                        count.textContent = activeCount;
                    }

                    row.classList.toggle("has-active-features", activeCount > 0);
                });
            };

            const refreshSummary = () => {
                const registrationIds = selectedIds("registration");
                const emailIds = selectedIds("email");
                const promotionIds = selectedIds("promotion");
                const doctorArrivalIds = selectedIds("doctorArrival");
                const promotionManagementIds = selectedIds("promotionManagement");
                const patientServiceIds = selectedIds("patientService");
                const promotionUserIds = selectedUserIds();
                const registrationChanged = registrationIds.join(",") !== initial.registration.join(",");
                const emailChanged = emailIds.join(",") !== initial.email.join(",");
                const promotionChanged = promotionIds.join(",") !== initial.promotion.join(",");
                const doctorArrivalChanged = doctorArrivalIds.join(",") !== initial.doctorArrival.join(",");
                const promotionManagementChanged = promotionManagementIds.join(",") !== initial.promotionManagement.join(",");
                const patientServiceChanged = patientServiceIds.join(",") !== initial.patientService.join(",");
                const promotionUsersChanged = promotionUserIds.join(",") !== initial.promotionUsers.join(",");
                const hasChanges = registrationChanged || emailChanged || promotionChanged || doctorArrivalChanged || promotionManagementChanged ||
                    patientServiceChanged || promotionUsersChanged;
                const assignmentCount = registrationIds.length + emailIds.length +
                    promotionIds.length + doctorArrivalIds.length + promotionManagementIds.length + patientServiceIds.length + promotionUserIds.length;

                inputs.forEach(refreshToggle);
                refreshRoleRows();

                document.getElementById("registrationRoleCount").textContent = registrationIds.length;
                document.getElementById("emailOnboardingRoleCount").textContent = emailIds.length;
                document.getElementById("registrationUserCount").textContent =
                    countUsers("registration").toLocaleString("id-ID");
                document.getElementById("emailOnboardingUserCount").textContent =
                    countUsers("email").toLocaleString("id-ID");
                document.getElementById("activeConfigurationCount").textContent = assignmentCount;
                document.querySelector("[data-registration-summary]").textContent = registrationIds.length;
                document.querySelector("[data-email-summary]").textContent = emailIds.length;
                document.querySelector("[data-promotion-summary]").textContent = promotionIds.length;
                document.querySelector("[data-doctor-arrival-summary]").textContent = doctorArrivalIds.length;
                document.querySelector("[data-promotion-management-summary]").textContent = promotionManagementIds.length;
                document.querySelector("[data-patient-service-summary]").textContent = patientServiceIds.length;
                document.querySelector("[data-direct-user-summary]").textContent = promotionUserIds.length;
                document.getElementById("promotionDirectUserCount").textContent = promotionUserIds.length;

                if (promotionRecipientCount) {
                    promotionRecipientCount.textContent = hasChanges
                        ? "—"
                        : promotionRecipientCount.dataset.savedCount;
                }

                saveButton.disabled = !hasChanges;
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
                    ? `${promotionManagementIds.length} role dapat mengelola konten; ${patientServiceIds.length} role menangani Pasien Service; ${promotionIds.length} role menjadi target informasi; ${doctorArrivalIds.length} role menerima notifikasi dokter datang.`
                    : "Ubah sakelar untuk mengaktifkan tombol simpan.";
            };

            inputs.forEach((input) => input.addEventListener("change", refreshSummary));

            roleRows.forEach((row) => {
                row.querySelector("[data-role-toggle]")?.addEventListener("click", () => {
                    const willExpand = !row.classList.contains("is-expanded");

                    if (willExpand) {
                        roleRows.forEach((candidate) => {
                            if (candidate !== row) {
                                setRoleExpanded(candidate, false);
                            }
                        });
                    }

                    setRoleExpanded(row, willExpand);
                });
            });

            roleSearch?.addEventListener("input", () => {
                const query = roleSearch.value.trim().toLocaleLowerCase("id-ID");
                let visible = 0;

                roleRows.forEach((row) => {
                    const matches = !query || row.dataset.roleName.includes(query);
                    row.hidden = !matches;

                    if (matches) {
                        visible += 1;
                    } else {
                        setRoleExpanded(row, false);
                    }
                });

                if (visibleRoleCount) {
                    visibleRoleCount.textContent = visible;
                }

                if (roleSearchEmpty) {
                    roleSearchEmpty.hidden = visible !== 0;
                }
            });

            if (userSelect && window.jQuery?.fn?.select2) {
                window.jQuery(userSelect).select2({
                    theme: "bootstrap4",
                    width: "100%",
                    placeholder: userSelect.dataset.placeholder,
                    allowClear: true,
                    closeOnSelect: false,
                    minimumInputLength: 1,
                    ajax: {
                        url: userSelect.dataset.searchUrl,
                        dataType: "json",
                        delay: 250,
                        data: (params) => ({ q: params.term || "" }),
                        processResults: (data) => data,
                        cache: true,
                    },
                    language: {
                        inputTooShort: () => "Ketik nama, username, atau email.",
                        noResults: () => "User tidak ditemukan.",
                        searching: () => "Mencari user...",
                    },
                }).on("change", refreshSummary);
            } else {
                userSelect?.addEventListener("change", refreshSummary);
            }

            resetButton?.addEventListener("click", () => {
                inputs.forEach((input) => {
                    input.checked = initial[input.dataset.feature].includes(input.value);
                });

                if (userSelect) {
                    if (window.jQuery) {
                        window.jQuery(userSelect).val(initial.promotionUsers).trigger("change");
                    } else {
                        [...userSelect.options].forEach((option) => {
                            option.selected = initial.promotionUsers.includes(option.value);
                        });
                    }
                }
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
