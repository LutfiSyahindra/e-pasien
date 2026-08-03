<div class="promo-empty">
    <span><i class="bi {{ $marketing ? "bi-images" : "bi-gift" }}"></i></span>
    <h3>{{ $marketing ? "Belum ada konten promosi" : "Promo baru sedang disiapkan" }}</h3>
    <p>{{ $marketing ? "Mulai buat kampanye pertama dan bagikan kabar baik kepada pasien." : "Silakan kembali lagi. Kami sedang menyiapkan penawaran kesehatan terbaik untuk Anda." }}</p>
    @if ($marketing)
        <a class="promo-primary-button" href="{{ route("promotions.create") }}"><i class="bi bi-plus-lg"></i>Buat promosi</a>
    @endif
</div>
