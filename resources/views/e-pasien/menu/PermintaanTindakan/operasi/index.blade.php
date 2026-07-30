@extends("template.epasien.appPasien")

@section("title", "Operasi | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/pemeriksaan-laborat.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/css/operasi.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $user = auth()->user();
        $patientName = trim((string) ($patient->nm_pasien ?? $user->name ?? "-"));
        $medicalRecordNumber = trim((string) ($patient->no_rkm_medis ?? $user->username ?? "-"));
        $hasPatient = (bool) $patient;
        $statusFilters = [
            "" => [
                "label" => "Semua Operasi",
                "short_label" => "Semua",
                "icon" => "bi-grid",
                "count" => $counts["all"],
            ],
            "terjadwal" => [
                "label" => "Terjadwal",
                "short_label" => "Terjadwal",
                "icon" => "bi-calendar2-check",
                "count" => $counts["terjadwal"],
            ],
            "proses" => [
                "label" => "Sedang Operasi",
                "short_label" => "Proses",
                "icon" => "bi-heart-pulse",
                "count" => $counts["proses"],
            ],
            "menunggu_laporan" => [
                "label" => "Menunggu Laporan",
                "short_label" => "Pelaporan",
                "icon" => "bi-file-earmark-medical",
                "count" => $counts["menunggu_laporan"],
            ],
            "selesai" => [
                "label" => "Laporan Tersedia",
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
        $hasActiveFilters = $workflowStatus !== null || $hasDetailFilters;
        $activeFilterCount = ($workflowStatus !== null ? 1 : 0) + count($persistentFilters);
    @endphp

    @include("e-pasien.menu.PermintaanTindakan.operasi.modalDetail")

    <div class="laboratory-page operation-page">
        <nav class="laboratory-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right"></i>
            <span>Permintaan dan Tindakan</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Operasi</span>
        </nav>

        <section class="laboratory-hero operation-hero">
            <div class="laboratory-hero-copy">
                <span class="laboratory-eyebrow">
                    <i class="bi bi-shield-check"></i>
                    Informasi operasi pribadi
                </span>
                <h1>Operasi</h1>
                <p>Pantau jadwal, pelaksanaan tindakan, hingga laporan operasi Anda dalam satu alur yang mudah dipahami.</p>
                <div class="laboratory-patient">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>No. RM {{ $medicalRecordNumber }}</span>
                </div>
            </div>

            <div class="laboratory-hero-summary">
                <span class="laboratory-hero-icon"><i class="bi bi-bandaid"></i></span>
                <span>
                    <small>Total jadwal operasi</small>
                    <strong>{{ number_format($counts["all"], 0, ",", ".") }}</strong>
                    <em>operasi</em>
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
                    <span class="laboratory-section-kicker">Tahap layanan</span>
                    <h2>Operasi Anda</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("operasi.index") }}" class="laboratory-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset semua filter
                    </a>
                @endif
            </div>

            <button type="button" class="laboratory-mobile-filter-toggle"
                data-mobile-filter-toggle aria-controls="operationFilterPanel"
                aria-expanded="true">
                <span class="laboratory-mobile-filter-toggle-icon">
                    <i class="bi bi-sliders2"></i>
                </span>
                <span class="laboratory-mobile-filter-toggle-copy">
                    <strong>Filter &amp; pencarian</strong>
                    <small>Pilih tahap, layanan, atau tanggal</small>
                </span>
                @if ($activeFilterCount > 0)
                    <span class="laboratory-mobile-filter-count">
                        {{ $activeFilterCount }} aktif
                    </span>
                @endif
                <i class="bi bi-chevron-up laboratory-mobile-filter-chevron"></i>
            </button>

            <div class="laboratory-filter-panel" id="operationFilterPanel"
                data-mobile-filter-panel>
            <nav class="laboratory-status-filters operation-status-filters"
                aria-label="Filter status operasi">
                @foreach ($statusFilters as $value => $filter)
                    @php
                        $isActive = ($workflowStatus ?? "") === $value;
                        $filterQuery = $persistentFilters;
                        if ($value !== "") {
                            $filterQuery["status"] = $value;
                        }
                    @endphp
                    <a href="{{ route("operasi.index", $filterQuery) }}"
                        class="tone-{{ $value ?: "semua" }} {{ $isActive ? "active" : "" }}"
                        @if ($isActive) aria-current="page" @endif>
                        <span class="laboratory-filter-icon">
                            <i class="bi {{ $filter["icon"] }}"></i>
                        </span>
                        <span>
                            <strong>{{ $filter["short_label"] }}</strong>
                            <small>{{ number_format($filter["count"], 0, ",", ".") }} operasi</small>
                        </span>
                        <i class="bi bi-check-circle-fill laboratory-filter-check"></i>
                    </a>
                @endforeach
            </nav>

            <form action="{{ route("operasi.index") }}" method="GET"
                class="laboratory-advanced-filter">
                @if ($workflowStatus)
                    <input type="hidden" name="status" value="{{ $workflowStatus }}">
                @endif

                <label class="laboratory-filter-field laboratory-search-field">
                    <span>Pencarian</span>
                    <span class="laboratory-filter-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ old("q", $search) }}"
                            placeholder="Tindakan, dokter, ruang, no. rawat..."
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
                        <a href="{{ route("operasi.index", array_filter([
                            "status" => $workflowStatus,
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
                    <h2>{{ $workflowStatus ? $statusFilters[$workflowStatus]["label"] : "Semua Operasi" }}</h2>
                    <p>Urutan data berdasarkan jadwal terbaru.</p>
                </div>
                <span>{{ number_format($operations->total(), 0, ",", ".") }} hasil</span>
            </div>

            @if ($connectionError || ! $hasPatient)
                <div class="laboratory-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Data operasi belum dapat ditampilkan</strong>
                    <p>Data pasien atau koneksi Khanza belum siap.</p>
                </div>
            @elseif ($operations->count() === 0)
                <div class="laboratory-empty">
                    <span><i class="bi bi-calendar2-x"></i></span>
                    <strong>{{ $hasActiveFilters ? "Operasi tidak ditemukan" : "Belum ada jadwal operasi" }}</strong>
                    <p>
                        {{ $hasActiveFilters
                            ? "Ubah tahap, kata kunci, layanan, atau rentang tanggal pencarian."
                            : "Jadwal dan laporan operasi akan muncul pada halaman ini." }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("operasi.index") }}">Tampilkan semua operasi</a>
                    @endif
                </div>
            @else
                <div class="laboratory-list">
                    @foreach ($operations as $operation)
                        <article class="laboratory-card operation-card tone-{{ $operation["status"] }}">
                            <div class="laboratory-card-accent"></div>

                            <div class="laboratory-date"
                                aria-label="{{ $operation["tanggal_booking_lengkap"] }}">
                                <span>{{ $operation["hari_short"] }}</span>
                                <strong>{{ $operation["tanggal_angka"] }}</strong>
                                <small>{{ $operation["bulan_short"] }}</small>
                            </div>

                            <div class="laboratory-card-body">
                                <div class="laboratory-card-heading">
                                    <div>
                                        <div class="laboratory-card-badges">
                                            <span class="laboratory-status-badge">
                                                <i class="bi {{ $operation["status_icon"] }}"></i>
                                                {{ $operation["status_label"] }}
                                            </span>
                                            <span class="laboratory-care-badge tone-{{ $operation["layanan_tone"] }}">
                                                <i class="bi {{ $operation["layanan_tone"] === "ranap" ? "bi-hospital" : "bi-person-walking" }}"></i>
                                                {{ $operation["jenis_layanan"] }}
                                            </span>
                                        </div>
                                        <h3>{{ $operation["judul"] }}</h3>
                                        <p class="laboratory-doctor">
                                            <i class="bi bi-person-badge"></i>
                                            {{ $operation["dokter_operator"] }}
                                        </p>
                                    </div>
                                    <div class="laboratory-order">
                                        <small>No. Rawat</small>
                                        <strong>{{ $operation["no_rawat"] ?: "-" }}</strong>
                                        <span>{{ $operation["poli"] }}</span>
                                    </div>
                                </div>

                                <div class="laboratory-requested-tests">
                                    <span class="laboratory-tests-label">
                                        <i class="bi bi-list-check"></i>
                                        Tindakan
                                    </span>
                                    <div class="laboratory-test-chips">
                                        @forelse (array_slice($operation["tindakan"], 0, 4) as $procedure)
                                            <span>{{ $procedure["nama"] }}</span>
                                        @empty
                                            <span>Rincian tindakan belum tersedia</span>
                                        @endforelse
                                        @if (count($operation["tindakan"]) > 4)
                                            <em>+{{ count($operation["tindakan"]) - 4 }} lainnya</em>
                                        @endif
                                    </div>
                                </div>

                                <div class="laboratory-timeline operation-timeline"
                                    aria-label="Progres operasi">
                                    <div class="is-complete">
                                        <span><i class="bi bi-calendar2-check"></i></span>
                                        <div>
                                            <small>Jadwal</small>
                                            <strong>{{ $operation["tanggal_booking_lengkap"] }}</strong>
                                            <em>{{ $operation["jam_mulai"] }}–{{ $operation["jam_selesai"] }} WIB</em>
                                        </div>
                                    </div>
                                    <i></i>
                                    <div class="{{ $operation["pelaksanaan_tercatat"] ? "is-complete" : ($operation["status"] === "proses" ? "is-current" : "") }}">
                                        <span><i class="bi bi-heart-pulse"></i></span>
                                        <div>
                                            <small>Pelaksanaan</small>
                                            <strong>{{ $operation["tanggal_pelaksanaan_lengkap"] }}</strong>
                                            <em>{{ $operation["pelaksanaan_tercatat"] ? $operation["jam_pelaksanaan"]." WIB" : "Belum tercatat" }}</em>
                                        </div>
                                    </div>
                                    <i></i>
                                    <div class="{{ $operation["laporan_tersedia"] ? "is-complete" : ($operation["status"] === "menunggu_laporan" ? "is-current" : "") }}">
                                        <span><i class="bi bi-file-earmark-medical"></i></span>
                                        <div>
                                            <small>Laporan</small>
                                            <strong>{{ $operation["tanggal_laporan_lengkap"] }}</strong>
                                            <em>{{ $operation["laporan_tersedia"] ? $operation["jam_laporan"]." WIB" : "Belum tersedia" }}</em>
                                        </div>
                                    </div>
                                </div>

                                <div class="laboratory-clinical-info">
                                    <div>
                                        <small><i class="bi bi-geo-alt"></i> Ruang operasi</small>
                                        <strong>{{ $operation["ruang_operasi"] }}</strong>
                                    </div>
                                    <div>
                                        <small><i class="bi bi-clock"></i> Estimasi jadwal</small>
                                        <strong>{{ $operation["durasi_jadwal"] }}</strong>
                                    </div>
                                </div>
                            </div>

                            <aside class="laboratory-card-action">
                                <div>
                                    <span><i class="bi bi-clipboard2-pulse"></i></span>
                                    <small>Ringkasan operasi</small>
                                    <strong>{{ $operation["jumlah_tindakan"] }} tindakan</strong>
                                    <em>{{ $operation["laporan_tersedia"] ? "Laporan dapat dilihat" : "Laporan belum tersedia" }}</em>
                                </div>

                                <button type="button" class="laboratory-result-button"
                                    data-bs-toggle="modal" data-bs-target="#operationDetailModal"
                                    data-detail-url="{{ route("operasi.detail", [
                                        "no_rawat" => $operation["no_rawat"],
                                        "tanggal" => $operation["tanggal_booking"],
                                        "jam_mulai" => $operation["jam_mulai_key"],
                                    ]) }}">
                                    <i class="bi bi-eye"></i>
                                    <span>Lihat Detail</span>
                                </button>
                            </aside>
                        </article>
                    @endforeach
                </div>

                @if ($operations->hasPages())
                    <nav class="laboratory-pagination" aria-label="Navigasi data operasi">
                        @if ($operations->previousPageUrl())
                            <a href="{{ $operations->previousPageUrl() }}">
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
                            Halaman <strong>{{ $operations->currentPage() }}</strong>
                            dari <strong>{{ $operations->lastPage() }}</strong>
                        </span>

                        @if ($operations->nextPageUrl())
                            <a href="{{ $operations->nextPageUrl() }}">
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

        <div class="laboratory-disclaimer operation-disclaimer">
            <i class="bi bi-info-circle"></i>
            <p>
                <strong>Informasi penting</strong>
                Laporan operasi adalah bagian dari rekam medis. Diskusikan diagnosis dan tindak lanjut bersama dokter yang merawat Anda.
            </p>
        </div>
    </div>
@endsection

@push("script")
    @include("e-pasien.menu.PermintaanTindakan.partials.mobileHistoryFilters")
    @include("e-pasien.menu.PermintaanTindakan.operasi.jsMain")
@endpush
