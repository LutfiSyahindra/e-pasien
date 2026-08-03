        @php
            $authenticationMenuActive = request()->routeIs(
                "users.*",
                "roles.*",
                "permissions.*",
                "roleConfiguration.*",
            );
            $requestMenuActive = request()->routeIs(
                "pemeriksaanLaborat.*",
                "pemeriksaanRadiologi.*",
                "resepObat.*",
                "operasi.*",
            );
            $facilityMenuActive = request()->routeIs("kamar.*", "laboratorium.*", "poliklinik.*", "radiologi.*");
            $registrationMenuActive = request()->routeIs("daftarOnline.*");
        @endphp

        <!--start sidebar -->
        <aside class="sidebar-wrapper ep-premium-sidebar" aria-label="Navigasi utama">
            <div class="sidebar-header">
                <a href="{{ route("dashboard") }}" class="ep-sidebar-brand" aria-label="E-Pasien - ke dashboard">
                    <img src="{{ asset("landing/assets/imagesArsy/epasien.png") }}"
                        class="ep-sidebar-logo ep-sidebar-logo-full" alt="E-Pasien">
                    <img src="{{ asset("landing/assets/imagesArsy/logoarsy.png") }}"
                        class="ep-sidebar-logo ep-sidebar-logo-compact" alt="RS-Arsy">
                </a>
                <button type="button" class="toggle-icon ms-auto" aria-label="Ciutkan sidebar" aria-expanded="true"
                    title="Ciutkan sidebar">
                    <i class="bi bi-chevron-double-left" aria-hidden="true"></i>
                </button>
            </div>

            <div class="ep-sidebar-tools">
                <label class="ep-sidebar-search" for="ep-sidebar-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="ep-sidebar-search" type="search" placeholder="Cari menu..." autocomplete="off"
                        aria-controls="menu">
                    <kbd aria-hidden="true">/</kbd>
                </label>
            </div>

            <div class="ep-sidebar-scroll" data-simplebar="true" data-simplebar-auto-hide="false">
                <!--navigation-->
                <ul class="metismenu" id="menu" aria-label="Menu E-Pasien">
                    @can("EPASIEN.SETTINGS")
                        <li class="menu-label"><span>Pengaturan</span></li>
                        <li class="{{ $authenticationMenuActive ? "mm-active" : "" }}">
                            <a href="javascript:;" class="has-arrow"
                                aria-expanded="{{ $authenticationMenuActive ? "true" : "false" }}">
                                <div class="parent-icon">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                                <div class="menu-title">Akses &amp; Keamanan</div>
                            </a>
                            <ul class="{{ $authenticationMenuActive ? "mm-show" : "" }}">
                                <li>
                                    <a href="{{ route("users.users") }}"
                                        class="{{ request()->routeIs("users.*") ? "mm-active" : "" }}"
                                        @if (request()->routeIs("users.*")) aria-current="page" @endif>
                                        <i class="bi bi-people"></i>
                                        Pengguna
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route("roles.roles") }}"
                                        class="{{ request()->routeIs("roles.*") ? "mm-active" : "" }}"
                                        @if (request()->routeIs("roles.*")) aria-current="page" @endif>
                                        <i class="bi bi-person-badge"></i>
                                        Peran
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route("permissions.permissions") }}"
                                        class="{{ request()->routeIs("permissions.*") ? "mm-active" : "" }}"
                                        @if (request()->routeIs("permissions.*")) aria-current="page" @endif>
                                        <i class="bi bi-key"></i>
                                        Hak Akses
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route("roleConfiguration.index") }}"
                                        class="{{ request()->routeIs("roleConfiguration.*") ? "mm-active" : "" }}"
                                        @if (request()->routeIs("roleConfiguration.*")) aria-current="page" @endif>
                                        <i class="bi bi-sliders"></i>
                                        Konfigurasi Peran
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @can("EPASIEN.SETTINGS.JADWAL_DOKTER")
                            <li class="{{ request()->routeIs("doctorScheduleSettings.*") ? "mm-active" : "" }}">
                                <a href="{{ route("doctorScheduleSettings.index") }}"
                                    @if (request()->routeIs("doctorScheduleSettings.*")) aria-current="page" @endif>
                                    <div class="parent-icon">
                                        <i class="bi bi-calendar2-check"></i>
                                    </div>
                                    <div class="menu-title">Atur Jadwal Dokter</div>
                                </a>
                            </li>
                        @endcan
                    @endcan

                    @can("EPASIEN.MENU")
                        <li class="menu-label"><span>Layanan</span></li>
                        @can("EPASIEN.MENU.DASHBOARD")
                            <li class="{{ request()->routeIs("dashboard") ? "mm-active" : "" }}">
                                <a href="{{ route("dashboard") }}"
                                    @if (request()->routeIs("dashboard")) aria-current="page" @endif>
                                    <div class="parent-icon"><i class="bi bi-house-door"></i>
                                    </div>
                                    <div class="menu-title">Dashboard</div>
                                </a>
                            </li>
                        @endcan

                        @can("EPASIEN.MENU.PROMOSI")
                            <li class="{{ request()->routeIs("promotions.*") ? "mm-active" : "" }}">
                                <a href="{{ route("promotions.index") }}"
                                    @if (request()->routeIs("promotions.*")) aria-current="page" @endif>
                                    <div class="parent-icon"><i class="bi bi-stars"></i></div>
                                    <div class="menu-title">Promo Sehat</div>
                                    <span class="ep-sidebar-menu-badge">Baru</span>
                                </a>
                            </li>
                        @endcan

                        @can("EPASIEN.MENU.PENDAFTARAN_ONLINE")
                            <li class="{{ $registrationMenuActive ? "mm-active" : "" }}">
                                <a href="javascript:;" class="has-arrow"
                                    aria-expanded="{{ $registrationMenuActive ? "true" : "false" }}">
                                    <div class="parent-icon"><i class="bi bi-calendar2-plus"></i>
                                    </div>
                                    <div class="menu-title">Pendaftaran Online</div>
                                </a>
                                <ul class="{{ $registrationMenuActive ? "mm-show" : "" }}">
                                    <li>
                                        <a href="{{ route("daftarOnline.index") }}"
                                            class="{{ request()->routeIs("daftarOnline.index") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("daftarOnline.index")) aria-current="page" @endif>
                                            <i class="bi bi-calendar2-plus"></i>
                                            Daftar Baru
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route("daftarOnline.history") }}"
                                            class="{{ request()->routeIs("daftarOnline.history") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("daftarOnline.history")) aria-current="page" @endif>
                                            <i class="bi bi-clock-history"></i>
                                            Riwayat Pendaftaran
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can("EPASIEN.MENU.JADWAL_DOKTER")
                            <li class="{{ request()->routeIs("jadwalDokter.*") ? "mm-active" : "" }}">
                                <a href="{{ route("jadwalDokter.index") }}"
                                    @if (request()->routeIs("jadwalDokter.*")) aria-current="page" @endif>
                                    <div class="parent-icon"><i class="bi bi-calendar2-week"></i>
                                    </div>
                                    <div class="menu-title">Jadwal Dokter</div>
                                </a>
                            </li>
                        @endcan

                        @can("EPASIEN.MENU.RIWAYAT_PEMERIKSAAN")
                            <li class="{{ request()->routeIs("riwayatPemeriksaan.*") ? "mm-active" : "" }}">
                                <a href="{{ route("riwayatPemeriksaan.index") }}"
                                    @if (request()->routeIs("riwayatPemeriksaan.*")) aria-current="page" @endif>
                                    <div class="parent-icon"><i class="bi bi-clipboard2-pulse"></i>
                                    </div>
                                    <div class="menu-title">Riwayat Pemeriksaan</div>
                                </a>
                            </li>
                        @endcan

                        @can("EPASIEN.MENU.RIWAYAT_MCU")
                            <li class="{{ request()->routeIs("riwayatMcu.*") ? "mm-active" : "" }}">
                                <a href="{{ route("riwayatMcu.index") }}"
                                    @if (request()->routeIs("riwayatMcu.*")) aria-current="page" @endif>
                                    <div class="parent-icon"><i class="bi bi-clipboard2-heart"></i>
                                    </div>
                                    <div class="menu-title">Riwayat MCU</div>
                                </a>
                            </li>
                        @endcan

                        @can("EPASIEN.MENU.SURAT")
                            <li class="{{ request()->routeIs("suratKontrol.*", "suratRujukan.*") ? "mm-active" : "" }}">
                                <a href="javascript:;" class="has-arrow"
                                    aria-expanded="{{ request()->routeIs("suratKontrol.*", "suratRujukan.*") ? "true" : "false" }}">
                                    <div class="parent-icon"><i class="bi bi-file-earmark-medical"></i>
                                    </div>
                                    <div class="menu-title">Surat</div>
                                </a>
                                <ul class="{{ request()->routeIs("suratKontrol.*", "suratRujukan.*") ? "mm-show" : "" }}">
                                    <li>
                                        <a href="{{ route("suratKontrol.index") }}"
                                            class="{{ request()->routeIs("suratKontrol.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("suratKontrol.*")) aria-current="page" @endif>
                                            <i class="bi bi-calendar2-check"></i>
                                            Surat Kontrol
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route("suratRujukan.index") }}"
                                            class="{{ request()->routeIs("suratRujukan.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("suratRujukan.*")) aria-current="page" @endif>
                                            <i class="bi bi-send-check"></i>
                                            Surat Rujukan
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan
                        @can("EPASIEN.MENU.PERMINTAAN_DAN_TINDAKAN")
                            <li class="{{ $requestMenuActive ? "mm-active" : "" }}">
                                <a href="javascript:;" class="has-arrow"
                                    aria-expanded="{{ $requestMenuActive ? "true" : "false" }}">
                                    <div class="parent-icon"><i class="bi bi-clipboard2-plus"></i>
                                    </div>
                                    <div class="menu-title">Permintaan dan Tindakan</div>
                                </a>
                                <ul class="{{ $requestMenuActive ? "mm-show" : "" }}">
                                    <li>
                                        <a href="{{ route("pemeriksaanLaborat.index") }}"
                                            class="{{ request()->routeIs("pemeriksaanLaborat.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("pemeriksaanLaborat.*")) aria-current="page" @endif>
                                            <i class="bi bi-droplet-half"></i>
                                            Pemeriksaan Laborat
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route("pemeriksaanRadiologi.index") }}"
                                            class="{{ request()->routeIs("pemeriksaanRadiologi.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("pemeriksaanRadiologi.*")) aria-current="page" @endif>
                                            <i class="bi bi-radioactive"></i>
                                            Pemeriksaan Radiologi
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route("resepObat.index") }}"
                                            class="{{ request()->routeIs("resepObat.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("resepObat.*")) aria-current="page" @endif>
                                            <i class="bi bi-capsule-pill"></i>
                                            Resep Obat
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route("operasi.index") }}"
                                            class="{{ request()->routeIs("operasi.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("operasi.*")) aria-current="page" @endif>
                                            <i class="bi bi-bandaid"></i>
                                            Operasi
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan
                        @can("EPASIEN.MENU.FASILITAS_TARIF")
                            <li
                                class="{{ request()->routeIs("kamar.*", "laboratorium.*", "poliklinik.*", "radiologi.*") ? "mm-active" : "" }}">
                                <a href="javascript:;" class="has-arrow"
                                    aria-expanded="{{ $facilityMenuActive ? "true" : "false" }}">
                                    <div class="parent-icon"><i class="bi bi-building"></i>
                                    </div>
                                    <div class="menu-title">Fasilitas &amp; Tarif</div>
                                </a>
                                <ul class="{{ $facilityMenuActive ? "mm-show" : "" }}">
                                    <li>
                                        <a href="{{ route("kamar.index") }}"
                                            class="{{ request()->routeIs("kamar.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("kamar.*")) aria-current="page" @endif>
                                            <i class="bi bi-door-open"></i>
                                            Kamar
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route("poliklinik.index") }}"
                                            class="{{ request()->routeIs("poliklinik.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("poliklinik.*")) aria-current="page" @endif>
                                            <i class="bi bi-hospital"></i>
                                            Poliklinik
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route("laboratorium.index") }}"
                                            class="{{ request()->routeIs("laboratorium.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("laboratorium.*")) aria-current="page" @endif>
                                            <i class="bi bi-droplet-half"></i>
                                            Laboratorium
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route("radiologi.index") }}"
                                            class="{{ request()->routeIs("radiologi.*") ? "mm-active" : "" }}"
                                            @if (request()->routeIs("radiologi.*")) aria-current="page" @endif>
                                            <i class="bi bi-radioactive"></i>
                                            Radiologi
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan
                    @endcan

                    <li class="ep-sidebar-empty" role="status">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <strong>Menu tidak ditemukan</strong>
                        <span>Coba gunakan kata kunci lain.</span>
                    </li>
                </ul>
                <!--end navigation-->

                <div class="ep-sidebar-assurance" aria-label="Status keamanan sistem">
                    <span class="ep-sidebar-assurance-icon">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <span class="ep-sidebar-status-dot" aria-hidden="true"></span>
                    </span>
                    <span class="ep-sidebar-assurance-copy">
                        <strong>Sistem terlindungi</strong>
                        <small>Layanan E-Pasien aktif</small>
                    </span>
                </div>
            </div>
        </aside>
        <!--end sidebar -->
