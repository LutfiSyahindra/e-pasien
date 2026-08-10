@extends("template.epasien.appPasien")

@section("title", $promotion->title . " - Promosi & Informasi")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/promotion-premium.css") }}" rel="stylesheet">
@endpush

@section("content")
    <div class="promo-shell promo-detail-shell">
        <nav class="promo-breadcrumb"><a href="{{ route("promotions.index") }}"><i class="bi bi-arrow-left"></i> Kembali ke Promosi &amp; Informasi</a></nav>
        <article class="promo-detail">
            <button class="promo-detail__image" type="button" data-promo-image-open aria-label="Lihat gambar {{ $promotion->title }} dalam ukuran penuh">
                <img src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}" decoding="async">
                <span class="promo-detail__badge is-{{ $promotion->category }}"><i class="bi {{ $promotion->category_icon }}"></i> {{ $promotion->category_label }}</span>
                <span class="promo-detail__zoom"><i class="bi bi-arrows-fullscreen"></i> Lihat gambar penuh</span>
            </button>
            <div class="promo-detail__content">
                <span class="promo-eyebrow"><i class="bi {{ $promotion->category_icon }}"></i> {{ $promotion->category === "information" ? "Informasi kesehatan" : "Penawaran kesehatan" }}</span>
                <h1>{{ $promotion->title }}</h1>
                @can("EPASIEN.MENU.PROMOSI.KELOLA")
                    <div class="promo-detail__period"><span><i class="bi bi-calendar-check"></i></span><div><small>Periode tayang</small><strong>{{ $promotion->starts_at_wib->translatedFormat("d F Y, H:i") }} – {{ $promotion->ends_at_wib->translatedFormat("d F Y, H:i") }} WIB</strong></div></div>
                @endcan
                <div class="promo-detail__caption">{!! nl2br(e($promotion->caption)) !!}</div>
                <div class="promo-detail__assurance"><i class="bi bi-shield-check"></i><span><strong>Informasi resmi RS Arsy</strong><small>Hubungi rumah sakit jika Anda memerlukan informasi lebih lanjut.</small></span></div>
                @can("EPASIEN.MENU.PROMOSI.KELOLA")
                    <a class="promo-primary-button" href="{{ route("promotions.edit", $promotion) }}"><i class="bi bi-pencil"></i>Edit konten</a>
                @endcan
            </div>
        </article>
    </div>

    <dialog class="promo-image-viewer" data-promo-image-viewer aria-labelledby="promo-image-viewer-title">
        <div class="promo-image-viewer__toolbar">
            <strong id="promo-image-viewer-title">{{ $promotion->title }}</strong>
            <button type="button" data-promo-image-close aria-label="Tutup gambar penuh"><i class="bi bi-x-lg"></i></button>
        </div>
        <figure class="promo-image-viewer__stage">
            <img src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}">
            <figcaption>{{ $promotion->title }}</figcaption>
        </figure>
    </dialog>
@endsection

@push("script")
    <script src="{{ versioned_asset("epasien/assets/js/promotion-image-viewer.js") }}"></script>
@endpush
