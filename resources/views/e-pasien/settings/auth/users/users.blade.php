@extends("template.epasien.appPasien")

@section("title", "Users | E-Pasien")

@section("content")
    @include("e-pasien.settings.auth.users.modalMain")
    @include("e-pasien.settings.auth.users.modalAssignRoles")

    <div class="access-page">
        <div class="access-breadcrumb">
            <a href="{{ route("dashboard") }}"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right"></i>
            <span>Authentication</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Users</span>
        </div>

        <div class="access-page-header">
            <div class="access-page-heading">
                <span class="access-page-icon"><i class="bi bi-people"></i></span>
                <div>
                    <span class="access-eyebrow">Access Control</span>
                    <h1>Users</h1>
                    <p>Kelola akun, status aktif, dan role akses pengguna aplikasi.</p>
                </div>
            </div>
            <button type="button" class="btn-access-primary" data-bs-toggle="modal" data-bs-target="#usersModal">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah User</span>
            </button>
        </div>

        <div class="row g-3 access-stats">
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon purple"><i class="bi bi-people"></i></span>
                    <span><small>Total Users</small><strong id="userStatTotal">0</strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon green"><i class="bi bi-check2-circle"></i></span>
                    <span><small>Aktif</small><strong id="userStatActive">0</strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon orange"><i class="bi bi-slash-circle"></i></span>
                    <span><small>Nonaktif</small><strong id="userStatInactive">0</strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon cyan"><i class="bi bi-person-badge"></i></span>
                    <span><small>Punya Role</small><strong id="userStatWithRoles">0</strong></span>
                </div>
            </div>
        </div>

        <section class="access-panel">
            <div class="access-panel-header">
                <div class="access-panel-title">
                    <h2>Direktori Users</h2>
                    <p>Daftar akun dengan status dan role aktif.</p>
                </div>
                <div class="access-filter">
                    <div class="access-search">
                        <i class="bi bi-search"></i>
                        <input id="searchUser" class="form-control" type="text" placeholder="Search users">
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
                    <button type="button" id="refreshUsers" class="access-filter-button">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tableUsers" class="table access-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Actions</th>
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
