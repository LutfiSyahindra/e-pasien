        <!--start sidebar -->
        <aside class="sidebar-wrapper" data-simplebar="true">
            <div class="sidebar-header">
                <a href="{{ route("dashboard") }}" class="ep-sidebar-brand">
                    <img src="{{ asset("landing/assets/imagesArsy/epasien.png") }}"
                        class="ep-sidebar-logo ep-sidebar-logo-full" alt="E-Pasien">
                    <img src="{{ asset("landing/assets/imagesArsy/logoarsy.png") }}"
                        class="ep-sidebar-logo ep-sidebar-logo-compact" alt="RS-Arsy">
                </a>
                <div class="toggle-icon ms-auto"><i class="bi bi-chevron-double-left"></i>
                </div>
            </div>
            <!--navigation-->
            <ul class="metismenu" id="menu">
                <li>
                    <a href="{{ route("dashboard") }}">
                        <div class="parent-icon"><i class="bi bi-house-door"></i>
                        </div>
                        <div class="menu-title">Dashboard</div>
                    </a>
                </li>

                <li class="menu-label">Settings</li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div class="menu-title">Authentication</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route("users.users") }}">
                                <i class="bi bi-people"></i>
                                Users
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("roles.roles") }}">
                                <i class="bi bi-person-badge"></i>
                                Roles
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("permissions.permissions") }}">
                                <i class="bi bi-key"></i>
                                Permissions
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("registrationRoleConfiguration.index") }}">
                                <i class="bi bi-person-check"></i>
                                Role Pendaftaran
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="menu-label">Menu</li>
                <li>
                    <a href="{{ route("riwayatPemeriksaan.index") }}">
                        <div class="parent-icon"><i class="bi bi-clipboard2-pulse"></i>
                        </div>
                        <div class="menu-title">Riwayat Pemeriksaan</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route("riwayatMcu.index") }}">
                        <div class="parent-icon"><i class="bi bi-clipboard2-heart"></i>
                        </div>
                        <div class="menu-title">Riwayat MCU</div>
                    </a>
                </li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class="bi bi-clipboard2-plus"></i>
                        </div>
                        <div class="menu-title">Permintaan dan Tindakan</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route("pemeriksaanLaborat.index") }}">
                                <i class="bi bi-droplet-half"></i>
                                Pemeriksaan Laborat
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("pemeriksaanRadiologi.index") }}">
                                <i class="bi bi-radioactive"></i>
                                Pemeriksaan Radiologi
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("resepObat.index") }}">
                                <i class="bi bi-capsule-pill"></i>
                                Resep Obat
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("operasi.index") }}">
                                <i class="bi bi-bandaid"></i>
                                Operasi
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="{{ request()->routeIs("kamar.*", "laboratorium.*", "poliklinik.*", "radiologi.*") ? "mm-active" : "" }}">
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class="bi bi-building"></i>
                        </div>
                        <div class="menu-title">Fasilitas &amp; Tarif</div>
                    </a>
                    <ul class="{{ request()->routeIs("kamar.*", "laboratorium.*", "poliklinik.*", "radiologi.*") ? "mm-show" : "" }}">
                        <li>
                            <a href="{{ route("kamar.index") }}"
                                class="{{ request()->routeIs("kamar.*") ? "mm-active" : "" }}">
                                <i class="bi bi-door-open"></i>
                                Kamar
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("poliklinik.index") }}"
                                class="{{ request()->routeIs("poliklinik.*") ? "mm-active" : "" }}">
                                <i class="bi bi-hospital"></i>
                                Poliklinik
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("laboratorium.index") }}"
                                class="{{ request()->routeIs("laboratorium.*") ? "mm-active" : "" }}">
                                <i class="bi bi-droplet-half"></i>
                                Laboratorium
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("radiologi.index") }}"
                                class="{{ request()->routeIs("radiologi.*") ? "mm-active" : "" }}">
                                <i class="bi bi-radioactive"></i>
                                Radiologi
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class="bi bi-calendar2-plus"></i>
                        </div>
                        <div class="menu-title">Pendaftaran Online</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route("daftarOnline.index") }}">
                                <i class="bi bi-calendar2-plus"></i>
                                Daftar Baru
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("daftarOnline.history") }}">
                                <i class="bi bi-clock-history"></i>
                                Riwayat Pendaftaran
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
            <!--end navigation-->
        </aside>
        <!--end sidebar -->
