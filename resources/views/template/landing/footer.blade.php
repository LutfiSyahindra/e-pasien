<footer class="landing-footer" aria-label="Informasi {{ $landingContent['identity']['short_name'] }}">
    <div class="container landing-footer__container">
        <div class="landing-footer__main">
            <a href="{{ route('landingPage.index') }}" class="landing-footer__brand"
                aria-label="Kembali ke beranda {{ $landingContent['identity']['short_name'] }}">
                <span class="landing-footer__logo">
                    <img src="{{ $landingImages['logo'] }}" alt="" width="58" height="58">
                </span>
                <span>
                    <strong>{{ $landingContent['identity']['short_name'] }}</strong>
                    <small>{{ $landingContent['identity']['full_name'] }}</small>
                </span>
            </a>

            <p class="landing-footer__summary">
                {{ $landingContent['identity']['footer_description'] }}
            </p>

            <div class="landing-footer__actions">
                <a href="{{ $hospitalContacts[0]['href'] }}" aria-label="Hubungi {{ $hospitalContacts[0]['label'] }} di {{ $hospitalContacts[0]['number'] }}">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    <span><small>{{ $hospitalContacts[0]['label'] }}</small>{{ $hospitalContacts[0]['number'] }}</span>
                </a>
                <a href="{{ $landingContent['contact']['social_url'] }}" target="_blank" rel="noopener noreferrer"
                    aria-label="Kunjungi {{ $landingContent['contact']['social_label'] }} {{ $landingContent['identity']['short_name'] }}">
                    <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                    <span><small>{{ $landingContent['contact']['social_label'] }}</small>{{ $landingContent['contact']['social_handle'] }}</span>
                </a>
            </div>
        </div>

        <div class="landing-footer__bottom">
            <p>&copy; {{ now()->year }} {{ $landingContent['identity']['full_name'] }}.</p>
            <a href="{{ route('landingPage.index') }}#top">
                Kembali ke atas
                <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</footer>
