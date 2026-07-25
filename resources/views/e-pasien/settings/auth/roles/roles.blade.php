@extends("template.epasien.appPasien")

@section("title", "Roles | E-Pasien")

@section("content")
    @include("e-pasien.settings.auth.roles.modalMain")
    @include("e-pasien.settings.auth.roles.modalAssignPermissions")

    <div class="access-page">
        <div class="access-breadcrumb">
            <a href="{{ route("dashboard") }}"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right"></i>
            <span>Authentication</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Roles</span>
        </div>

        <div class="access-page-header">
            <div class="access-page-heading">
                <span class="access-page-icon"><i class="bi bi-shield-lock"></i></span>
                <div>
                    <span class="access-eyebrow">Access Control</span>
                    <h1>Roles</h1>
                    <p>Atur role dan permission yang membentuk akses pengguna.</p>
                </div>
            </div>
            <button type="button" class="btn-access-primary" data-bs-toggle="modal" data-bs-target="#rolesModal">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah Role</span>
            </button>
        </div>

        <div class="row g-3 access-stats">
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon purple"><i class="bi bi-shield-lock"></i></span>
                    <span><small>Total Roles</small><strong id="roleStatTotal">0</strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon orange"><i class="bi bi-award"></i></span>
                    <span><small>System Role</small><strong id="roleStatSystem">0</strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon green"><i class="bi bi-key"></i></span>
                    <span><small>Punya Permission</small><strong id="roleStatWithPermissions">0</strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon cyan"><i class="bi bi-diagram-3"></i></span>
                    <span><small>Total Assignment</small><strong id="roleStatAssignments">0</strong></span>
                </div>
            </div>
        </div>

        <section class="access-panel">
            <div class="access-panel-header">
                <div class="access-panel-title">
                    <h2>Daftar Roles</h2>
                    <p>Kelola role, guard, dan rangkaian permission.</p>
                </div>
                <div class="access-filter">
                    <div class="access-search">
                        <i class="bi bi-search"></i>
                        <input id="searchRole" class="form-control" type="text" placeholder="Search roles">
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
                    <button type="button" id="refreshRoles" class="access-filter-button">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tableRoles" class="table access-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Role</th>
                            <th>Guard</th>
                            <th>Permissions</th>
                            <th>Actions</th>
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
