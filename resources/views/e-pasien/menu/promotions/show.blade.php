@extends("template.epasien.appPasien")

@section("title", $promotion->title . " - Promosi & Informasi")

@push("style")
    <link href="{{ asset("epasien/assets/css/promotion-premium.css") }}" rel="stylesheet">
@endpush

@section("content")
    <div class="promo-shell promo-detail-shell">
        <nav class="promo-breadcrumb"><a href="{{ route("promotions.index") }}"><i class="bi bi-arrow-left"></i> Kembali ke Promosi &amp; Informasi</a></nav>
        <article class="promo-detail">
            <div class="promo-detail__image"><img src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}" decoding="async"><span class="promo-detail__badge is-{{ $promotion->category }}"><i class="bi {{ $promotion->category_icon }}"></i> {{ $promotion->category_label }}</span></div>
            <div class="promo-detail__content">
                <span class="promo-eyebrow"><i class="bi {{ $promotion->category_icon }}"></i> {{ $promotion->category === "information" ? "Informasi kesehatan" : "Penawaran kesehatan" }}</span>
                <h1>{{ $promotion->title }}</h1>
                <div class="promo-detail__period"><span><i class="bi bi-calendar-check"></i></span><div><small>Periode tayang</small><strong>{{ $promotion->starts_at->translatedFormat("d F Y, H:i") }} – {{ $promotion->ends_at->translatedFormat("d F Y, H:i") }} WIB</strong></div></div>
                <div class="promo-detail__caption">{!! nl2br(e($promotion->caption)) !!}</div>
                <div class="promo-detail__assurance"><i class="bi bi-shield-check"></i><span><strong>Informasi resmi RS Arsy</strong><small>Hubungi rumah sakit jika Anda memerlukan informasi lebih lanjut.</small></span></div>
                @can("EPASIEN.MENU.PROMOSI.KELOLA")
                    <a class="promo-primary-button" href="{{ route("promotions.edit", $promotion) }}"><i class="bi bi-pencil"></i>Edit konten</a>
                @endcan
            </div>
        </article>
    </div>
@endsection
