@extends("template.epasien.appPasien")

@section("title", "Kamar | Fasilitas & Tarif")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/kamar.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $statusFilters = [
            "" => [
                "label" => "Semua",
                "icon" => "bi-grid",
                "count" => $counts["all"],
            ],
            "tersedia" => [
                "label" => "Tersedia",
                "icon" => "bi-check-circle-fill",
                "count" => $counts["available"],
            ],
            "terisi" => [
                "label" => "Terisi",
                "icon" => "bi-person-fill",
                "count" => $counts["occupied"],
            ],
            "dibersihkan" => [
                "label" => "Dibersihkan",
                "icon" => "bi-stars",
                "count" => $counts["cleaning"],
            ],
            "dipesan" => [
                "label" => "Dipesan",
                "icon" => "bi-calendar2-check-fill",
                "count" => $counts["booked"],
            ],
        ];
        $persistentFilters = array_filter([
            "kelas" => $class,
            "q" => $search,
        ], fn ($value) => $value !== null && $value !== "");
        $hasActiveFilters = $status !== null || count($persistentFilters) > 0;
    @endphp

    <div class="room-page">
        <nav class="room-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>Fasilitas &amp; Tarif</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span class="active">Kamar</span>
        </nav>

        <section class="room-hero">
            <div class="room-hero-copy">
                <span class="room-eyebrow">
                    <i class="bi bi-hospital"></i>
                    Informasi rawat inap
                </span>
                <h1>Kamar &amp; Tarif</h1>
                <p>
                    Cari kamar berdasarkan kelas dan lihat ketersediaan serta
                    perkiraan tarifnya dengan mudah.
                </p>
                <span class="room-updated">
                    <i class="bi bi-arrow-repeat"></i>
                    Status mengikuti data saat halaman dibuka
                </span>
            </div>

            <div class="room-hero-availability">
                <span class="room-hero-icon">
                    <i class="bi bi-door-open"></i>
                </span>
                <span>
                    <small>Kamar tersedia</small>
                    <strong>{{ number_format($counts["available"], 0, ",", ".") }}</strong>
                    <em>kamar</em>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="room-alert" role="alert">
                <i class="bi bi-cloud-slash"></i>
                <div>
                    <strong>Data belum dapat ditampilkan</strong>
                    <span>{{ $connectionError }}</span>
                </div>
            </div>
        @endif

        <section class="room-overview" aria-label="Ringkasan status kamar">
            <div class="tone-available">
                <span><i class="bi bi-check-circle-fill"></i></span>
                <div>
                    <strong>{{ number_format($counts["available"], 0, ",", ".") }}</strong>
                    <small>Tersedia</small>
                </div>
            </div>
            <div class="tone-occupied">
                <span><i class="bi bi-person-fill"></i></span>
                <div>
                    <strong>{{ number_format($counts["occupied"], 0, ",", ".") }}</strong>
                    <small>Terisi</small>
                </div>
            </div>
            <div class="tone-cleaning">
                <span><i class="bi bi-stars"></i></span>
                <div>
                    <strong>{{ number_format($counts["cleaning"], 0, ",", ".") }}</strong>
                    <small>Dibersihkan</small>
                </div>
            </div>
            <div class="tone-booked">
                <span><i class="bi bi-calendar2-check-fill"></i></span>
                <div>
                    <strong>{{ number_format($counts["booked"], 0, ",", ".") }}</strong>
                    <small>Dipesan</small>
                </div>
            </div>
        </section>

        <section class="room-content">
            <div class="room-content-heading">
                <div>
                    <span>Temukan kamar</span>
                    <h2>Pilih sesuai kebutuhan Anda</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("kamar.index") }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset filter</span>
                    </a>
                @endif
            </div>

            <nav class="room-status-filter" aria-label="Filter status kamar"
                tabindex="0">
                @foreach ($statusFilters as $value => $filter)
                    @php
                        $isActive = ($status ?? "") === $value;
                        $filterQuery = $persistentFilters;
                        if ($value !== "") {
                            $filterQuery["status"] = $value;
                        }
                    @endphp
                    <a href="{{ route("kamar.index", $filterQuery) }}"
                        class="tone-{{ $value ?: "all" }} {{ $isActive ? "active" : "" }}"
                        @if ($isActive) aria-current="page" @endif>
                        <i class="bi {{ $filter["icon"] }}"></i>
                        <span>{{ $filter["label"] }}</span>
                        <strong>{{ number_format($filter["count"], 0, ",", ".") }}</strong>
                    </a>
                @endforeach
            </nav>
            <p class="room-status-hint" aria-hidden="true">
                <i class="bi bi-hand-index-thumb"></i>
                Geser untuk melihat status lainnya
            </p>

            <form action="{{ route("kamar.index") }}" method="GET"
                class="room-search-panel">
                @if ($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif

                <label class="room-search-field">
                    <span>Cari kamar</span>
                    <span class="room-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q"
                            value="{{ old("q", $search) }}"
                            placeholder="Nama ruang atau kode kamar"
                            maxlength="60" autocomplete="off"
                            inputmode="search" enterkeyhint="search"
                            @disabled($connectionError)>
                    </span>
                    @error("q")
                        <small class="room-field-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="room-class-field">
                    <span>Kelas perawatan</span>
                    <span class="room-control">
                        <i class="bi bi-layers"></i>
                        <select name="kelas" @disabled($connectionError)>
                            <option value="">Semua kelas</option>
                            @foreach ($classes as $classOption)
                                <option value="{{ $classOption }}"
                                    @selected(old("kelas", $class) === $classOption)>
                                    {{ $classOption }}
                                </option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down room-select-icon"></i>
                    </span>
                    @error("kelas")
                        <small class="room-field-error">{{ $message }}</small>
                    @enderror
                </label>

                <button type="submit" @disabled($connectionError)>
                    <i class="bi bi-search"></i>
                    <span>Tampilkan</span>
                </button>
            </form>

            <div class="room-results-heading">
                <div>
                    <h2>
                        {{ $status ? $statusFilters[$status]["label"] : "Semua Kamar" }}
                    </h2>
                    <p>
                        @if ($class)
                            {{ $class }}
                        @else
                            Seluruh kelas perawatan
                        @endif
                    </p>
                </div>
                <span>{{ number_format($rooms->total(), 0, ",", ".") }} hasil</span>
            </div>

            @if ($connectionError)
                <div class="room-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Informasi kamar belum tersedia</strong>
                    <p>Silakan coba muat ulang halaman beberapa saat lagi.</p>
                </div>
            @elseif ($rooms->count() === 0)
                <div class="room-empty">
                    <span><i class="bi bi-door-closed"></i></span>
                    <strong>
                        {{ $hasActiveFilters
                            ? "Kamar tidak ditemukan"
                            : "Belum ada data kamar aktif" }}
                    </strong>
                    <p>
                        {{ $hasActiveFilters
                            ? "Coba ubah kata kunci, kelas, atau status kamar."
                            : "Informasi kamar akan tampil setelah datanya tersedia." }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("kamar.index") }}">Lihat semua kamar</a>
                    @endif
                </div>
            @else
                <div class="room-grid">
                    @foreach ($rooms as $room)
                        <article class="room-card tone-{{ $room["status"] }}">
                            <div class="room-card-top">
                                <span class="room-status">
                                    <i class="bi {{ $room["status_icon"] }}"></i>
                                    {{ $room["status_label"] }}
                                </span>
                                <span class="room-class">
                                    <i class="bi bi-layers"></i>
                                    {{ $room["class"] }}
                                </span>
                            </div>

                            <div class="room-card-main">
                                <span class="room-door">
                                    <i class="bi bi-door-open"></i>
                                </span>
                                <div>
                                    <small>
                                        <i class="bi bi-building"></i>
                                        {{ $room["ward"] }}
                                    </small>
                                    <h3>{{ $room["code"] }}</h3>
                                    <span>Nomor / kode kamar</span>
                                </div>
                            </div>

                            <div class="room-card-meta">
                                <span>
                                    <i class="bi bi-geo-alt"></i>
                                    <span>
                                        <small>Bangsal</small>
                                        <strong>{{ $room["ward"] }}</strong>
                                    </span>
                                </span>
                                <span>
                                    <i class="bi bi-upc-scan"></i>
                                    <span>
                                        <small>Kode bangsal</small>
                                        <strong>{{ $room["ward_code"] }}</strong>
                                    </span>
                                </span>
                            </div>

                            <div class="room-tariff">
                                <span class="room-tariff-icon">
                                    <i class="bi bi-receipt"></i>
                                </span>
                                <span class="room-tariff-copy">
                                    <small>Tarif kamar</small>
                                    <strong>{{ $room["tariff_formatted"] }}</strong>
                                </span>
                                <em>/ hari</em>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($rooms->hasPages())
                    <nav class="room-pagination" aria-label="Navigasi daftar kamar">
                        @if ($rooms->previousPageUrl())
                            <a href="{{ $rooms->previousPageUrl() }}" rel="prev">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </a>
                        @else
                            <span class="disabled">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </span>
                        @endif

                        <span class="room-page-count">
                            <small>Halaman</small>
                            <strong>{{ $rooms->currentPage() }} / {{ $rooms->lastPage() }}</strong>
                        </span>

                        @if ($rooms->nextPageUrl())
                            <a href="{{ $rooms->nextPageUrl() }}" rel="next">
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

        <aside class="room-disclaimer">
            <i class="bi bi-info-circle-fill"></i>
            <p>
                <strong>Perlu diketahui</strong>
                Ketersediaan dapat berubah sewaktu-waktu. Tarif di atas adalah
                tarif kamar per hari dan belum termasuk tindakan, obat,
                pemeriksaan, maupun biaya layanan lainnya. Konfirmasikan kembali
                dengan petugas pendaftaran.
            </p>
        </aside>
    </div>
@endsection

@push("script")
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (!window.matchMedia("(max-width: 991.98px)").matches) {
                return;
            }

            const activeStatus = document.querySelector(
                ".room-status-filter a.active"
            );

            if (activeStatus) {
                const statusFilter = activeStatus.closest(
                    ".room-status-filter"
                );
                const centeredPosition = activeStatus.offsetLeft
                    - ((statusFilter.clientWidth - activeStatus.clientWidth) / 2);

                statusFilter.scrollTo({
                    behavior: "auto",
                    left: Math.max(0, centeredPosition),
                });
            }
        });
    </script>
@endpush
