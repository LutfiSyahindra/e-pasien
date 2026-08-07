@extends("template.epasien.appPasien")

@section("title", "Pembaca {$promotion->title} - Promosi & Informasi")

@push("style")
    <link href="{{ asset("epasien/assets/css/promotion-premium.css") }}" rel="stylesheet">
@endpush

@section("content")
    <div class="promo-shell promo-viewers-shell">
        <nav class="promo-breadcrumb">
            <a href="{{ route("promotions.index") }}"><i class="bi bi-arrow-left"></i> Kembali ke daftar Promosi &amp; Informasi</a>
        </nav>

        <section class="promo-viewers-panel" aria-labelledby="promo-viewers-title">
            <header class="promo-viewers-header">
                <span class="promo-viewers-header__icon"><i class="bi bi-eye-fill"></i></span>
                <div>
                    <span class="promo-eyebrow">Audiens {{ $promotion->category_label }}</span>
                    <h1 id="promo-viewers-title">Siapa yang sudah melihat?</h1>
                    <p>{{ $promotion->title }}</p>
                </div>
                <span class="promo-viewers-total"><strong>{{ $views->total() }}</strong><small>orang melihat</small></span>
            </header>

            @if ($views->isNotEmpty())
                <div class="promo-viewer-list" role="list">
                    @foreach ($views as $view)
                        <article class="promo-viewer-item" role="listitem">
                            <img src="{{ $view->user->profile_photo_url }}" alt="" loading="lazy">
                            <span class="promo-viewer-item__identity">
                                <strong>{{ $view->user->name }}</strong>
                                <small>{{ $view->user->username ?: ($view->user->email ?: "Pengguna E-Pasien") }}</small>
                            </span>
                            <span class="promo-viewer-item__time">
                                <i class="bi bi-clock"></i>
                                <span><small>Terakhir dilihat</small><strong>{{ $view->viewed_at->copy()->setTimezone(\App\Models\Promotion::TIMEZONE)->locale("id")->translatedFormat("d M Y, H:i") }} WIB</strong></span>
                            </span>
                        </article>
                    @endforeach
                </div>
                <div class="promo-pagination">{{ $views->links() }}</div>
            @else
                <div class="promo-viewers-empty">
                    <span><i class="bi bi-eye-slash"></i></span>
                    <h2>Belum ada yang melihat</h2>
                    <p>Daftar pembaca akan muncul setelah pengguna membuka detail konten ini.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
