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
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret ep-navbar-action" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false" aria-label="Buka pesan Pasien Service"
                            title="Pesan Pasien Service">
                            <span class="messages ep-message-button">
                                <span class="notify-badge" data-message-badge hidden>0</span>
                                <i class="bi bi-chat-heart-fill" aria-hidden="true"></i>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end ep-message-menu p-0">
                            <div class="ep-message-header">
                                <div><span>Percakapan realtime</span><h5>Pasien Service</h5></div>
                                <span class="ep-message-live"><i></i> Aktif</span>
                            </div>
                            <label class="ep-message-search">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" placeholder="Cari percakapan..." data-message-search>
                            </label>
                            <div class="ep-message-list" data-navbar-message-list aria-live="polite">
                                <div class="ep-message-loading"><span></span><span></span><span></span></div>
                            </div>
                            <a class="ep-message-footer" href="{{ route("patientService.index") }}">
                                <span>Lihat semua percakapan</span><i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </li>
                @endcan

                <li class="nav-item dropdown ep-notification-nav">
                    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret ep-navbar-action" href="#" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="Buka notifikasi" title="Notifikasi">
                        <span class="notifications ep-notification-bell">
                            <span class="notify-badge" data-notification-badge hidden>0</span>
                            <i class="bi bi-bell-fill" aria-hidden="true"></i>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end ep-notification-menu p-0">
                        <div class="ep-notification-header">
                            <div><span>Kabar terbaru</span><h5>Notifikasi</h5></div>
                            <button type="button" data-notification-read-all>Tandai dibaca</button>
                        </div>
                        <div class="ep-notification-list" data-notification-list aria-live="polite">
                            <div class="ep-notification-loading"><span></span><span></span><span></span></div>
                        </div>
                        <div class="ep-notification-footer">
                            <button type="button" data-push-toggle>
                                <i class="bi bi-phone-vibrate"></i>
                                <span>Aktifkan notifikasi perangkat</span>
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
                            <form method="POST" action="{{ route("logout") }}">
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
</header>
