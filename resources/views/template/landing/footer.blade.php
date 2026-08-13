<footer class="landing-footer" aria-label="Informasi RS ARSY">
    <div class="container landing-footer__container">
        <div class="landing-footer__main">
            <a href="{{ route('landingPage.index') }}" class="landing-footer__brand"
                aria-label="Kembali ke beranda RS ARSY">
                <span class="landing-footer__logo">
                    <img src="{{ asset('landing/assets/imagesArsy/logoarsy.png') }}" alt="" width="58" height="58">
                </span>
                <span>
                    <strong>RS ARSY</strong>
                    <small>RS Abdurrahman Syamsuri</small>
                </span>
            </a>

            <p class="landing-footer__summary">
                Pelayanan kesehatan yang Islami, bermanfaat, akurat, dan nyaman untuk Anda dan keluarga.
            </p>

            <div class="landing-footer__actions">
                <a href="tel:+6281232870119" aria-label="Hubungi Call Centre di 081232870119">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    <span><small>Call Centre</small>081232870119</span>
                </a>
                <a href="https://www.instagram.com/rsarsy_official/" target="_blank" rel="noopener noreferrer"
                    aria-label="Kunjungi Instagram RS ARSY">
                    <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                    <span><small>Instagram</small>@rsarsy_official</span>
                </a>
            </div>
        </div>

        <div class="landing-footer__bottom">
            <p>&copy; {{ now()->year }} RS Abdurrahman Syamsuri.</p>
            <a href="{{ route('landingPage.index') }}#top">
                Kembali ke atas
                <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</footer>
