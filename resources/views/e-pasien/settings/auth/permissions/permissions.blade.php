@extends("template.epasien.appPasien")

@section("title", "Permissions | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/auth-premium.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @include("e-pasien.settings.auth.permissions.modalMain")

    <div class="access-page auth-premium-page auth-permissions-page">
        <div class="access-breadcrumb">
            <a href="{{ route("dashboard") }}"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right"></i>
            <span>Pengaturan Akses</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Permissions</span>
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
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                </a>
                <a class="auth-suite-tab" href="{{ route("roles.roles") }}">
                    <i class="bi bi-shield-lock"></i>
                    <span>Roles</span>
                </a>
                <a class="auth-suite-tab active" href="{{ route("permissions.permissions") }}" aria-current="page">
                    <i class="bi bi-key"></i>
                    <span>Permissions</span>
                </a>
                <a class="auth-suite-tab" href="{{ route("roleConfiguration.index") }}">
                    <i class="bi bi-sliders"></i>
                    <span>Konfigurasi Roles</span>
                </a>
            </nav>
        </div>

        <div class="access-page-header">
            <div class="access-page-heading">
                <span class="access-page-icon"><i class="bi bi-key"></i></span>
                <div>
                    <span class="access-eyebrow">Access Capabilities</span>
                    <h1>Manajemen Permissions</h1>
                    <p>Kelola kapabilitas akses granular yang digunakan oleh roles.</p>
                </div>
            </div>
            <div class="auth-header-actions">
                <span class="auth-security-state">
                    <i class="bi bi-lock"></i>
                    Granular control
                </span>
                <button type="button" class="btn-access-primary" data-bs-toggle="modal"
                    data-bs-target="#permissionsModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Tambah Permission</span>
                </button>
            </div>
        </div>

        <div class="row g-3 access-stats">
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-indigo">
                    <span class="access-stat-icon purple"><i class="bi bi-key"></i></span>
                    <span class="access-stat-copy">
                        <small>Total Permissions</small>
                        <strong id="permissionStatTotal">0</strong>
                        <em>Semua kapabilitas</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-green">
                    <span class="access-stat-icon green"><i class="bi bi-lock"></i></span>
                    <span class="access-stat-copy">
                        <small>Protected</small>
                        <strong id="permissionStatProtected">0</strong>
                        <em>Kapabilitas inti</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-cyan">
                    <span class="access-stat-icon cyan"><i class="bi bi-person-badge"></i></span>
                    <span class="access-stat-copy">
                        <small>Dipakai Role</small>
                        <strong id="permissionStatAssigned">0</strong>
                        <em>Sudah terpetakan</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-amber">
                    <span class="access-stat-icon orange"><i class="bi bi-exclamation-circle"></i></span>
                    <span class="access-stat-copy">
                        <small>Belum Dipakai</small>
                        <strong id="permissionStatUnassigned">0</strong>
                        <em>Perlu ditinjau</em>
                    </span>
                </div>
            </div>
        </div>

        <section class="access-panel">
            <div class="access-panel-header">
                <div class="access-panel-title">
                    <span class="access-panel-kicker"><i class="bi bi-list-ul"></i> Permission Catalog</span>
                    <h2>Daftar Permissions</h2>
                    <p>Kapabilitas akses dan role yang menggunakannya.</p>
                </div>
                <div class="access-filter">
                    <div class="access-search">
                        <i class="bi bi-search"></i>
                        <input id="searchPermission" class="form-control" type="search"
                            placeholder="Cari permission..." aria-label="Cari permission">
                    </div>
                    <select id="filterPermissionType" class="form-select" aria-label="Filter tipe permission">
                        <option value="">Semua Permission</option>
                        <option value="protected">Protected</option>
                        <option value="custom">Custom</option>
                    </select>
                    <button type="button" id="resetPermissionFilter" class="access-filter-button">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset</span>
                    </button>
                    <button type="button" id="refreshPermissions" class="access-filter-button icon-only"
                        title="Muat ulang data" aria-label="Muat ulang data permission">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tablePermissions" class="table access-table" aria-label="Daftar permission">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Permission</th>
                            <th>Guard</th>
                            <th>Roles</th>
                            <th>Aksi</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push("script")
    @include("e-pasien.settings.auth.permissions.jsMain")
@endpush
