@extends("template.epasien.appPasien")

@section("title", "Resep Obat | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/resep-obat.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $user = auth()->user();
        $patientName = trim((string) ($patient->nm_pasien ?? $user->name ?? "-"));
        $medicalRecordNumber = trim((string) ($patient->no_rkm_medis ?? $user->username ?? "-"));
        $hasPatient = (bool) $patient;
        $careFilters = [
            "" => [
                "label" => "Semua Resep",
                "short_label" => "Semua",
                "icon" => "bi-grid",
                "count" => $counts["all"],
            ],
            "ralan" => [
                "label" => "Resep Rawat Jalan",
                "short_label" => "Rawat Jalan",
                "icon" => "bi-person-walking",
                "count" => $counts["ralan"],
            ],
            "ranap" => [
                "label" => "Resep Rawat Inap",
                "short_label" => "Rawat Inap",
                "icon" => "bi-hospital",
                "count" => $counts["ranap"],
            ],
        ];
        $persistentFilters = array_filter([
            "jenis_resep" => $prescriptionType,
            "tanggal_mulai" => $startDate,
            "tanggal_selesai" => $endDate,
            "q" => $search,
        ], fn ($value) => $value !== null && $value !== "");
        $hasDetailFilters = filled($prescriptionType)
            || filled($startDate)
            || filled($endDate)
            || filled($search);
        $hasActiveFilters = filled($careType) || $hasDetailFilters;
        $activeFilterCount = (filled($careType) ? 1 : 0) + count($persistentFilters);
    @endphp

    <div class="prescription-page">
        <nav class="prescription-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right"></i>
            <span>Permintaan dan Tindakan</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Resep Obat</span>
        </nav>

        <section class="prescription-hero">
            <div class="prescription-hero-copy">
                <span class="prescription-eyebrow">
                    <i class="bi bi-shield-check"></i>
                    Akses resep pribadi
                </span>
                <h1>Resep Obat</h1>
                <p>
                    Lihat resep dokter, obat racikan, dan permintaan resep pulang
                    dari layanan rawat jalan maupun rawat inap.
                </p>
                <div class="prescription-patient">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>No. RM {{ $medicalRecordNumber }}</span>
                </div>
            </div>

            <div class="prescription-hero-summary">
                <span class="prescription-hero-icon">
                    <i class="bi bi-capsule-pill"></i>
                </span>
                <span>
                    <small>Total riwayat resep</small>
                    <strong>{{ number_format($counts["all"], 0, ",", ".") }}</strong>
                    <em>resep</em>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="prescription-alert danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ $connectionError }}</span>
            </div>
        @elseif (! $hasPatient)
            <div class="prescription-alert warning" role="alert">
                <i class="bi bi-person-x"></i>
                <span>Data pasien dengan nomor rekam medis {{ $medicalRecordNumber }} tidak ditemukan.</span>
            </div>
        @endif

        <section class="prescription-content">
            <div class="prescription-section-heading">
                <div>
                    <span class="prescription-section-kicker">Riwayat resep</span>
                    <h2>Resep Anda</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("resepObat.index") }}" class="prescription-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset semua filter
                    </a>
                @endif
            </div>

            <button type="button" class="prescription-mobile-filter-toggle"
                data-mobile-filter-toggle aria-controls="prescriptionFilterPanel"
                aria-expanded="true">
                <span class="prescription-mobile-filter-toggle-icon">
                    <i class="bi bi-sliders2"></i>
                </span>
                <span class="prescription-mobile-filter-toggle-copy">
                    <strong>Filter &amp; pencarian</strong>
                    <small>Pilih layanan, jenis resep, atau tanggal</small>
                </span>
                @if ($activeFilterCount > 0)
                    <span class="prescription-mobile-filter-count">
                        {{ $activeFilterCount }} aktif
                    </span>
                @endif
                <i class="bi bi-chevron-up prescription-mobile-filter-chevron"></i>
            </button>

            <div class="prescription-filter-panel" id="prescriptionFilterPanel"
                data-mobile-filter-panel>
            <nav class="prescription-care-filters" aria-label="Filter jenis layanan resep">
                @foreach ($careFilters as $value => $filter)
                    @php
                        $isActive = ($careType ?? "") === $value;
                        $filterQuery = $persistentFilters;
                        if ($value !== "") {
                            $filterQuery["status_layanan"] = $value;
                        }
                    @endphp
                    <a href="{{ route("resepObat.index", $filterQuery) }}"
                        class="tone-{{ $value ?: "semua" }} {{ $isActive ? "active" : "" }}"
                        @if ($isActive) aria-current="page" @endif>
                        <span class="prescription-filter-icon">
                            <i class="bi {{ $filter["icon"] }}"></i>
                        </span>
                        <span>
                            <strong>{{ $filter["short_label"] }}</strong>
                            <small>{{ number_format($filter["count"], 0, ",", ".") }} resep</small>
                        </span>
                        <i class="bi bi-check-circle-fill prescription-filter-check"></i>
                    </a>
                @endforeach
            </nav>

            <form action="{{ route("resepObat.index") }}" method="GET"
                class="prescription-advanced-filter">
                @if ($careType)
                    <input type="hidden" name="status_layanan" value="{{ $careType }}">
                @endif

                <label class="prescription-filter-field prescription-search-field">
                    <span>Pencarian</span>
                    <span class="prescription-filter-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ old("q", $search) }}"
                            placeholder="No. resep, no. rawat, dokter..."
                            @disabled($connectionError || ! $hasPatient)>
                    </span>
                    @error("q")
                        <small class="prescription-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="prescription-filter-field">
                    <span>Jenis resep</span>
                    <span class="prescription-filter-control">
                        <i class="bi bi-prescription2"></i>
                        <select name="jenis_resep" @disabled($connectionError || ! $hasPatient)>
                            <option value="">Semua jenis</option>
                            <option value="dokter" @selected(old("jenis_resep", $prescriptionType) === "dokter")>
                                Resep Dokter ({{ number_format($counts["dokter"], 0, ",", ".") }})
                            </option>
                            <option value="pulang" @selected(old("jenis_resep", $prescriptionType) === "pulang")>
                                Resep Pulang ({{ number_format($counts["pulang"], 0, ",", ".") }})
                            </option>
                        </select>
                    </span>
                    @error("jenis_resep")
                        <small class="prescription-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="prescription-filter-field">
                    <span>Tanggal mulai</span>
                    <span class="prescription-filter-control">
                        <i class="bi bi-calendar-event"></i>
                        <input type="date" name="tanggal_mulai"
                            value="{{ old("tanggal_mulai", $startDate) }}"
                            @disabled($connectionError || ! $hasPatient)>
                    </span>
                    @error("tanggal_mulai")
                        <small class="prescription-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="prescription-filter-field">
                    <span>Tanggal selesai</span>
                    <span class="prescription-filter-control">
                        <i class="bi bi-calendar-check"></i>
                        <input type="date" name="tanggal_selesai"
                            value="{{ old("tanggal_selesai", $endDate) }}"
                            @disabled($connectionError || ! $hasPatient)>
                    </span>
                    @error("tanggal_selesai")
                        <small class="prescription-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <div class="prescription-filter-actions">
                    @if ($hasDetailFilters)
                        <a href="{{ route("resepObat.index", array_filter([
                            "status_layanan" => $careType,
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

            <div class="prescription-list-heading">
                <div>
                    <h2>{{ $careType ? $careFilters[$careType]["label"] : "Semua Resep" }}</h2>
                    <p>Urutan resep berdasarkan tanggal terbaru.</p>
                </div>
                <span>{{ number_format($prescriptions->total(), 0, ",", ".") }} hasil</span>
            </div>

            @if ($connectionError || ! $hasPatient)
                <div class="prescription-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Data resep belum dapat ditampilkan</strong>
                    <p>Data pasien atau koneksi Khanza belum siap.</p>
                </div>
            @elseif ($prescriptions->count() === 0)
                <div class="prescription-empty">
                    <span><i class="bi bi-file-earmark-medical"></i></span>
                    <strong>{{ $hasActiveFilters ? "Resep tidak ditemukan" : "Belum ada riwayat resep" }}</strong>
                    <p>
                        {{ $hasActiveFilters
                            ? "Ubah jenis layanan, jenis resep, kata kunci, atau rentang tanggal pencarian."
                            : "Resep dokter dan permintaan resep pulang akan muncul pada halaman ini." }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("resepObat.index") }}">Tampilkan semua resep</a>
                    @endif
                </div>
            @else
                <div class="prescription-list">
                    @foreach ($prescriptions as $prescription)
                        <article class="prescription-card tone-{{ $prescription["status"] }}">
                            <div class="prescription-card-accent"></div>

                            <div class="prescription-date"
                                aria-label="{{ $prescription["tanggal_lengkap"] }}">
                                <span>{{ $prescription["hari_short"] }}</span>
                                <strong>{{ $prescription["tanggal_angka"] }}</strong>
                                <small>{{ $prescription["bulan_short"] }}</small>
                            </div>

                            <div class="prescription-card-body">
                                <div class="prescription-card-heading">
                                    <div>
                                        <div class="prescription-card-badges">
                                            <span class="prescription-status-badge">
                                                <i class="bi {{ $prescription["status_icon"] }}"></i>
                                                {{ $prescription["status_label"] }}
                                            </span>
                                            <span class="prescription-care-badge tone-{{ $prescription["layanan_tone"] }}">
                                                <i class="bi {{ $prescription["layanan_tone"] === "ranap" ? "bi-hospital" : "bi-person-walking" }}"></i>
                                                {{ $prescription["jenis_layanan"] }}
                                            </span>
                                            <span class="prescription-type-badge tone-{{ $prescription["sumber"] }}">
                                                <i class="bi {{ $prescription["jenis_resep_icon"] }}"></i>
                                                {{ $prescription["jenis_resep"] }}
                                            </span>
                                        </div>
                                        <h3>{{ $prescription["poli"] }}</h3>
                                        <p class="prescription-doctor">
                                            <i class="bi bi-person-badge"></i>
                                            {{ $prescription["dokter"] }}
                                        </p>
                                    </div>
                                    <div class="prescription-number">
                                        <small>{{ $prescription["sumber"] === "pulang" ? "No. Permintaan" : "No. Resep" }}</small>
                                        <strong>{{ $prescription["nomor_resep"] ?: "-" }}</strong>
                                        <span>{{ $prescription["no_rawat"] ?: "-" }}</span>
                                    </div>
                                </div>

                                <div class="prescription-progress">
                                    <div class="is-complete">
                                        <span>
                                            <i class="bi {{ $prescription["sumber"] === "pulang" ? "bi-send-check" : "bi-pen" }}"></i>
                                        </span>
                                        <div>
                                            <small>{{ $prescription["sumber"] === "pulang" ? "Diminta" : "Diresepkan" }}</small>
                                            <strong>{{ $prescription["tanggal_lengkap"] }}</strong>
                                            <em>{{ $prescription["jam"] }} WIB</em>
                                        </div>
                                    </div>
                                    <i></i>
                                    <div class="{{ $prescription["selesai"] ? "is-complete" : "is-current" }}">
                                        <span>
                                            <i class="bi {{ $prescription["selesai"] ? "bi-check2" : "bi-hourglass-split" }}"></i>
                                        </span>
                                        <div>
                                            <small>{{ $prescription["sumber"] === "pulang" ? "Validasi" : "Penyerahan" }}</small>
                                            <strong>
                                                {{ $prescription["selesai"]
                                                    ? $prescription["tanggal_selesai_lengkap"]
                                                    : $prescription["status_label"] }}
                                            </strong>
                                            <em>
                                                {{ $prescription["selesai"]
                                                    ? $prescription["jam_selesai"]." WIB"
                                                    : "Belum selesai" }}
                                            </em>
                                        </div>
                                    </div>
                                </div>

                                <details class="prescription-details">
                                    <summary>
                                        <span>
                                            <i class="bi bi-capsule"></i>
                                            Rincian obat
                                        </span>
                                        <em>
                                            {{ $prescription["jumlah_item"] }} item
                                            @if ($prescription["jumlah_racikan"] > 0)
                                                · {{ $prescription["jumlah_racikan"] }} racikan
                                            @endif
                                        </em>
                                        <i class="bi bi-chevron-down prescription-details-chevron"></i>
                                    </summary>

                                    <div class="prescription-items">
                                        @forelse ($prescription["obat"] as $medicine)
                                            <div class="prescription-item">
                                                <span class="prescription-item-icon">
                                                    <i class="bi bi-capsule-pill"></i>
                                                </span>
                                                <div>
                                                    <small>Obat</small>
                                                    <strong>{{ $medicine["nama"] }}</strong>
                                                    <p>
                                                        <i class="bi bi-activity"></i>
                                                        {{ $medicine["aturan_pakai"] }}
                                                    </p>
                                                </div>
                                                <span class="prescription-quantity">
                                                    <small>Jumlah</small>
                                                    <strong>
                                                        {{ $medicine["jumlah"] }}
                                                        {{ $medicine["satuan"] }}
                                                    </strong>
                                                </span>
                                            </div>
                                        @empty
                                            @if ($prescription["racikan"] === [])
                                                <div class="prescription-no-items">
                                                    <i class="bi bi-info-circle"></i>
                                                    Rincian obat belum tersedia.
                                                </div>
                                            @endif
                                        @endforelse

                                        @foreach ($prescription["racikan"] as $compound)
                                            <div class="prescription-compound">
                                                <div class="prescription-compound-heading">
                                                    <span class="prescription-item-icon">
                                                        <i class="bi bi-eyedropper"></i>
                                                    </span>
                                                    <div>
                                                        <small>Obat Racikan · {{ $compound["metode"] }}</small>
                                                        <strong>{{ $compound["nama"] }}</strong>
                                                        <p>
                                                            <i class="bi bi-activity"></i>
                                                            {{ $compound["aturan_pakai"] }}
                                                            @if ($compound["keterangan"])
                                                                · {{ $compound["keterangan"] }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                    <span class="prescription-quantity">
                                                        <small>Jumlah</small>
                                                        <strong>{{ $compound["jumlah"] }}</strong>
                                                    </span>
                                                </div>

                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($prescriptions->hasPages())
                    <nav class="prescription-pagination" aria-label="Navigasi resep obat">
                        @if ($prescriptions->previousPageUrl())
                            <a href="{{ $prescriptions->previousPageUrl() }}">
                                <i class="bi bi-chevron-left"></i>
                                Sebelumnya
                            </a>
                        @else
                            <span class="disabled">
                                <i class="bi bi-chevron-left"></i>
                                Sebelumnya
                            </span>
                        @endif

                        <span class="prescription-page-count">
                            Halaman <strong>{{ $prescriptions->currentPage() }}</strong>
                            dari <strong>{{ $prescriptions->lastPage() }}</strong>
                        </span>

                        @if ($prescriptions->nextPageUrl())
                            <a href="{{ $prescriptions->nextPageUrl() }}">
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

        <div class="prescription-disclaimer">
            <i class="bi bi-info-circle"></i>
            <p>
                <strong>Informasi penting</strong>
                Gunakan obat sesuai petunjuk dokter atau apoteker. Jangan mengubah dosis
                dan jangan menghentikan obat tanpa berkonsultasi dengan tenaga kesehatan.
            </p>
        </div>
    </div>
@endsection

@push("script")
    @include("e-pasien.menu.PermintaanTindakan.partials.mobileHistoryFilters")
@endpush
