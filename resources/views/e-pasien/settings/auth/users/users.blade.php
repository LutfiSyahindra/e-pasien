@extends("template.epasien.appPasien")

@section("title", "Users | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/auth-premium.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @include("e-pasien.settings.auth.users.modalMain")
    @include("e-pasien.settings.auth.users.modalAssignRoles")

    <div class="access-page auth-premium-page auth-users-page">
        <div class="access-breadcrumb">
            <a href="{{ route("dashboard") }}"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right"></i>
            <span>Pengaturan Akses</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Users</span>
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
                <a class="auth-suite-tab active" href="{{ route("users.users") }}" aria-current="page">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                </a>
                <a class="auth-suite-tab" href="{{ route("roles.roles") }}">
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
                <span class="access-page-icon"><i class="bi bi-people"></i></span>
                <div>
                    <span class="access-eyebrow">Identity Directory</span>
                    <h1>Manajemen Users</h1>
                    <p>Kelola identitas pengguna dan aksesnya dalam satu ruang kerja.</p>
                </div>
            </div>
            <div class="auth-header-actions">
                <span class="auth-security-state">
                    <i class="bi bi-shield-check"></i>
                    Role-based access
                </span>
                <button type="button" class="btn-access-primary" data-bs-toggle="modal"
                    data-bs-target="#usersModal">
                    <i class="bi bi-person-plus"></i>
                    <span>Tambah User</span>
                </button>
            </div>
        </div>

        <div class="row g-3 access-stats">
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-indigo">
                    <span class="access-stat-icon purple"><i class="bi bi-people"></i></span>
                    <span class="access-stat-copy">
                        <small>Total Users</small>
                        <strong id="userStatTotal">0</strong>
                        <em>Semua akun</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-green">
                    <span class="access-stat-icon green"><i class="bi bi-check2-circle"></i></span>
                    <span class="access-stat-copy">
                        <small>Aktif</small>
                        <strong id="userStatActive">0</strong>
                        <em>Dapat mengakses</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-amber">
                    <span class="access-stat-icon orange"><i class="bi bi-slash-circle"></i></span>
                    <span class="access-stat-copy">
                        <small>Nonaktif</small>
                        <strong id="userStatInactive">0</strong>
                        <em>Akses ditangguhkan</em>
                    </span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card tone-cyan">
                    <span class="access-stat-icon cyan"><i class="bi bi-person-badge"></i></span>
                    <span class="access-stat-copy">
                        <small>Punya Role</small>
                        <strong id="userStatWithRoles">0</strong>
                        <em>Akses terpetakan</em>
                    </span>
                </div>
            </div>
        </div>

        <section class="access-panel">
            <div class="access-panel-header">
                <div class="access-panel-title">
                    <span class="access-panel-kicker"><i class="bi bi-list-ul"></i> User Directory</span>
                    <h2>Daftar Pengguna</h2>
                    <p>Identitas, role, dan status akun terbaru.</p>
                </div>
                <div class="access-filter">
                    <div class="access-search">
                        <i class="bi bi-search"></i>
                        <input id="searchUser" class="form-control" type="search"
                            placeholder="Cari nama atau email..." aria-label="Cari user">
                    </div>
                    <select id="filterUserStatus" class="form-select" aria-label="Filter status user">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                    <button type="button" id="resetUserFilter" class="access-filter-button">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset</span>
                    </button>
                    <button type="button" id="refreshUsers" class="access-filter-button icon-only"
                        title="Muat ulang data" aria-label="Muat ulang data user">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tableUsers" class="table access-table" aria-label="Daftar user">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push("script")
    @include("e-pasien.settings.auth.users.jsMain")
@endpush
