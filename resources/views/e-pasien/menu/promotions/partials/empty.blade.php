<div class="promo-empty">
    <span><i class="bi {{ $marketing ? "bi-images" : "bi-gift" }}"></i></span>
    <h3>{{ $marketing ? "Belum ada promosi atau informasi" : "Konten baru sedang disiapkan" }}</h3>
    <p>{{ $marketing ? "Mulai buat konten pertama dan bagikan kabar baik kepada pasien." : "Silakan kembali lagi. Kami sedang menyiapkan promosi dan informasi terbaru untuk Anda." }}</p>
    @if ($marketing)
        <a class="promo-primary-button" href="{{ route("promotions.create") }}"><i class="bi bi-plus-lg"></i>Buat konten</a>
    @endif
</div>
