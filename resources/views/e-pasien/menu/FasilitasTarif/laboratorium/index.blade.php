@extends("template.epasien.appPasien")

@section("title", "Laboratorium | Fasilitas & Tarif")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/laboratorium-tarif.css") }}"
        rel="stylesheet" />
@endpush

@section("content")
    @php
        $hasActiveFilters = $group !== "" || $search !== "";
        $selectedGroup = collect($groups)->firstWhere("value", $group);
        $selectedGroupLabel = $selectedGroup["label"]
            ?? ($group !== "" ? "Kelompok terpilih" : "Semua kelompok");
    @endphp

    <main class="laboratory-rate-page">
        <nav class="laboratory-rate-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Fasilitas &amp; Tarif</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span class="active">Laboratorium</span>
        </nav>

        <section class="laboratory-rate-hero">
            <div class="laboratory-rate-hero-copy">
                <span class="laboratory-rate-eyebrow">
                    <i class="bi bi-droplet-half"></i>
                    Informasi pemeriksaan
                </span>
                <h1>Laboratorium &amp; Tarif</h1>
                <p>
                    Cari item pemeriksaan laboratorium, lihat kelompok layanan,
                    satuan hasil, dan perkiraan tarifnya dalam satu halaman.
                </p>
                <span class="laboratory-rate-updated">
                    <i class="bi bi-arrow-repeat"></i>
                    Data aktif diperbarui saat halaman dibuka
                </span>
            </div>

            <div class="laboratory-rate-hero-total"
                aria-label="Jumlah item pemeriksaan aktif">
                <span class="laboratory-rate-hero-icon">
                    <i class="bi bi-clipboard2-pulse"></i>
                </span>
                <span>
                    <small>Item pemeriksaan</small>
                    <strong>{{ number_format($summary["items"], 0, ",", ".") }}</strong>
                    <em>item aktif</em>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="laboratory-rate-alert" role="alert">
                <i class="bi bi-cloud-slash"></i>
                <div>
                    <strong>Data belum dapat ditampilkan</strong>
                    <span>{{ $connectionError }}</span>
                </div>
            </div>
        @endif

        <section class="laboratory-rate-overview"
            aria-label="Ringkasan layanan laboratorium">
            <div>
                <span><i class="bi bi-collection"></i></span>
                <div>
                    <small>Kelompok layanan</small>
                    <strong>{{ number_format($summary["groups"], 0, ",", ".") }}</strong>
                </div>
            </div>
            <div>
                <span><i class="bi bi-tags"></i></span>
                <div>
                    <small>Tarif tersedia</small>
                    <strong>{{ number_format($summary["priced"], 0, ",", ".") }} item</strong>
                </div>
            </div>
            <div>
                <span><i class="bi bi-arrow-down-circle"></i></span>
                <div>
                    <small>Tarif mulai</small>
                    <strong>{{ $summary["minimum_formatted"] }}</strong>
                </div>
            </div>
            <div>
                <span><i class="bi bi-arrow-up-circle"></i></span>
                <div>
                    <small>Tarif tertinggi</small>
                    <strong>{{ $summary["maximum_formatted"] }}</strong>
                </div>
            </div>
        </section>

        <section class="laboratory-rate-content">
            <div class="laboratory-rate-content-heading">
                <div>
                    <span>Daftar pemeriksaan</span>
                    <h2>Temukan pemeriksaan yang dibutuhkan</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("laboratorium.index") }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset filter</span>
                    </a>
                @endif
            </div>

            <form action="{{ route("laboratorium.index") }}" method="GET"
                class="laboratory-rate-search">
                <label class="laboratory-rate-search-field">
                    <span>Cari pemeriksaan</span>
                    <span class="laboratory-rate-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q"
                            value="{{ old("q", $search) }}"
                            placeholder="Contoh: hemoglobin atau gula darah"
                            maxlength="80" autocomplete="off"
                            inputmode="search" enterkeyhint="search"
                            @disabled($connectionError)>
                    </span>
                    @error("q")
                        <small class="laboratory-rate-field-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="laboratory-rate-group-field">
                    <span>Kelompok layanan</span>
                    <span class="laboratory-rate-control">
                        <i class="bi bi-collection"></i>
                        <select name="kelompok" @disabled($connectionError)>
                            <option value="">Semua kelompok</option>
                            @foreach ($groups as $groupOption)
                                <option value="{{ $groupOption["value"] }}"
                                    @selected(old("kelompok", $group) === $groupOption["value"])>
                                    {{ $groupOption["label"] }}
                                    ({{ $groupOption["total_items"] }})
                                </option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down laboratory-rate-select-icon"></i>
                    </span>
                    @error("kelompok")
                        <small class="laboratory-rate-field-error">{{ $message }}</small>
                    @enderror
                </label>

                <button type="submit" @disabled($connectionError)>
                    <i class="bi bi-search"></i>
                    <span>Tampilkan</span>
                </button>
            </form>

            <div class="laboratory-rate-results-heading" aria-live="polite">
                <div>
                    <h2>
                        {{ $search !== "" ? "Hasil pencarian" : "Semua pemeriksaan" }}
                    </h2>
                    <p>{{ $selectedGroupLabel }}</p>
                </div>
                <span>
                    {{ number_format($items->total(), 0, ",", ".") }} hasil
                </span>
            </div>

            @if ($connectionError)
                <div class="laboratory-rate-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Informasi laboratorium belum tersedia</strong>
                    <p>Silakan coba muat ulang halaman beberapa saat lagi.</p>
                </div>
            @elseif ($items->count() === 0)
                <div class="laboratory-rate-empty">
                    <span><i class="bi bi-search"></i></span>
                    <strong>
                        {{ $hasActiveFilters
                            ? "Pemeriksaan tidak ditemukan"
                            : "Belum ada data laboratorium aktif" }}
                    </strong>
                    <p>
                        {{ $hasActiveFilters
                            ? "Coba kata kunci lain atau pilih semua kelompok."
                            : "Informasi tarif akan tampil setelah datanya tersedia." }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("laboratorium.index") }}">
                            Lihat semua pemeriksaan
                        </a>
                    @endif
                </div>
            @else
                <div class="laboratory-rate-grid">
                    @foreach ($items as $item)
                        <article class="laboratory-rate-card">
                            <div class="laboratory-rate-card-top">
                                <span class="laboratory-rate-group">
                                    <i class="bi bi-collection"></i>
                                    {{ $item["group_name"] }}
                                </span>
                                <span class="laboratory-rate-code">
                                    {{ $item["code"] }}
                                </span>
                            </div>

                            <div class="laboratory-rate-card-main">
                                <span class="laboratory-rate-card-icon">
                                    <i class="bi bi-droplet-half"></i>
                                </span>
                                <div>
                                    <small>Item pemeriksaan</small>
                                    <h3>{{ $item["name"] }}</h3>
                                    @if ($item["has_unit"])
                                        <span>
                                            <i class="bi bi-rulers"></i>
                                            Satuan hasil: {{ $item["unit"] }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div @class([
                                "laboratory-rate-price",
                                "is-unavailable" => !$item["has_tariff"],
                            ])>
                                <span>
                                    <i class="bi bi-receipt"></i>
                                </span>
                                <div>
                                    <small>
                                        {{ $item["has_tariff"]
                                            ? "Perkiraan tarif item"
                                            : "Informasi tarif" }}
                                    </small>
                                    <strong>{{ $item["tariff_formatted"] }}</strong>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($items->hasPages())
                    <nav class="laboratory-rate-pagination"
                        aria-label="Navigasi daftar tarif laboratorium">
                        @if ($items->previousPageUrl())
                            <a href="{{ $items->previousPageUrl() }}" rel="prev">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </a>
                        @else
                            <span class="disabled">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </span>
                        @endif

                        <span class="laboratory-rate-page-count">
                            <small>Halaman</small>
                            <strong>
                                {{ $items->currentPage() }} / {{ $items->lastPage() }}
                            </strong>
                        </span>

                        @if ($items->nextPageUrl())
                            <a href="{{ $items->nextPageUrl() }}" rel="next">
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

        <aside class="laboratory-rate-disclaimer">
            <i class="bi bi-info-circle-fill"></i>
            <p>
                <strong>Perlu diketahui</strong>
                Tarif ditampilkan per item pemeriksaan. Total biaya dapat berbeda
                sesuai kombinasi pemeriksaan yang dipilih, kebutuhan klinis,
                serta kebijakan rumah sakit. Konfirmasikan kembali dengan petugas.
            </p>
        </aside>
    </main>
@endsection
