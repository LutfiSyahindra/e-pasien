<!-- premium navigation start -->
<header class="header-one header--sticky premium-navbar">
    <div class="container-full-header premium-navbar__container">
        <div class="header-wrapper-1 premium-navbar__surface">
            <div class="logo-area-start premium-navbar__left">
                <a href="{{ route('landingPage.index') }}" class="logo premium-navbar__brand"
                    aria-label="Beranda RS ARSY">
                    <span class="premium-navbar__brand-mark">
                        <img src="{{ asset('landing/assets/imagesArsy/logoarsy.png') }}" alt="" width="56">
                    </span>
                    <span class="premium-navbar__brand-copy">
                        <strong>RS ARSY</strong>
                        <small>Pelayanan Kesehatan</small>
                    </span>
                </a>

                <nav class="nav-area premium-navbar__nav" aria-label="Navigasi utama">
                    <ul>
                        <li class="main-nav">
                            <a href="{{ route('landingPage.index') }}" class="is-active" aria-current="page">Beranda</a>
                        </li>
                        <li class="main-nav">
                            <a href="{{ route('landingPage.index') }}#layanan">Layanan</a>
                        </li>
                        <li class="main-nav">
                            <a href="{{ route('landingPage.index') }}#aplikasi">Aplikasi</a>
                        </li>
                        <li class="main-nav">
                            <a href="{{ route('landingPage.index') }}#poli-spesialis">Poli Spesialis</a>
                        </li>
                        <li class="main-nav">
                            <a href="{{ route('landingPage.index') }}#jadwal-praktik">Jadwal Dokter</a>
                        </li>
                        <li class="main-nav">
                            <a href="{{ route('landingPage.index') }}#dokter-spesialis">Dokter Spesialis</a>
                        </li>
                        <li class="main-nav">
                            <a href="{{ route('landingPage.index') }}#informasi-kontak">Kontak</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="header-right premium-navbar__actions">
                <div class="premium-navbar__trust" aria-label="Portal pasien aman">
                    <span><i class="fa-regular fa-shield-check" aria-hidden="true"></i></span>
                    <small>Portal aman</small>
                </div>

                <a href="{{ route('login') }}" class="rts-btn btn-primary premium-navbar__login">
                    <span class="premium-navbar__login-icon">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                    </span>
                    <span>Login</span>
                    <i class="fa-regular fa-arrow-up-right premium-navbar__login-arrow" aria-hidden="true"></i>
                </a>

                <button type="button" class="menu-btn premium-navbar__menu" id="menu-btn"
                    aria-label="Buka menu navigasi" aria-controls="side-bar" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>
</header>
<!-- premium navigation end -->
