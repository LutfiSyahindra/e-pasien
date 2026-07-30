@extends("template.epasien.appPasien")

@section("title", "Pemeriksaan Radiologi | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/pemeriksaan-laborat.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/css/pemeriksaan-radiologi.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $user = auth()->user();
        $patientName = trim((string) ($patient->nm_pasien ?? $user->name ?? "-"));
        $medicalRecordNumber = trim((string) ($patient->no_rkm_medis ?? $user->username ?? "-"));
        $hasPatient = (bool) $patient;
        $statusFilters = [
            "" => [
                "label" => "Semua Permintaan",
                "short_label" => "Semua",
                "icon" => "bi-grid",
                "count" => $counts["all"],
            ],
            "menunggu" => [
                "label" => "Menunggu Pemeriksaan",
                "short_label" => "Menunggu",
                "icon" => "bi-clock-history",
                "count" => $counts["menunggu"],
            ],
            "proses" => [
                "label" => "Sedang Diperiksa",
                "short_label" => "Diperiksa",
                "icon" => "bi-hourglass-split",
                "count" => $counts["proses"],
            ],
            "selesai" => [
                "label" => "Hasil Tersedia",
                "short_label" => "Selesai",
                "icon" => "bi-check-circle",
                "count" => $counts["selesai"],
            ],
        ];
        $persistentFilters = array_filter([
            "status_layanan" => $careType,
            "tanggal_mulai" => $startDate,
            "tanggal_selesai" => $endDate,
            "q" => $search,
        ], fn ($value) => $value !== null && $value !== "");
        $hasDetailFilters = count($persistentFilters) > 0;
        $hasActiveFilters = $resultStatus !== null || $hasDetailFilters;
        $activeFilterCount = ($resultStatus !== null ? 1 : 0) + count($persistentFilters);
    @endphp

    @include("e-pasien.menu.pemeriksaanRadiologi.modalHasil")

    <div class="laboratory-page radiology-page">
        <nav class="laboratory-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right"></i>
            <span>Permintaan dan Tindakan</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Pemeriksaan Radiologi</span>
        </nav>

        <section class="laboratory-hero">
            <div class="laboratory-hero-copy">
                <span class="laboratory-eyebrow">
                    <i class="bi bi-shield-check"></i>
                    Akses hasil pribadi
                </span>
                <h1>Pemeriksaan Radiologi</h1>
                <p>Pantau permintaan, proses pemeriksaan, hasil pembacaan dokter, dan gambar radiologi Anda.</p>
                <div class="laboratory-patient">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>No. RM {{ $medicalRecordNumber }}</span>
                </div>
            </div>

            <div class="laboratory-hero-summary">
                <span class="laboratory-hero-icon"><i class="bi bi-radioactive"></i></span>
                <span>
                    <small>Total permintaan radiologi</small>
                    <strong>{{ number_format($counts["all"], 0, ",", ".") }}</strong>
                    <em>permintaan</em>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="laboratory-alert danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ $connectionError }}</span>
            </div>
        @elseif (! $hasPatient)
            <div class="laboratory-alert warning" role="alert">
                <i class="bi bi-person-x"></i>
                <span>Data pasien dengan nomor rekam medis {{ $medicalRecordNumber }} tidak ditemukan.</span>
            </div>
        @endif

        <section class="laboratory-content">
            <div class="laboratory-section-heading">
                <div>
                    <span class="laboratory-section-kicker">Status pemeriksaan</span>
                    <h2>Permintaan Radiologi Anda</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("pemeriksaanRadiologi.index") }}" class="laboratory-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset semua filter
                    </a>
                @endif
            </div>

            <button type="button" class="laboratory-mobile-filter-toggle"
                data-mobile-filter-toggle aria-controls="radiologyFilterPanel"
                aria-expanded="true">
                <span class="laboratory-mobile-filter-toggle-icon">
                    <i class="bi bi-sliders2"></i>
                </span>
                <span class="laboratory-mobile-filter-toggle-copy">
                    <strong>Filter &amp; pencarian</strong>
                    <small>Pilih status, layanan, atau tanggal</small>
                </span>
                @if ($activeFilterCount > 0)
                    <span class="laboratory-mobile-filter-count">
                        {{ $activeFilterCount }} aktif
                    </span>
                @endif
                <i class="bi bi-chevron-up laboratory-mobile-filter-chevron"></i>
            </button>

            <div class="laboratory-filter-panel" id="radiologyFilterPanel"
                data-mobile-filter-panel>
            <nav class="laboratory-status-filters" aria-label="Filter status radiologi">
                @foreach ($statusFilters as $value => $filter)
                    @php
                        $isActive = ($resultStatus ?? "") === $value;
                        $filterQuery = $persistentFilters;
                        if ($value !== "") {
                            $filterQuery["status_hasil"] = $value;
                        }
                    @endphp
                    <a href="{{ route("pemeriksaanRadiologi.index", $filterQuery) }}"
                        class="tone-{{ $value ?: "semua" }} {{ $isActive ? "active" : "" }}"
                        @if ($isActive) aria-current="page" @endif>
                        <span class="laboratory-filter-icon">
                            <i class="bi {{ $filter["icon"] }}"></i>
                        </span>
                        <span>
                            <strong>{{ $filter["short_label"] }}</strong>
                            <small>{{ number_format($filter["count"], 0, ",", ".") }} permintaan</small>
                        </span>
                        <i class="bi bi-check-circle-fill laboratory-filter-check"></i>
                    </a>
                @endforeach
            </nav>

            <form action="{{ route("pemeriksaanRadiologi.index") }}" method="GET"
                class="laboratory-advanced-filter">
                @if ($resultStatus)
                    <input type="hidden" name="status_hasil" value="{{ $resultStatus }}">
                @endif

                <label class="laboratory-filter-field laboratory-search-field">
                    <span>Pencarian</span>
                    <span class="laboratory-filter-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ old("q", $search) }}"
                            placeholder="No. permintaan, tindakan, dokter..."
                            @disabled($connectionError || ! $hasPatient)>
                    </span>
                    @error("q")
                        <small class="laboratory-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="laboratory-filter-field">
                    <span>Jenis layanan</span>
                    <span class="laboratory-filter-control">
                        <i class="bi bi-hospital"></i>
                        <select name="status_layanan" @disabled($connectionError || ! $hasPatient)>
                            <option value="">Semua layanan</option>
                            <option value="ralan" @selected(old("status_layanan", $careType) === "ralan")>
                                Rawat Jalan
                            </option>
                            <option value="ranap" @selected(old("status_layanan", $careType) === "ranap")>
                                Rawat Inap
                            </option>
                        </select>
                    </span>
                    @error("status_layanan")
                        <small class="laboratory-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="laboratory-filter-field">
                    <span>Tanggal mulai</span>
                    <span class="laboratory-filter-control">
                        <i class="bi bi-calendar-event"></i>
                        <input type="date" name="tanggal_mulai"
                            value="{{ old("tanggal_mulai", $startDate) }}"
                            @disabled($connectionError || ! $hasPatient)>
                    </span>
                    @error("tanggal_mulai")
                        <small class="laboratory-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="laboratory-filter-field">
                    <span>Tanggal selesai</span>
                    <span class="laboratory-filter-control">
                        <i class="bi bi-calendar-check"></i>
                        <input type="date" name="tanggal_selesai"
                            value="{{ old("tanggal_selesai", $endDate) }}"
                            @disabled($connectionError || ! $hasPatient)>
                    </span>
                    @error("tanggal_selesai")
                        <small class="laboratory-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <div class="laboratory-filter-actions">
                    @if ($hasDetailFilters)
                        <a href="{{ route("pemeriksaanRadiologi.index", array_filter([
                            "status_hasil" => $resultStatus,
                        ])) }}">
                            Hapus
                        </a>
                    @endif
                    <button type="submit" @disabled($connectionError || ! $hasPatient)>
                        <i class="bi bi-funnel"></i>
                        Terapkan
                    </button>
                </div>
            </form>
            </div>

            <div class="laboratory-list-heading">
                <div>
                    <h2>{{ $resultStatus ? $statusFilters[$resultStatus]["label"] : "Semua Permintaan" }}</h2>
                    <p>Urutan data berdasarkan permintaan terbaru.</p>
                </div>
                <span>{{ number_format($requests->total(), 0, ",", ".") }} hasil</span>
            </div>

            @if ($connectionError || ! $hasPatient)
                <div class="laboratory-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Data radiologi belum dapat ditampilkan</strong>
                    <p>Data pasien atau koneksi Khanza belum siap.</p>
                </div>
            @elseif ($requests->count() === 0)
                <div class="laboratory-empty">
                    <span><i class="bi bi-clipboard2-x"></i></span>
                    <strong>{{ $hasActiveFilters ? "Permintaan tidak ditemukan" : "Belum ada permintaan radiologi" }}</strong>
                    <p>
                        {{ $hasActiveFilters
                            ? "Ubah status, kata kunci, layanan, atau rentang tanggal pencarian."
                            : "Permintaan pemeriksaan radiologi akan muncul pada halaman ini." }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("pemeriksaanRadiologi.index") }}">Tampilkan semua permintaan</a>
                    @endif
                </div>
            @else
                <div class="laboratory-list">
                    @foreach ($requests as $radiologyRequest)
                        <article class="laboratory-card tone-{{ $radiologyRequest["status"] }}">
                            <div class="laboratory-card-accent"></div>

                            <div class="laboratory-date"
                                aria-label="{{ $radiologyRequest["tanggal_permintaan_lengkap"] }}">
                                <span>{{ $radiologyRequest["hari_short"] }}</span>
                                <strong>{{ $radiologyRequest["tanggal_angka"] }}</strong>
                                <small>{{ $radiologyRequest["bulan_short"] }}</small>
                            </div>

                            <div class="laboratory-card-body">
                                <div class="laboratory-card-heading">
                                    <div>
                                        <div class="laboratory-card-badges">
                                            <span class="laboratory-status-badge">
                                                <i class="bi {{ $radiologyRequest["status_icon"] }}"></i>
                                                {{ $radiologyRequest["status_label"] }}
                                            </span>
                                            <span class="laboratory-care-badge tone-{{ $radiologyRequest["layanan_tone"] }}">
                                                <i class="bi {{ $radiologyRequest["layanan_tone"] === "ranap" ? "bi-hospital" : "bi-person-walking" }}"></i>
                                                {{ $radiologyRequest["jenis_layanan"] }}
                                            </span>
                                        </div>
                                        <h3>{{ $radiologyRequest["poli"] }}</h3>
                                        <p class="laboratory-doctor">
                                            <i class="bi bi-person-badge"></i>
                                            {{ $radiologyRequest["dokter_perujuk"] }}
                                        </p>
                                    </div>
                                    <div class="laboratory-order">
                                        <small>No. Permintaan</small>
                                        <strong>{{ $radiologyRequest["noorder"] ?: "-" }}</strong>
                                        <span>{{ $radiologyRequest["no_rawat"] ?: "-" }}</span>
                                    </div>
                                </div>

                                <div class="laboratory-requested-tests">
                                    <span class="laboratory-tests-label">
                                        <i class="bi bi-bounding-box-circles"></i>
                                        Pemeriksaan diminta
                                    </span>
                                    <div class="laboratory-test-chips">
                                        @forelse (array_slice($radiologyRequest["pemeriksaan_diminta"], 0, 4) as $test)
                                            <span>{{ $test["nama"] }}</span>
                                        @empty
                                            <span>Rincian pemeriksaan belum tersedia</span>
                                        @endforelse
                                        @if (count($radiologyRequest["pemeriksaan_diminta"]) > 4)
                                            <em>+{{ count($radiologyRequest["pemeriksaan_diminta"]) - 4 }} lainnya</em>
                                        @endif
                                    </div>
                                </div>

                                <div class="laboratory-timeline" aria-label="Progres radiologi">
                                    <div class="is-complete">
                                        <span><i class="bi bi-clipboard2-check"></i></span>
                                        <div>
                                            <small>Diminta</small>
                                            <strong>{{ $radiologyRequest["tanggal_permintaan_lengkap"] }}</strong>
                                            <em>{{ $radiologyRequest["jam_permintaan"] }} WIB</em>
                                        </div>
                                    </div>
                                    <i></i>
                                    <div class="{{ $radiologyRequest["tanggal_pemeriksaan"] ? "is-complete" : ($radiologyRequest["status"] === "menunggu" ? "is-current" : "") }}">
                                        <span><i class="bi bi-radioactive"></i></span>
                                        <div>
                                            <small>Diperiksa</small>
                                            <strong>{{ $radiologyRequest["tanggal_pemeriksaan_lengkap"] }}</strong>
                                            <em>{{ $radiologyRequest["tanggal_pemeriksaan"] ? $radiologyRequest["jam_pemeriksaan"]." WIB" : "Belum diperiksa" }}</em>
                                        </div>
                                    </div>
                                    <i></i>
                                    <div class="{{ $radiologyRequest["tanggal_hasil"] ? "is-complete" : ($radiologyRequest["status"] === "proses" ? "is-current" : "") }}">
                                        <span><i class="bi bi-file-earmark-medical"></i></span>
                                        <div>
                                            <small>Hasil</small>
                                            <strong>{{ $radiologyRequest["tanggal_hasil_lengkap"] }}</strong>
                                            <em>{{ $radiologyRequest["tanggal_hasil"] ? $radiologyRequest["jam_hasil"]." WIB" : "Belum tersedia" }}</em>
                                        </div>
                                    </div>
                                </div>

                                <div class="laboratory-clinical-info">
                                    <div>
                                        <small><i class="bi bi-activity"></i> Diagnosa klinis</small>
                                        <strong>{{ $radiologyRequest["diagnosa_klinis"] }}</strong>
                                    </div>
                                    <div>
                                        <small><i class="bi bi-info-circle"></i> Informasi tambahan</small>
                                        <strong>{{ $radiologyRequest["informasi_tambahan"] }}</strong>
                                    </div>
                                </div>
                            </div>

                            <aside class="laboratory-card-action">
                                <div>
                                    <span><i class="bi bi-images"></i></span>
                                    <small>Dokumen radiologi</small>
                                    <strong>{{ $radiologyRequest["jumlah_pemeriksaan"] }} pemeriksaan</strong>
                                    <em>
                                        {{ $radiologyRequest["jumlah_hasil"] }} hasil ·
                                        {{ $radiologyRequest["jumlah_gambar"] }} gambar
                                    </em>
                                </div>

                                @if ($radiologyRequest["status"] === "selesai")
                                    <button type="button" class="laboratory-result-button"
                                        data-bs-toggle="modal" data-bs-target="#radiologyResultModal"
                                        data-result-url="{{ route("pemeriksaanRadiologi.result", [
                                            "noorder" => $radiologyRequest["noorder"],
                                        ]) }}">
                                        <i class="bi bi-eye"></i>
                                        <span>Lihat Hasil</span>
                                    </button>
                                @else
                                    <button type="button" class="laboratory-result-button is-disabled" disabled>
                                        <i class="bi {{ $radiologyRequest["status"] === "proses" ? "bi-hourglass-split" : "bi-clock" }}"></i>
                                        <span>{{ $radiologyRequest["status"] === "proses" ? "Dalam Proses" : "Menunggu" }}</span>
                                    </button>
                                @endif
                            </aside>
                        </article>
                    @endforeach
                </div>

                @if ($requests->hasPages())
                    <nav class="laboratory-pagination" aria-label="Navigasi permintaan radiologi">
                        @if ($requests->previousPageUrl())
                            <a href="{{ $requests->previousPageUrl() }}">
                                <i class="bi bi-chevron-left"></i>
                                Sebelumnya
                            </a>
                        @else
                            <span class="disabled">
                                <i class="bi bi-chevron-left"></i>
                                Sebelumnya
                            </span>
                        @endif

                        <span class="laboratory-page-count">
                            Halaman <strong>{{ $requests->currentPage() }}</strong>
                            dari <strong>{{ $requests->lastPage() }}</strong>
                        </span>

                        @if ($requests->nextPageUrl())
                            <a href="{{ $requests->nextPageUrl() }}">
                                Berikutnya
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        @else
                            <span class="disabled">
                                Berikutnya
                                <i class="bi bi-chevron-right"></i>
                            </span>
                        @endif
                    </nav>
                @endif
            @endif
        </section>

        <div class="laboratory-disclaimer">
            <i class="bi bi-info-circle"></i>
            <p>
                <strong>Informasi penting</strong>
                Hasil dan gambar radiologi perlu diinterpretasikan oleh dokter sesuai kondisi klinis Anda.
            </p>
        </div>
    </div>
@endsection

@push("script")
    @include("e-pasien.menu.partials.mobileHistoryFilters")
    @include("e-pasien.menu.pemeriksaanRadiologi.jsMain")
@endpush
