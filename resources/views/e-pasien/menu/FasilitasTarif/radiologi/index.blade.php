@extends("template.epasien.appPasien")

@section("title", "Radiologi | Fasilitas & Tarif")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/radiologi-tarif.css") }}"
        rel="stylesheet" />
@endpush

@section("content")
    @php
        $hasActiveFilters = $class !== "" || $search !== "";
        $selectedClassLabel = collect($classes)->firstWhere("value", $class)["label"]
            ?? ($class !== "" ? $class : "Semua kelas layanan");
    @endphp

    <main class="radiology-rate-page">
        <nav class="radiology-rate-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Kembali ke Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Fasilitas &amp; Tarif</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span class="active">Radiologi</span>
        </nav>

        <section class="radiology-rate-hero">
            <div class="radiology-rate-hero-copy">
                <span class="radiology-rate-eyebrow">
                    <i class="bi bi-radioactive"></i>
                    Informasi pemeriksaan
                </span>
                <h1>Radiologi &amp; Tarif</h1>
                <p>
                    Temukan jenis pemeriksaan radiologi dan perkiraan tarifnya
                    sebelum Anda datang ke rumah sakit.
                </p>
                <span class="radiology-rate-updated">
                    <i class="bi bi-arrow-repeat"></i>
                    Tarif mengikuti data saat halaman dibuka
                </span>
            </div>

            <div class="radiology-rate-hero-total" aria-label="Jumlah layanan aktif">
                <span class="radiology-rate-hero-icon">
                    <i class="bi bi-clipboard2-pulse"></i>
                </span>
                <span>
                    <small>Layanan tersedia</small>
                    <strong>{{ number_format($summary["total"], 0, ",", ".") }}</strong>
                    <em>layanan</em>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="radiology-rate-alert" role="alert">
                <i class="bi bi-cloud-slash"></i>
                <div>
                    <strong>Data belum dapat ditampilkan</strong>
                    <span>{{ $connectionError }}</span>
                </div>
            </div>
        @endif

        <section class="radiology-rate-overview"
            aria-label="Ringkasan tarif radiologi">
            <div>
                <span><i class="bi bi-list-check"></i></span>
                <div>
                    <small>Total layanan</small>
                    <strong>{{ number_format($summary["total"], 0, ",", ".") }}</strong>
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

        <section class="radiology-rate-content">
            <div class="radiology-rate-content-heading">
                <div>
                    <span>Daftar pemeriksaan</span>
                    <h2>Cari layanan yang Anda perlukan</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("radiologi.index") }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset filter</span>
                    </a>
                @endif
            </div>

            <form action="{{ route("radiologi.index") }}" method="GET"
                class="radiology-rate-search">
                <label class="radiology-rate-search-field">
                    <span>Cari pemeriksaan</span>
                    <span class="radiology-rate-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q"
                            value="{{ old("q", $search) }}"
                            placeholder="Contoh: thorax atau USG"
                            maxlength="80" autocomplete="off"
                            inputmode="search" enterkeyhint="search"
                            @disabled($connectionError)>
                    </span>
                    @error("q")
                        <small class="radiology-rate-field-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="radiology-rate-class-field">
                    <span>Kelas layanan</span>
                    <span class="radiology-rate-control">
                        <i class="bi bi-layers"></i>
                        <select name="kelas" @disabled($connectionError)>
                            <option value="">Semua kelas</option>
                            @foreach ($classes as $classOption)
                                <option value="{{ $classOption["value"] }}"
                                    @selected(old("kelas", $class) === $classOption["value"])>
                                    {{ $classOption["label"] }}
                                </option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down radiology-rate-select-icon"></i>
                    </span>
                    @error("kelas")
                        <small class="radiology-rate-field-error">{{ $message }}</small>
                    @enderror
                </label>

                <button type="submit" @disabled($connectionError)>
                    <i class="bi bi-search"></i>
                    <span>Tampilkan</span>
                </button>
            </form>

            <div class="radiology-rate-results-heading">
                <div>
                    <h2>
                        {{ $search !== "" ? "Hasil pencarian" : "Semua pemeriksaan" }}
                    </h2>
                    <p>{{ $selectedClassLabel }}</p>
                </div>
                <span>
                    {{ number_format($rates->total(), 0, ",", ".") }} hasil
                </span>
            </div>

            @if ($connectionError)
                <div class="radiology-rate-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Informasi radiologi belum tersedia</strong>
                    <p>Silakan coba muat ulang halaman beberapa saat lagi.</p>
                </div>
            @elseif ($rates->count() === 0)
                <div class="radiology-rate-empty">
                    <span><i class="bi bi-search"></i></span>
                    <strong>
                        {{ $hasActiveFilters
                            ? "Pemeriksaan tidak ditemukan"
                            : "Belum ada data radiologi aktif" }}
                    </strong>
                    <p>
                        {{ $hasActiveFilters
                            ? "Coba gunakan kata kunci lain atau pilih semua kelas."
                            : "Informasi tarif akan tampil setelah datanya tersedia." }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("radiologi.index") }}">
                            Lihat semua pemeriksaan
                        </a>
                    @endif
                </div>
            @else
                <div class="radiology-rate-grid">
                    @foreach ($rates as $rate)
                        <article class="radiology-rate-card">
                            <div class="radiology-rate-card-top">
                                <span class="radiology-rate-class">
                                    <i class="bi bi-layers"></i>
                                    {{ $rate["class_label"] }}
                                </span>
                                <span class="radiology-rate-code">
                                    {{ $rate["code"] }}
                                </span>
                            </div>

                            <div class="radiology-rate-card-main">
                                <span class="radiology-rate-card-icon">
                                    <i class="bi bi-radioactive"></i>
                                </span>
                                <div>
                                    <small>Jenis pemeriksaan</small>
                                    <h3>{{ $rate["name"] }}</h3>
                                </div>
                            </div>

                            <div class="radiology-rate-price">
                                <span>
                                    <i class="bi bi-receipt"></i>
                                </span>
                                <div>
                                    <small>Perkiraan tarif</small>
                                    <strong>{{ $rate["tariff_formatted"] }}</strong>
                                </div>
                                <em>/ pemeriksaan</em>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($rates->hasPages())
                    <nav class="radiology-rate-pagination"
                        aria-label="Navigasi daftar tarif radiologi">
                        @if ($rates->previousPageUrl())
                            <a href="{{ $rates->previousPageUrl() }}" rel="prev">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </a>
                        @else
                            <span class="disabled">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </span>
                        @endif

                        <span class="radiology-rate-page-count">
                            <small>Halaman</small>
                            <strong>
                                {{ $rates->currentPage() }} / {{ $rates->lastPage() }}
                            </strong>
                        </span>

                        @if ($rates->nextPageUrl())
                            <a href="{{ $rates->nextPageUrl() }}" rel="next">
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

        <aside class="radiology-rate-disclaimer">
            <i class="bi bi-info-circle-fill"></i>
            <p>
                <strong>Perlu diketahui</strong>
                Tarif yang ditampilkan merupakan perkiraan per pemeriksaan dan
                dapat berubah sesuai kebijakan rumah sakit serta kondisi pasien.
                Konfirmasikan kembali dengan petugas pendaftaran.
            </p>
        </aside>
    </main>
@endsection
