<!-- premium mobile navigation start -->
<aside id="side-bar" class="side-bar header-two premium-mobile-nav" aria-label="Navigasi seluler"
    aria-hidden="true" inert>
    <div class="premium-mobile-nav__header">
        <a href="{{ route('landingPage.index') }}" class="premium-mobile-nav__brand" aria-label="Beranda RS ARSY">
            <span class="premium-mobile-nav__brand-mark">
                <img src="{{ asset('landing/assets/imagesArsy/logoarsy.png') }}" alt="" width="48">
            </span>
            <span>
                <strong>RS ARSY</strong>
                <small>Pelayanan Kesehatan</small>
            </span>
        </a>

        <button type="button" class="close-icon-menu premium-mobile-nav__close" aria-label="Tutup menu navigasi">
            <i class="far fa-times" aria-hidden="true"></i>
        </button>
    </div>

    <div class="mobile-menu-main premium-mobile-nav__body">
        <div class="premium-mobile-nav__intro">
            <span>MENU UTAMA</span>
            <p>Akses cepat layanan dan informasi RS ARSY.</p>
        </div>

        <nav class="nav-main mainmenu-nav onepage" aria-label="Navigasi utama seluler">
            <ul class="mainmenu metismenu" id="mobile-menu-active">
                <li><a href="{{ route('landingPage.index') }}" class="main is-active" aria-current="page"><span>01</span>Beranda<i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                <li><a href="{{ route('landingPage.index') }}#aplikasi" class="main"><span>02</span>Aplikasi E-Pasien<i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                <li><a href="{{ route('landingPage.index') }}#layanan" class="main"><span>03</span>Layanan<i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                <li><a href="{{ route('landingPage.index') }}#poli-spesialis" class="main"><span>04</span>Poli Spesialis<i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                <li><a href="{{ route('landingPage.index') }}#jadwal-praktik" class="main"><span>05</span>Jadwal Dokter<i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                <li><a href="{{ route('landingPage.index') }}#dokter-spesialis" class="main"><span>06</span>Dokter Spesialis<i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
                <li><a href="{{ route('landingPage.index') }}#informasi-kontak" class="main"><span>07</span>Kontak<i class="fa-regular fa-arrow-right" aria-hidden="true"></i></a></li>
            </ul>
        </nav>

        <div class="buttons-area premium-mobile-nav__action">
            <a href="{{ route('login') }}" class="rts-btn btn-primary">
                <i class="fa-regular fa-user" aria-hidden="true"></i>
                <span>Login Portal Pasien</span>
                <i class="fa-regular fa-arrow-up-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</aside>
<!-- premium mobile navigation end -->
