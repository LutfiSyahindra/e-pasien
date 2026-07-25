@extends("template.epasien.appPasien")

@section("title", "Roles | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/auth-premium.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @include("e-pasien.settings.auth.roles.modalMain")
    @include("e-pasien.settings.auth.roles.modalAssignPermissions")

    <div class="access-page auth-premium-page auth-roles-page">
        <div class="access-breadcrumb">
            <a href="{{ route("dashboard") }}"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right"></i>
            <span>Pengaturan Akses</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Roles</span>
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
                <a class="auth-suite-tab active" href="{{ route("roles.roles") }}" aria-current="page">
                    <i class="bi bi-shield-lock"></i>
                    <span>Roles</span>
                </a>
                <a class="auth-suite-tab" href="{{ route("permissions.permissions") }}">
                    <i class="bi bi-key"></i>
                    <span>Permissions</span>
                </a>
            </nav>
        </div>

        <div class="access-page-header">
            <div class="access-page-heading">
                <span class="access-page-icon"><i class="bi bi-shield-lock"></i></span>
                <div>
                    <span class="access-eyebrow">Authorization Groups</span>
                    <h1>Manajemen Roles</h1>
                    <p>Rancang kelompok akses dan permission yang menyertainya.</p>
                </div>
            </div>
            <div class="auth-header-actions">
                <span class="auth-security-state">
                    <i class="bi bi-diagram-3"></i>
                    Structured access
                </span>
                <button type="button" class="btn-access-primary" data-bs-toggle="modal"
                    data-bs-target="#rolesModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Tambah Role</span>
                </button>
            </div>
        </div>

        <div class="row g-3 access-stats">
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-indigo">
                    <span class="access-stat-icon purple"><i class="bi bi-shield-lock"></i></span>
                    <span class="access-stat-copy">
                        <small>Total Roles</small>
                        <strong id="roleStatTotal">0</strong>
                        <em>Semua kelompok</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-amber">
                    <span class="access-stat-icon orange"><i class="bi bi-award"></i></span>
                    <span class="access-stat-copy">
                        <small>System Role</small>
                        <strong id="roleStatSystem">0</strong>
                        <em>Akses inti</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-green">
                    <span class="access-stat-icon green"><i class="bi bi-key"></i></span>
                    <span class="access-stat-copy">
                        <small>Punya Permission</small>
                        <strong id="roleStatWithPermissions">0</strong>
                        <em>Siap digunakan</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-cyan">
                    <span class="access-stat-icon cyan"><i class="bi bi-diagram-3"></i></span>
                    <span class="access-stat-copy">
                        <small>Total Assignment</small>
                        <strong id="roleStatAssignments">0</strong>
                        <em>Relasi permission</em>
                    </span>
                </div>
            </div>
        </div>

        <section class="access-panel">
            <div class="access-panel-header">
                <div class="access-panel-title">
                    <span class="access-panel-kicker"><i class="bi bi-list-ul"></i> Role Registry</span>
                    <h2>Daftar Roles</h2>
                    <p>Role, guard, dan permission yang sedang berlaku.</p>
                </div>
                <div class="access-filter">
                    <div class="access-search">
                        <i class="bi bi-search"></i>
                        <input id="searchRole" class="form-control" type="search"
                            placeholder="Cari nama role..." aria-label="Cari role">
                    </div>
                    <select id="filterRoleType" class="form-select" aria-label="Filter tipe role">
                        <option value="">Semua Role</option>
                        <option value="system">System</option>
                        <option value="custom">Custom</option>
                    </select>
                    <button type="button" id="resetRoleFilter" class="access-filter-button">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset</span>
                    </button>
                    <button type="button" id="refreshRoles" class="access-filter-button icon-only"
                        title="Muat ulang data" aria-label="Muat ulang data role">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tableRoles" class="table access-table" aria-label="Daftar role">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Role</th>
                            <th>Guard</th>
                            <th>Permissions</th>
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
    @include("e-pasien.settings.auth.roles.jsMain")
@endpush
