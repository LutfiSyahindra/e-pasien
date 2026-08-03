@extends("template.epasien.appPasien")

@section("title", "Promo Sehat - E-Pasien")

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
                    <span class="promo-eyebrow"><i class="bi bi-megaphone-fill"></i> Marketing studio</span>
                    <h1 id="promo-page-title">Hadirkan cerita sehat yang <em>berkesan.</em></h1>
                    <p>Rancang, jadwalkan, dan pantau seluruh konten promosi pasien dari satu ruang kerja.</p>
                    <a class="promo-primary-button" href="{{ route("promotions.create") }}">
                        <i class="bi bi-plus-lg"></i><span>Buat promosi</span>
                    </a>
                </div>
                <div class="promo-hero__visual" aria-hidden="true">
                    <div class="promo-orbit"><i class="bi bi-stars"></i></div>
                    <div class="promo-phone-card">
                        <span class="promo-phone-card__tag">LIVE</span>
                        <i class="bi bi-heart-pulse-fill"></i>
                        <strong>Promo Sehat</strong>
                        <small>Konten tepat, pasien terpikat</small>
                    </div>
                </div>
            </section>

            <section class="promo-stats" aria-label="Ringkasan promosi">
                <article><span class="promo-stat-icon is-indigo"><i class="bi bi-collection"></i></span><div><strong>{{ $summary["total"] }}</strong><span>Total konten</span></div></article>
                <article><span class="promo-stat-icon is-emerald"><i class="bi bi-broadcast-pin"></i></span><div><strong>{{ $summary["active"] }}</strong><span>Sedang tayang</span></div></article>
                <article><span class="promo-stat-icon is-amber"><i class="bi bi-clock-history"></i></span><div><strong>{{ $summary["scheduled"] }}</strong><span>Terjadwal</span></div></article>
                <article><span class="promo-stat-icon is-slate"><i class="bi bi-pencil-square"></i></span><div><strong>{{ $summary["draft"] }}</strong><span>Masih draf</span></div></article>
            </section>

            <section class="promo-workspace" aria-labelledby="content-heading">
                <div class="promo-section-heading">
                    <div><span>Koleksi kampanye</span><h2 id="content-heading">Konten promosi</h2></div>
                    <span class="promo-result-count">{{ $promotions->total() }} konten</span>
                </div>

                <form class="promo-filters" method="GET" action="{{ route("promotions.index") }}">
                    <label class="promo-search-field">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ $filters["q"] }}" placeholder="Cari judul atau caption..." aria-label="Cari promosi">
                    </label>
                    <label class="promo-select-field">
                        <i class="bi bi-funnel"></i>
                        <select name="status" aria-label="Filter status" onchange="this.form.submit()">
                            @foreach (["all" => "Semua status", "active" => "Sedang tayang", "scheduled" => "Terjadwal", "draft" => "Draf", "expired" => "Berakhir", "archived" => "Diarsipkan"] as $value => $label)
                                <option value="{{ $value }}" @selected($filters["status"] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="promo-filter-button" type="submit">Terapkan</button>
                    @if ($filters["q"] !== "" || $filters["status"] !== "all")
                        <a class="promo-reset-button" href="{{ route("promotions.index") }}">Reset</a>
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
                                    <img src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}" loading="lazy">
                                    <span class="promo-status is-{{ $tone }}"><i class="bi bi-circle-fill"></i>{{ $promotion->status_label }}</span>
                                </a>
                                <div class="promo-admin-card__body">
                                    <div class="promo-admin-card__meta"><span><i class="bi bi-hourglass-split"></i>{{ $promotion->duration_label }}</span><span>{{ $promotion->creator?->name ?? "Marketing" }}</span></div>
                                    <h3><a href="{{ route("promotions.show", $promotion) }}">{{ $promotion->title }}</a></h3>
                                    <p>{{ Str::limit($promotion->caption, 115) }}</p>
                                    <div class="promo-schedule-line">
                                        <i class="bi bi-calendar3"></i>
                                        <span><small>Periode tayang</small>{{ $promotion->starts_at->translatedFormat("d M Y, H:i") }} – {{ $promotion->ends_at->translatedFormat("d M Y, H:i") }}</span>
                                    </div>
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
                    <span class="promo-eyebrow"><i class="bi bi-stars"></i> Pilihan spesial untuk Anda</span>
                    <h1 id="promo-page-title">Lebih sehat, lebih hemat, <em>lebih bahagia.</em></h1>
                    <p>Temukan program kesehatan dan penawaran terbaik RS Arsy dalam satu tempat.</p>
                    <button class="promo-notification-button" type="button" data-push-toggle>
                        <i class="bi bi-bell"></i><span>Aktifkan notifikasi promo</span>
                    </button>
                    <small class="promo-push-hint" data-push-hint>Jangan lewatkan promo terbaru dari kami.</small>
                </div>
                <div class="promo-hero__patient-art" aria-hidden="true">
                    <div class="promo-heart-ring"><i class="bi bi-heart-pulse-fill"></i></div>
                    <span class="promo-floating-pill pill-one"><i class="bi bi-shield-check"></i> Terpercaya</span>
                    <span class="promo-floating-pill pill-two"><i class="bi bi-gift"></i> Pilihan terbaik</span>
                </div>
            </section>

            @if ($featured)
                <section class="promo-featured" aria-labelledby="featured-heading">
                    <div class="promo-section-heading">
                        <div><span>Sedang berlangsung</span><h2 id="featured-heading">Promo unggulan</h2></div>
                    </div>
                    <a class="promo-featured-card" href="{{ route("promotions.show", $featured) }}">
                        <img src="{{ $featured->image_url }}" alt="{{ $featured->title }}">
                        <span class="promo-featured-card__shade"></span>
                        <div class="promo-featured-card__content">
                            <span><i class="bi bi-lightning-charge-fill"></i> Promo pilihan</span>
                            <h3>{{ $featured->title }}</h3>
                            <p>{{ Str::limit($featured->caption, 150) }}</p>
                            <strong>Lihat selengkapnya <i class="bi bi-arrow-right"></i></strong>
                        </div>
                        <div class="promo-featured-card__time"><small>Berakhir</small><strong>{{ $featured->ends_at->translatedFormat("d M Y") }}</strong></div>
                    </a>
                </section>
            @endif

            <section class="promo-patient-list" aria-labelledby="all-promos-heading">
                <div class="promo-section-heading">
                    <div><span>Jelajahi manfaat</span><h2 id="all-promos-heading">Semua Promo Sehat</h2></div>
                    @if ($promotions->total())<span class="promo-result-count">{{ $promotions->total() }} promo aktif</span>@endif
                </div>
                @if ($promotions->isNotEmpty())
                    <div class="promo-patient-grid">
                        @foreach ($promotions as $promotion)
                            <a class="promo-patient-card" href="{{ route("promotions.show", $promotion) }}">
                                <span class="promo-patient-card__media">
                                    <img src="{{ $promotion->image_url }}" alt="{{ $promotion->title }}" loading="lazy">
                                    <span class="promo-patient-card__badge"><i class="bi bi-gift-fill"></i> Promo</span>
                                </span>
                                <span class="promo-patient-card__body">
                                    <small><i class="bi bi-clock"></i> Hingga {{ $promotion->ends_at->translatedFormat("d M Y") }}</small>
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

@push("script")
    <script src="{{ asset("epasien/assets/js/promotion-page.js") }}"></script>
@endpush
