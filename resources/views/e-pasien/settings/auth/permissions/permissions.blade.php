@extends("template.epasien.appPasien")

@section("title", "Permissions | E-Pasien")

@section("content")
    @include("e-pasien.settings.auth.permissions.modalMain")

    <div class="access-page">
        <div class="access-breadcrumb">
            <a href="{{ route("dashboard") }}"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right"></i>
            <span>Authentication</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Permissions</span>
        </div>

        <div class="access-page-header">
            <div class="access-page-heading">
                <span class="access-page-icon"><i class="bi bi-key"></i></span>
                <div>
                    <span class="access-eyebrow">Access Control</span>
                    <h1>Permissions</h1>
                    <p>Kelola permission granular yang dapat disematkan ke role.</p>
                </div>
            </div>
            <button type="button" class="btn-access-primary" data-bs-toggle="modal"
                data-bs-target="#permissionsModal">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah Permission</span>
            </button>
        </div>

        <div class="row g-3 access-stats">
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon purple"><i class="bi bi-key"></i></span>
                    <span><small>Total Permissions</small><strong id="permissionStatTotal">0</strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon green"><i class="bi bi-lock"></i></span>
                    <span><small>Protected</small><strong id="permissionStatProtected">0</strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon cyan"><i class="bi bi-person-badge"></i></span>
                    <span><small>Dipakai Role</small><strong id="permissionStatAssigned">0</strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="access-stat-card">
                    <span class="access-stat-icon orange"><i class="bi bi-exclamation-circle"></i></span>
                    <span><small>Belum Dipakai</small><strong id="permissionStatUnassigned">0</strong></span>
                </div>
            </div>
        </div>

        <section class="access-panel">
            <div class="access-panel-header">
                <div class="access-panel-title">
                    <h2>Daftar Permissions</h2>
                    <p>Daftar permission dan role yang sedang menggunakannya.</p>
                </div>
                <div class="access-filter">
                    <div class="access-search">
                        <i class="bi bi-search"></i>
                        <input id="searchPermission" class="form-control" type="text" placeholder="Search permissions">
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
                    <button type="button" id="refreshPermissions" class="access-filter-button">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tablePermissions" class="table access-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Permission</th>
                            <th>Guard</th>
                            <th>Roles</th>
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
    @include("e-pasien.settings.auth.permissions.jsMain")
@endpush
