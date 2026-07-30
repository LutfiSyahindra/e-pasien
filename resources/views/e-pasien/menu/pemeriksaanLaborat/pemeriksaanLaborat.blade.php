@extends("template.epasien.appPasien")

@section("title", "Pemeriksaan Laborat | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/pemeriksaan-laborat.css") }}" rel="stylesheet" />
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
                "label" => "Menunggu Sampel",
                "short_label" => "Menunggu",
                "icon" => "bi-clock-history",
                "count" => $counts["menunggu"],
            ],
            "proses" => [
                "label" => "Sedang Diproses",
                "short_label" => "Diproses",
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

    @include("e-pasien.menu.pemeriksaanLaborat.modalHasil")

    <div class="laboratory-page">
        <nav class="laboratory-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right"></i>
            <span>Permintaan dan Tindakan</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Pemeriksaan Laborat</span>
        </nav>

        <section class="laboratory-hero">
            <div class="laboratory-hero-copy">
                <span class="laboratory-eyebrow">
                    <i class="bi bi-shield-check"></i>
                    Akses hasil pribadi
                </span>
                <h1>Pemeriksaan Laborat</h1>
                <p>Pantau proses pemeriksaan dari permintaan, pengambilan sampel, hingga hasil laboratorium tersedia.</p>
                <div class="laboratory-patient">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>No. RM {{ $medicalRecordNumber }}</span>
                </div>
            </div>

            <div class="laboratory-hero-summary">
                <span class="laboratory-hero-icon"><i class="bi bi-droplet-half"></i></span>
                <span>
                    <small>Total permintaan laboratorium</small>
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
                    <h2>Permintaan Laboratorium Anda</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("pemeriksaanLaborat.index") }}" class="laboratory-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset semua filter
                    </a>
                @endif
            </div>

            <button type="button" class="laboratory-mobile-filter-toggle"
                data-mobile-filter-toggle aria-controls="laboratoryFilterPanel"
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

            <div class="laboratory-filter-panel" id="laboratoryFilterPanel"
                data-mobile-filter-panel>
            <nav class="laboratory-status-filters" aria-label="Filter status hasil laboratorium">
                @foreach ($statusFilters as $value => $filter)
                    @php
                        $isActive = ($resultStatus ?? "") === $value;
                        $filterQuery = $persistentFilters;
                        if ($value !== "") {
                            $filterQuery["status_hasil"] = $value;
                        }
                    @endphp
                    <a href="{{ route("pemeriksaanLaborat.index", $filterQuery) }}"
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

            <form action="{{ route("pemeriksaanLaborat.index") }}" method="GET"
                class="laboratory-advanced-filter">
                @if ($resultStatus)
                    <input type="hidden" name="status_hasil" value="{{ $resultStatus }}">
                @endif

                <label class="laboratory-filter-field laboratory-search-field">
                    <span>Pencarian</span>
                    <span class="laboratory-filter-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ old("q", $search) }}"
                            placeholder="No. permintaan, no. rawat, dokter..."
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
                            <option value="Ralan" @selected(old("status_layanan", $careType) === "Ralan")>
                                Rawat Jalan
                            </option>
                            <option value="Ranap" @selected(old("status_layanan", $careType) === "Ranap")>
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
                        <a href="{{ route("pemeriksaanLaborat.index", array_filter([
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
                    <strong>Data laboratorium belum dapat ditampilkan</strong>
                    <p>Data pasien atau koneksi Khanza belum siap.</p>
                </div>
            @elseif ($requests->count() === 0)
                <div class="laboratory-empty">
                    <span><i class="bi bi-clipboard2-x"></i></span>
                    <strong>{{ $hasActiveFilters ? "Permintaan tidak ditemukan" : "Belum ada permintaan laboratorium" }}</strong>
                    <p>
                        {{ $hasActiveFilters
                            ? "Ubah status, kata kunci, layanan, atau rentang tanggal pencarian."
                            : "Permintaan pemeriksaan laboratorium akan muncul pada halaman ini." }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("pemeriksaanLaborat.index") }}">Tampilkan semua permintaan</a>
                    @endif
                </div>
            @else
                <div class="laboratory-list">
                    @foreach ($requests as $laboratoryRequest)
                        <article class="laboratory-card tone-{{ $laboratoryRequest["status"] }}">
                            <div class="laboratory-card-accent"></div>

                            <div class="laboratory-date"
                                aria-label="{{ $laboratoryRequest["tanggal_permintaan_lengkap"] }}">
                                <span>{{ $laboratoryRequest["hari_short"] }}</span>
                                <strong>{{ $laboratoryRequest["tanggal_angka"] }}</strong>
                                <small>{{ $laboratoryRequest["bulan_short"] }}</small>
                            </div>

                            <div class="laboratory-card-body">
                                <div class="laboratory-card-heading">
                                    <div>
                                        <div class="laboratory-card-badges">
                                            <span class="laboratory-status-badge">
                                                <i class="bi {{ $laboratoryRequest["status_icon"] }}"></i>
                                                {{ $laboratoryRequest["status_label"] }}
                                            </span>
                                            <span class="laboratory-care-badge tone-{{ $laboratoryRequest["layanan_tone"] }}">
                                                <i class="bi {{ $laboratoryRequest["layanan_tone"] === "ranap" ? "bi-hospital" : "bi-person-walking" }}"></i>
                                                {{ $laboratoryRequest["jenis_layanan"] }}
                                            </span>
                                        </div>
                                        <h3>{{ $laboratoryRequest["poli"] }}</h3>
                                        <p class="laboratory-doctor">
                                            <i class="bi bi-person-badge"></i>
                                            {{ $laboratoryRequest["dokter_perujuk"] }}
                                        </p>
                                    </div>
                                    <div class="laboratory-order">
                                        <small>No. Permintaan</small>
                                        <strong>{{ $laboratoryRequest["noorder"] ?: "-" }}</strong>
                                        <span>{{ $laboratoryRequest["no_rawat"] ?: "-" }}</span>
                                    </div>
                                </div>

                                <div class="laboratory-requested-tests">
                                    <span class="laboratory-tests-label">
                                        <i class="bi bi-list-check"></i>
                                        Pemeriksaan diminta
                                    </span>
                                    <div class="laboratory-test-chips">
                                        @forelse (array_slice($laboratoryRequest["pemeriksaan_diminta"], 0, 4) as $test)
                                            <span>{{ $test["nama"] }}</span>
                                        @empty
                                            <span>Rincian pemeriksaan belum tersedia</span>
                                        @endforelse
                                        @if (count($laboratoryRequest["pemeriksaan_diminta"]) > 4)
                                            <em>+{{ count($laboratoryRequest["pemeriksaan_diminta"]) - 4 }} lainnya</em>
                                        @endif
                                    </div>
                                </div>

                                <div class="laboratory-timeline"
                                    aria-label="Progres permintaan laboratorium">
                                    <div class="is-complete">
                                        <span><i class="bi bi-clipboard2-check"></i></span>
                                        <div>
                                            <small>Diminta</small>
                                            <strong>{{ $laboratoryRequest["tanggal_permintaan_lengkap"] }}</strong>
                                            <em>{{ $laboratoryRequest["jam_permintaan"] }} WIB</em>
                                        </div>
                                    </div>
                                    <i></i>
                                    <div class="{{ $laboratoryRequest["tanggal_sampel"] ? "is-complete" : ($laboratoryRequest["status"] === "menunggu" ? "is-current" : "") }}">
                                        <span><i class="bi bi-droplet"></i></span>
                                        <div>
                                            <small>Sampel</small>
                                            <strong>{{ $laboratoryRequest["tanggal_sampel_lengkap"] }}</strong>
                                            <em>{{ $laboratoryRequest["tanggal_sampel"] ? $laboratoryRequest["jam_sampel"]." WIB" : "Belum diambil" }}</em>
                                        </div>
                                    </div>
                                    <i></i>
                                    <div class="{{ $laboratoryRequest["tanggal_hasil"] ? "is-complete" : ($laboratoryRequest["status"] === "proses" ? "is-current" : "") }}">
                                        <span><i class="bi bi-file-earmark-medical"></i></span>
                                        <div>
                                            <small>Hasil</small>
                                            <strong>{{ $laboratoryRequest["tanggal_hasil_lengkap"] }}</strong>
                                            <em>{{ $laboratoryRequest["tanggal_hasil"] ? $laboratoryRequest["jam_hasil"]." WIB" : "Belum tersedia" }}</em>
                                        </div>
                                    </div>
                                </div>

                                <div class="laboratory-clinical-info">
                                    <div>
                                        <small><i class="bi bi-activity"></i> Diagnosa klinis</small>
                                        <strong>{{ $laboratoryRequest["diagnosa_klinis"] }}</strong>
                                    </div>
                                    <div>
                                        <small><i class="bi bi-info-circle"></i> Informasi tambahan</small>
                                        <strong>{{ $laboratoryRequest["informasi_tambahan"] }}</strong>
                                    </div>
                                </div>
                            </div>

                            <aside class="laboratory-card-action">
                                <div>
                                    <span><i class="bi bi-clipboard-data"></i></span>
                                    <small>Rincian permintaan</small>
                                    <strong>{{ $laboratoryRequest["jumlah_pemeriksaan"] }} pemeriksaan</strong>
                                    @if ($laboratoryRequest["hasil_tersedia"])
                                        <em>{{ $laboratoryRequest["jumlah_hasil"] }} parameter hasil</em>
                                    @else
                                        <em>Hasil belum tercatat</em>
                                    @endif
                                </div>

                                @if ($laboratoryRequest["status"] === "selesai")
                                    <button type="button" class="laboratory-result-button"
                                        data-bs-toggle="modal" data-bs-target="#laboratoryResultModal"
                                        data-result-url="{{ route("pemeriksaanLaborat.result", [
                                            "noorder" => $laboratoryRequest["noorder"],
                                        ]) }}">
                                        <i class="bi bi-eye"></i>
                                        <span>Lihat Hasil</span>
                                    </button>
                                @else
                                    <button type="button" class="laboratory-result-button is-disabled" disabled>
                                        <i class="bi {{ $laboratoryRequest["status"] === "proses" ? "bi-hourglass-split" : "bi-clock" }}"></i>
                                        <span>{{ $laboratoryRequest["status"] === "proses" ? "Dalam Proses" : "Menunggu Sampel" }}</span>
                                    </button>
                                @endif
                            </aside>
                        </article>
                    @endforeach
                </div>

                @if ($requests->hasPages())
                    <nav class="laboratory-pagination" aria-label="Navigasi permintaan laboratorium">
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
                Hasil laboratorium perlu diinterpretasikan bersama dokter dengan mempertimbangkan kondisi klinis Anda.
            </p>
        </div>
    </div>
@endsection

@push("script")
    @include("e-pasien.menu.partials.mobileHistoryFilters")
    @include("e-pasien.menu.pemeriksaanLaborat.jsMain")
@endpush
