@extends("template.epasien.appPasien")

@section("title", "Poliklinik | Fasilitas & Tarif")

@push("style")
    <link href="{{ asset("epasien/assets/css/poliklinik-tarif.css") }}"
        rel="stylesheet" />
@endpush

@section("content")
    @php
        $hasActiveFilters = $search !== "";
    @endphp

    <main class="polyclinic-rate-page">
        <nav class="polyclinic-rate-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Fasilitas &amp; Tarif</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span class="active">Poliklinik</span>
        </nav>

        <section class="polyclinic-rate-hero">
            <div class="polyclinic-rate-hero-copy">
                <span class="polyclinic-rate-eyebrow">
                    <i class="bi bi-hospital"></i>
                    Informasi poli spesialis
                </span>
                <h1>Poliklinik &amp; Tarif</h1>
                <p>
                    Temukan poliklinik spesialis yang tersedia, lalu
                    lihat perkiraan biaya registrasi sebelum berkunjung.
                </p>
                <div class="polyclinic-rate-hero-actions">
                    <span>
                        <i class="bi bi-arrow-repeat"></i>
                        Data aktif diperbarui saat halaman dibuka
                    </span>
                    <a href="{{ route("daftarOnline.index") }}">
                        <i class="bi bi-calendar2-plus"></i>
                        Lihat jadwal &amp; daftar
                    </a>
                </div>
            </div>

            <div class="polyclinic-rate-hero-total"
                aria-label="Jumlah poliklinik spesialis aktif">
                <span class="polyclinic-rate-hero-icon">
                    <i class="bi bi-building"></i>
                </span>
                <span>
                    <small>Poliklinik spesialis</small>
                    <strong>{{ number_format($summary["total"], 0, ",", ".") }}</strong>
                    <em>poli tersedia</em>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="polyclinic-rate-alert" role="alert">
                <i class="bi bi-cloud-slash"></i>
                <div>
                    <strong>Data belum dapat ditampilkan</strong>
                    <span>{{ $connectionError }}</span>
                </div>
            </div>
        @endif

        <section class="polyclinic-rate-overview"
            aria-label="Ringkasan poliklinik">
            <div>
                <span><i class="bi bi-hospital"></i></span>
                <div>
                    <small>Poli spesialis aktif</small>
                    <strong>{{ number_format($summary["total"], 0, ",", ".") }}</strong>
                </div>
            </div>
            <div>
                <span><i class="bi bi-tags"></i></span>
                <div>
                    <small>Tarif tersedia</small>
                    <strong>{{ number_format($summary["priced"], 0, ",", ".") }} poli</strong>
                </div>
            </div>
            <div>
                <span><i class="bi bi-wallet2"></i></span>
                <div>
                    <small>Rentang registrasi</small>
                    <strong>
                        {{ $summary["minimum_formatted"] }}
                        @if ($summary["maximum"] > $summary["minimum"])
                            – {{ $summary["maximum_formatted"] }}
                        @endif
                    </strong>
                </div>
            </div>
        </section>

        <section class="polyclinic-rate-content">
            <div class="polyclinic-rate-content-heading">
                <div>
                    <span>Daftar poli spesialis</span>
                    <h2>Cari poliklinik yang Anda perlukan</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("poliklinik.index") }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset pencarian</span>
                    </a>
                @endif
            </div>

            <form action="{{ route("poliklinik.index") }}" method="GET"
                class="polyclinic-rate-search">
                <label>
                    <span>Cari poliklinik spesialis</span>
                    <span class="polyclinic-rate-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q"
                            value="{{ old("q", $search) }}"
                            placeholder="Contoh: anak, mata, atau penyakit dalam"
                            maxlength="80" autocomplete="off"
                            inputmode="search" enterkeyhint="search"
                            @disabled($connectionError)>
                    </span>
                    @error("q")
                        <small class="polyclinic-rate-field-error">{{ $message }}</small>
                    @enderror
                </label>

                <button type="submit" @disabled($connectionError)>
                    <i class="bi bi-search"></i>
                    <span>Tampilkan</span>
                </button>
            </form>

            <div class="polyclinic-rate-results-heading" aria-live="polite">
                <div>
                    <h2>
                        {{ $search !== "" ? "Hasil pencarian" : "Semua poliklinik spesialis" }}
                    </h2>
                    <p>
                        {{ $search !== ""
                            ? "Pencarian untuk “".$search."”"
                            : "Hanya poliklinik spesialis berstatus aktif" }}
                    </p>
                </div>
                <span>
                    {{ number_format($clinics->total(), 0, ",", ".") }} hasil
                </span>
            </div>

            @if ($connectionError)
                <div class="polyclinic-rate-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Informasi poliklinik belum tersedia</strong>
                    <p>Silakan coba muat ulang halaman beberapa saat lagi.</p>
                </div>
            @elseif ($clinics->count() === 0)
                <div class="polyclinic-rate-empty">
                    <span><i class="bi bi-search"></i></span>
                    <strong>
                        {{ $hasActiveFilters
                            ? "Poliklinik tidak ditemukan"
                            : "Belum ada poliklinik spesialis aktif" }}
                    </strong>
                    <p>
                        {{ $hasActiveFilters
                            ? "Periksa ejaan atau coba kata kunci yang lebih singkat."
                            : "Informasi poliklinik akan tampil setelah datanya tersedia." }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("poliklinik.index") }}">
                            Lihat semua poli spesialis
                        </a>
                    @endif
                </div>
            @else
                <div class="polyclinic-rate-grid">
                    @foreach ($clinics as $clinic)
                        <article class="polyclinic-rate-card">
                            <div class="polyclinic-rate-card-top">
                                <span class="polyclinic-rate-status">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Aktif
                                </span>
                                <span class="polyclinic-rate-code">
                                    {{ $clinic["code"] }}
                                </span>
                            </div>

                            <div class="polyclinic-rate-card-main">
                                <span class="polyclinic-rate-card-icon">
                                    <i class="bi {{ $clinic["icon"] }}"></i>
                                </span>
                                <div>
                                    <small>Poliklinik spesialis</small>
                                    <h3>{{ $clinic["name"] }}</h3>
                                    <span>
                                        <i class="bi bi-geo-alt"></i>
                                        Rumah Sakit ARSY
                                    </span>
                                </div>
                            </div>

                            <div class="polyclinic-rate-fees">
                                <div @class([
                                    "polyclinic-rate-fee",
                                    "is-unavailable" => !$clinic["new_fee_available"],
                                ])>
                                    <span><i class="bi bi-person-plus"></i></span>
                                    <div>
                                        <small>Registrasi pasien baru</small>
                                        <strong>{{ $clinic["new_fee_formatted"] }}</strong>
                                    </div>
                                </div>
                                <div @class([
                                    "polyclinic-rate-fee",
                                    "is-unavailable" => !$clinic["returning_fee_available"],
                                ])>
                                    <span><i class="bi bi-person-check"></i></span>
                                    <div>
                                        <small>Registrasi pasien lama</small>
                                        <strong>{{ $clinic["returning_fee_formatted"] }}</strong>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($clinics->hasPages())
                    <nav class="polyclinic-rate-pagination"
                        aria-label="Navigasi daftar poliklinik">
                        @if ($clinics->previousPageUrl())
                            <a href="{{ $clinics->previousPageUrl() }}" rel="prev">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </a>
                        @else
                            <span class="disabled">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </span>
                        @endif

                        <span class="polyclinic-rate-page-count">
                            <small>Halaman</small>
                            <strong>
                                {{ $clinics->currentPage() }} / {{ $clinics->lastPage() }}
                            </strong>
                        </span>

                        @if ($clinics->nextPageUrl())
                            <a href="{{ $clinics->nextPageUrl() }}" rel="next">
                                <span>Berikutnya</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        @else
                            <span class="disabled">
                                <span>Berikutnya</span>
                                <i class="bi bi-chevron-right"></i>
                            </span>
                        @endif
                    </nav>
                @endif
            @endif
        </section>

        <aside class="polyclinic-rate-disclaimer">
            <i class="bi bi-info-circle-fill"></i>
            <p>
                <strong>Perlu diketahui</strong>
                Tarif di atas hanya biaya registrasi dan belum termasuk
                konsultasi dokter, tindakan, pemeriksaan penunjang, atau obat.
                Biaya akhir dapat berbeda sesuai penjamin dan kebutuhan pasien.
            </p>
        </aside>
    </main>
@endsection
