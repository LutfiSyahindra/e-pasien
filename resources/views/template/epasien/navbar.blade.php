@php
    $navbarUser = auth()->user();
    $navbarUserName = $navbarUser?->name ?? "Guest";
    $navbarDefaultPhoto = asset("epasien/assets/images/avatars/avatar-patient-default.webp");
    $navbarUserPhoto = $navbarUser?->profile_photo_url ?? $navbarDefaultPhoto;
    $navbarUserRole = $navbarUser ? ($navbarUser->getRoleNames()->implode(", ") ?: "User E-Pasien") : "Guest";
@endphp

<header class="top-header">
    <nav class="navbar navbar-expand ep-premium-navbar" aria-label="Navigasi atas E-Pasien">
        <button type="button" class="mobile-toggle-icon d-xl-none" aria-label="Buka menu utama"
            aria-controls="menu" aria-expanded="false" title="Buka menu utama">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <a class="ep-navbar-context" href="{{ route("dashboard") }}" aria-label="Kembali ke dashboard E-Pasien">
            <span class="ep-navbar-context__icon"><i class="bi bi-heart-pulse-fill" aria-hidden="true"></i></span>
            <span class="ep-navbar-context__copy">
                <small>Portal layanan RS Arsy</small>
                <strong>E-Pasien</strong>
            </span>
            <span class="ep-navbar-live"><i aria-hidden="true"></i> Terhubung</span>
        </a>

        <div class="top-navbar-right ms-auto">
            <ul class="navbar-nav align-items-center">
                @can("EPASIEN.MENU.PASIEN_SERVICE")
                    <li class="nav-item dropdown ep-message-nav">
                        <button class="nav-link dropdown-toggle dropdown-toggle-nocaret ep-navbar-action" type="button"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                            aria-label="Buka pesan Pasien Service"
                            aria-controls="ep-navbar-message-panel" title="Pesan Pasien Service">
                            <span class="messages ep-message-button">
                                <span class="notify-badge" data-message-badge hidden>0</span>
                                <i class="bi bi-chat-heart-fill" aria-hidden="true"></i>
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end ep-message-menu p-0" id="ep-navbar-message-panel"
                            role="dialog" aria-labelledby="ep-navbar-message-title">
                            <span class="ep-navbar-sheet-grabber" aria-hidden="true"></span>
                            <div class="ep-message-header">
                                <span class="ep-message-header__icon" aria-hidden="true"><i class="bi bi-chat-heart-fill"></i></span>
                                <div class="ep-message-header__copy">
                                    <span>Percakapan realtime</span>
                                    <h5 id="ep-navbar-message-title">Pasien Service</h5>
                                    <small data-message-unread-copy>Memuat percakapan...</small>
                                </div>
                                <div class="ep-message-header__actions">
                                    <span class="ep-message-live"><i></i> Aktif</span>
                                    <button class="ep-navbar-sheet-close" type="button" data-navbar-sheet-close
                                        aria-label="Tutup percakapan" title="Tutup">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <label class="ep-message-search">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" placeholder="Cari nama atau topik..." data-message-search
                                    aria-label="Cari percakapan" autocomplete="off">
                            </label>
                            <div class="ep-message-list" data-navbar-message-list aria-live="polite" aria-busy="true">
                                <div class="ep-message-loading"><span></span><span></span><span></span></div>
                            </div>
                            <a class="ep-message-footer" href="{{ route("patientService.index") }}">
                                <span class="ep-message-footer__icon"><i class="bi bi-chat-dots-fill" aria-hidden="true"></i></span>
                                <span><strong>Buka pusat percakapan</strong><small>Lihat dan balas semua pesan</small></span>
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </li>
                @endcan

                <li class="nav-item dropdown ep-notification-nav">
                    <button class="nav-link dropdown-toggle dropdown-toggle-nocaret ep-navbar-action" type="button"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Buka notifikasi"
                        aria-controls="ep-navbar-notification-panel" title="Notifikasi">
                        <span class="notifications ep-notification-bell">
                            <span class="notify-badge" data-notification-badge hidden>0</span>
                            <i class="bi bi-bell-fill" aria-hidden="true"></i>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end ep-notification-menu p-0"
                        id="ep-navbar-notification-panel" role="dialog" aria-labelledby="ep-navbar-notification-title">
                        <span class="ep-navbar-sheet-grabber" aria-hidden="true"></span>
                        <div class="ep-notification-header">
                            <span class="ep-notification-header__icon" aria-hidden="true"><i class="bi bi-bell-fill"></i></span>
                            <div class="ep-notification-header__copy">
                                <span>Kabar terbaru</span>
                                <h5 id="ep-navbar-notification-title">Notifikasi</h5>
                                <small data-notification-unread-copy>Memuat kabar terbaru...</small>
                            </div>
                            <div class="ep-notification-header__actions">
                                <button class="ep-notification-read-all" type="button" data-notification-read-all disabled
                                    aria-label="Tandai semua notifikasi telah dibaca" title="Tandai semua dibaca">
                                    <i class="bi bi-check2-all" aria-hidden="true"></i><span>Tandai dibaca</span>
                                </button>
                                <button class="ep-navbar-sheet-close" type="button" data-navbar-sheet-close
                                    aria-label="Tutup notifikasi" title="Tutup">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="ep-notification-list" data-notification-list aria-live="polite" aria-busy="true">
                            <div class="ep-notification-loading"><span></span><span></span><span></span></div>
                        </div>
                        <div class="ep-notification-footer">
                            <button type="button" data-push-toggle>
                                <i class="bi bi-phone-vibrate" aria-hidden="true"></i>
                                <span>Aktifkan notifikasi perangkat</span>
                                <i class="bi bi-chevron-right ep-notification-footer__arrow" aria-hidden="true"></i>
                            </button>
                            <small data-push-hint>Terima kabar meskipun E-Pasien sedang ditutup.</small>
                        </div>
                    </div>
                </li>

                <li class="ep-navbar-separator" aria-hidden="true"></li>

                <li class="nav-item dropdown ep-user-nav">
                    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret ep-user-trigger" href="#" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="Buka menu akun {{ $navbarUserName }}">
                        <img src="{{ $navbarUserPhoto }}" class="user-img"
                            alt="" width="40" height="40" decoding="async"
                            onerror="this.onerror=null;this.src='{{ $navbarDefaultPhoto }}';">
                        <span class="ep-user-copy">
                            <strong>{{ $navbarUserName }}</strong>
                            <small>{{ $navbarUserRole }}</small>
                        </span>
                        <i class="bi bi-chevron-down ep-user-chevron" aria-hidden="true"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end ep-user-menu p-0">
                        <div class="ep-user-menu__header">
                            <span class="ep-user-menu__avatar">
                                <img src="{{ $navbarUserPhoto }}" alt="Foto {{ $navbarUserName }}" width="54" height="54"
                                    decoding="async" onerror="this.onerror=null;this.src='{{ $navbarDefaultPhoto }}';">
                                <i aria-hidden="true"></i>
                            </span>
                            <span class="ep-user-menu__identity">
                                <small>Akun aktif</small>
                                <strong>{{ $navbarUserName }}</strong>
                                <span>{{ $navbarUserRole }}</span>
                            </span>
                        </div>
                        <div class="ep-user-menu__actions">
                            <a class="dropdown-item" href="{{ route("profile.edit") }}">
                                <span class="ep-user-menu__action-icon"><i class="bi bi-person-fill"></i></span>
                                <span><strong>Profil saya</strong><small>Kelola identitas dan akun</small></span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                            <form method="POST" action="{{ route("logout") }}" data-push-logout>
                                @csrf
                                <button type="submit" class="dropdown-item ep-user-logout">
                                    <span class="ep-user-menu__action-icon"><i class="bi bi-box-arrow-right"></i></span>
                                    <span><strong>Keluar</strong><small>Akhiri sesi dengan aman</small></span>
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                        <div class="ep-user-menu__security">
                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                            <span><strong>Sesi terlindungi</strong><small>Koneksi E-Pasien diamankan</small></span>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <button class="ep-navbar-sheet-backdrop" type="button" data-navbar-sheet-backdrop
        aria-label="Tutup panel navbar" hidden></button>
</header>
