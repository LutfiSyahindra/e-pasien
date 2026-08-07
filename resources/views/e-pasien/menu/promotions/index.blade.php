@extends("template.epasien.appPasien")

@section("title", "Promosi & Informasi - E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/promotion-premium.css") }}" rel="stylesheet">
@endpush

@section("content")
    <div class="promo-shell">
        @if (session("success"))
            <div class="promo-alert" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>{{ session("success") }}</span>
                <button type="button" data-bs-dismiss="alert" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
            </div>
        @endif

        @if ($canManage)
            <section class="promo-hero promo-hero--marketing" aria-labelledby="promo-page-title">
                <div class="promo-hero__glow promo-hero__glow--one"></div>
                <div class="promo-hero__glow promo-hero__glow--two"></div>
                <div class="promo-hero__content">
                    <span class="promo-eyebrow"><i class="bi bi-megaphone-fill"></i> Pusat publikasi</span>
                    <h1 id="promo-page-title">Bagikan kabar sehat yang <em>bermanfaat.</em></h1>
                    <p>Rancang, jadwalkan, dan pantau seluruh konten promosi dan informasi pasien dari satu ruang kerja.</p>
                    <div class="promo-hero-actions">
                        <a class="promo-primary-button" href="{{ route("promotions.create") }}">
                            <i class="bi bi-plus-lg"></i><span>Buat konten</span>
                        </a>
                        <a class="promo-secondary-button" href="{{ route("promotions.configuration.edit") }}">
                            <i class="bi bi-sliders"></i><span>Konfigurasi</span>
                        </a>
                    </div>
                </div>
                <div class="promo-hero__visual" aria-hidden="true">
                    <div class="promo-orbit"><i class="bi bi-stars"></i></div>
                    <div class="promo-phone-card">
                        <span class="promo-phone-card__tag">LIVE</span>
                        <i class="bi bi-heart-pulse-fill"></i>
                        <strong>Promosi &amp; Informasi</strong>
                        <small>Kabar tepat, pasien terhubung</small>
                    </div>
                </div>
            </section>

            <section class="promo-stats" aria-label="Ringkasan konten">
                <article><span class="promo-stat-icon is-indigo"><i class="bi bi-collection"></i></span><div><strong>{{ $summary["total"] }}</strong><span>Total konten</span></div></article>
                <article><span class="promo-stat-icon is-emerald"><i class="bi bi-broadcast-pin"></i></span><div><strong>{{ $summary["active"] }}</strong><span>Sedang tayang</span></div></article>
                <article><span class="promo-stat-icon is-amber"><i class="bi bi-clock-history"></i></span><div><strong>{{ $summary["scheduled"] }}</strong><span>Terjadwal</span></div></article>
                <article><span class="promo-stat-icon is-slate"><i class="bi bi-pencil-square"></i></span><div><strong>{{ $summary["draft"] }}</strong><span>Masih draf</span></div></article>
            </section>

            <section class="promo-workspace" aria-labelledby="content-heading">
                <div class="promo-section-heading">
                    <div><span>Pusat informasi</span><h2 id="content-heading">Konten promosi &amp; informasi</h2></div>
                    <span class="promo-result-count">{{ $promotions->total() }} konten</span>
                </div>

                <form class="promo-filters" method="GET" action="{{ route("promotions.index") }}">
                    <label class="promo-search-field">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ $filters["q"] }}" placeholder="Cari judul atau caption..." aria-label="Cari konten">
                    </label>
                    <label class="promo-select-field">
                        <i class="bi bi-tags"></i>
                        <select name="category" aria-label="Filter kategori" onchange="this.form.submit()">
                            @foreach (["all" => "Semua kategori", "promotion" => "Promosi", "information" => "Informasi"] as $value => $label)
                                <option value="{{ $value }}" @selected($filters["category"] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="promo-select-field">
                        <i class="bi bi-funnel"></i>
                        <select name="status" aria-label="Filter status" onchange="this.form.submit()">
                            @foreach (["all" => "Semua status", "active" => "Sedang tayang", "scheduled" => "Terjadwal", "draft" => "Draf", "archived" => "Diarsipkan"] as $value => $label)
                                <option value="{{ $value }}" @selected($filters["status"] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="promo-filter-button" type="submit"><i class="bi bi-check2"></i>Terapkan</button>
                    @if ($filters["q"] !== "" || $filters["status"] !== "all" || $filters["category"] !== "all")
                        <a class="promo-reset-button" href="{{ route("promotions.index") }}"><i class="bi bi-arrow-counterclockwise"></i>Reset</a>
                    @endif
                </form>

                @if ($promotions->isNotEmpty())
                    <div class="promo-management-grid">
                        @foreach ($promotions as $promotion)
                            @php
                                $tone = match (true) {
                                    $promotion->status === "archived" => "archived",
                                    $promotion->status === "draft" => "draft",
                                    $promotion->starts_at->isFuture() => "scheduled",
                                    $promotion->ends_at->isPast() => "expired",
                                    default => "active",
                                };
                            @endphp
                            <article class="promo-admin-card">
                                <a class="promo-admin-card__media" href="{{ route("promotions.show", $promotion) }}">
                                    <img src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}" loading="lazy" decoding="async">
                                    <span class="promo-status is-{{ $tone }}"><i class="bi bi-circle-fill"></i>{{ $promotion->status_label }}</span>
                                    <span class="promo-category-badge is-{{ $promotion->category }}"><i class="bi {{ $promotion->category_icon }}"></i>{{ $promotion->category_label }}</span>
                                </a>
                                <div class="promo-admin-card__body">
                                    <div class="promo-admin-card__meta"><span><i class="bi bi-hourglass-split"></i>{{ $promotion->duration_label }}</span><span>{{ $promotion->creator?->name ?? "Marketing" }}</span></div>
                                    <h3><a href="{{ route("promotions.show", $promotion) }}">{{ $promotion->title }}</a></h3>
                                    <p>{{ Str::limit($promotion->caption, 115) }}</p>
                                    <div class="promo-schedule-line">
                                        <i class="bi bi-calendar3"></i>
                                        <span><small>Periode tayang (WIB)</small>{{ $promotion->starts_at_wib->translatedFormat("d M Y, H:i") }} – {{ $promotion->ends_at_wib->translatedFormat("d M Y, H:i") }}</span>
                                    </div>
                                    <a class="promo-viewer-summary" href="{{ route("promotions.viewers", $promotion) }}">
                                        <i class="bi bi-eye"></i>
                                        <span><strong>{{ $promotion->views_count }} orang melihat</strong><small>Lihat siapa saja yang sudah membuka konten</small></span>
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                                <div class="promo-card-actions">
                                    <a href="{{ route("promotions.edit", $promotion) }}"><i class="bi bi-pencil"></i><span>Edit</span></a>
                                    @if ($promotion->status === "archived")
                                        <form method="POST" action="{{ route("promotions.restore", $promotion) }}">@csrf @method("PATCH")<button type="submit"><i class="bi bi-arrow-counterclockwise"></i><span>Pulihkan</span></button></form>
                                    @else
                                        <form method="POST" action="{{ route("promotions.archive", $promotion) }}">@csrf @method("PATCH")<button type="submit"><i class="bi bi-archive"></i><span>Arsipkan</span></button></form>
                                    @endif
                                    <form method="POST" action="{{ route("promotions.destroy", $promotion) }}" data-promo-delete>
                                        @csrf @method("DELETE")
                                        <button class="is-danger" type="submit"><i class="bi bi-trash3"></i><span>Hapus</span></button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="promo-pagination">{{ $promotions->links() }}</div>
                @else
                    @include("e-pasien.menu.promotions.partials.empty", ["marketing" => true])
                @endif
            </section>
        @else
            <section class="promo-hero promo-hero--patient" aria-labelledby="promo-page-title">
                <div class="promo-hero__glow promo-hero__glow--one"></div>
                <div class="promo-hero__content">
                    <span class="promo-eyebrow"><i class="bi bi-stars"></i> Kabar kesehatan untuk Anda</span>
                    <h1 id="promo-page-title">Promosi dan informasi kesehatan <em>terbaru.</em></h1>
                    <p>Temukan informasi, program kesehatan, dan penawaran terbaru RS Arsy dalam satu tempat.</p>
                    <button class="promo-notification-button" type="button" data-push-toggle>
                        <i class="bi bi-bell"></i><span>Aktifkan notifikasi</span>
                    </button>
                    <small class="promo-push-hint" data-push-hint>Jangan lewatkan promosi dan informasi terbaru dari kami.</small>
                </div>
                <div class="promo-hero__patient-art" aria-hidden="true">
                    <div class="promo-heart-ring"><i class="bi bi-heart-pulse-fill"></i></div>
                    <span class="promo-floating-pill pill-one"><i class="bi bi-shield-check"></i> Terpercaya</span>
                    <span class="promo-floating-pill pill-two"><i class="bi bi-gift"></i> Pilihan terbaik</span>
                </div>
            </section>

            <nav class="promo-category-tabs" aria-label="Filter kategori konten">
                <a class="{{ $filters["category"] === "all" ? "is-active" : "" }}" href="{{ route("promotions.index") }}" @if ($filters["category"] === "all") aria-current="page" @endif><i class="bi bi-grid-fill"></i>Semua</a>
                <a class="{{ $filters["category"] === "promotion" ? "is-active" : "" }}" href="{{ route("promotions.index", ["category" => "promotion"]) }}" @if ($filters["category"] === "promotion") aria-current="page" @endif><i class="bi bi-megaphone-fill"></i>Promosi</a>
                <a class="{{ $filters["category"] === "information" ? "is-active" : "" }}" href="{{ route("promotions.index", ["category" => "information"]) }}" @if ($filters["category"] === "information") aria-current="page" @endif><i class="bi bi-info-circle-fill"></i>Informasi</a>
            </nav>

            @if ($featured)
                <section class="promo-featured" aria-labelledby="featured-heading">
                    <div class="promo-section-heading">
                        <div><span>Sedang berlangsung</span><h2 id="featured-heading">Konten unggulan</h2></div>
                    </div>
                    <a class="promo-featured-card" href="{{ route("promotions.show", $featured) }}">
                        <img src="{{ $featured->image_url }}" alt="{{ $featured->title }}" decoding="async">
                        <span class="promo-featured-card__shade"></span>
                        <div class="promo-featured-card__content">
                            <span class="is-{{ $featured->category }}"><i class="bi {{ $featured->category_icon }}"></i> {{ $featured->category_label }}</span>
                            <h3>{{ $featured->title }}</h3>
                            <p>{{ Str::limit($featured->caption, 150) }}</p>
                            <strong>Lihat selengkapnya <i class="bi bi-arrow-right"></i></strong>
                        </div>
                    </a>
                </section>
            @endif

            <section class="promo-patient-list" aria-labelledby="all-promos-heading">
                <div class="promo-section-heading">
                    <div><span>Jelajahi kabar terbaru</span><h2 id="all-promos-heading">{{ match ($filters["category"]) { "promotion" => "Promosi", "information" => "Informasi", default => "Semua Promosi & Informasi" } }}</h2></div>
                    @if ($promotions->total())<span class="promo-result-count">{{ $promotions->total() }} konten aktif</span>@endif
                </div>
                @if ($promotions->isNotEmpty())
                    <div class="promo-patient-grid">
                        @foreach ($promotions as $promotion)
                            <a class="promo-patient-card is-{{ $promotion->category }}" href="{{ route("promotions.show", $promotion) }}">
                                <span class="promo-patient-card__media">
                                    <img src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}" loading="lazy" decoding="async">
                                    <span class="promo-patient-card__badge is-{{ $promotion->category }}"><i class="bi {{ $promotion->category_icon }}"></i> {{ $promotion->category_label }}</span>
                                </span>
                                <span class="promo-patient-card__body">
                                    <strong>{{ $promotion->title }}</strong>
                                    <span>{{ Str::limit($promotion->caption, 96) }}</span>
                                    <em>Lihat detail <i class="bi bi-arrow-up-right"></i></em>
                                </span>
                            </a>
                        @endforeach
                    </div>
                    <div class="promo-pagination">{{ $promotions->links() }}</div>
                @else
                    @include("e-pasien.menu.promotions.partials.empty", ["marketing" => false])
                @endif
            </section>
        @endif
    </div>
@endsection

@if ($canManage)
    @push("script")
        <script
            src="{{ asset("epasien/assets/js/promotion-page.js") }}"
            data-premium-css="{{ asset("epasien/assets/css/sweetalert-premium.css") }}"
        ></script>
    @endpush
@endif
