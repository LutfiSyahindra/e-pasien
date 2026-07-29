@extends("template.epasien.appPasien")

@section("title", "Riwayat Pemeriksaan | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/riwayat-pemeriksaan.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $user = auth()->user();
        $patientName = trim((string) ($patient->nm_pasien ?? $user->name ?? "-"));
        $medicalRecordNumber = trim((string) ($patient->no_rkm_medis ?? $user->username ?? "-"));
        $hasPatient = (bool) $patient;
        $filters = [
            "" => ["label" => "Semua", "icon" => "bi-grid", "count" => $counts["all"]],
            "Ralan" => ["label" => "Rawat Jalan", "icon" => "bi-person-walking", "count" => $counts["Ralan"]],
            "Ranap" => ["label" => "Rawat Inap", "icon" => "bi-hospital", "count" => $counts["Ranap"]],
        ];
        $persistentFilters = array_filter([
            "tanggal_mulai" => $startDate,
            "tanggal_selesai" => $endDate,
            "dokter" => $doctorCode,
        ], fn ($value) => $value !== null && $value !== "");
        $hasDetailFilters = count($persistentFilters) > 0;
        $hasActiveFilters = $careType !== null || $hasDetailFilters;
    @endphp

    @include("e-pasien.menu.riwayatPemeriksaan.modalMain")
    @include("e-pasien.menu.riwayatPemeriksaan.paymentModal")

    <div class="examination-history-page">
        <nav class="examination-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right"></i>
            <span>Menu</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Riwayat Pemeriksaan</span>
        </nav>

        <section class="examination-hero">
            <div class="examination-hero-copy">
                <span class="examination-eyebrow">
                    <i class="bi bi-shield-check"></i>
                    Kunjungan selesai
                </span>
                <h1>Riwayat Pemeriksaan</h1>
                <p>Lihat kembali kunjungan rawat jalan dan rawat inap Anda dengan tampilan yang ringkas.</p>
                <div class="examination-patient">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>No. RM {{ $medicalRecordNumber }}</span>
                </div>
            </div>
            <div class="examination-total">
                <span class="examination-total-icon"><i class="bi bi-journal-medical"></i></span>
                <span>
                    <small>Total pemeriksaan selesai</small>
                    <strong>{{ number_format($counts["all"], 0, ",", ".") }}</strong>
                    <em>kunjungan</em>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="examination-alert danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ $connectionError }}</span>
            </div>
        @elseif (! $hasPatient)
            <div class="examination-alert warning" role="alert">
                <i class="bi bi-person-x"></i>
                <span>Data pasien dengan nomor rekam medis {{ $medicalRecordNumber }} tidak ditemukan.</span>
            </div>
        @endif

        <section class="examination-content">
            <div class="examination-section-heading">
                <div>
                    <span class="examination-section-kicker">Jenis perawatan</span>
                    <h2>Filter riwayat Anda</h2>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("riwayatPemeriksaan.index") }}" class="examination-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset semua filter
                    </a>
                @endif
            </div>

            <nav class="examination-filters" aria-label="Filter jenis perawatan">
                @foreach ($filters as $value => $filter)
                    @php
                        $isActive = ($careType ?? "") === $value;
                        $filterQuery = $persistentFilters;
                        if ($value !== "") {
                            $filterQuery["status_lanjut"] = $value;
                        }
                        $filterUrl = route("riwayatPemeriksaan.index", $filterQuery);
                    @endphp
                    <a href="{{ $filterUrl }}" class="{{ $isActive ? "active" : "" }}"
                        @if ($isActive) aria-current="page" @endif>
                        <span class="examination-filter-icon"><i class="bi {{ $filter["icon"] }}"></i></span>
                        <span class="examination-filter-copy">
                            <strong>{{ $filter["label"] }}</strong>
                            <small>{{ $filter["count"] }} kunjungan</small>
                        </span>
                        <i class="bi bi-check-circle-fill examination-filter-check"></i>
                    </a>
                @endforeach
            </nav>

            <form action="{{ route("riwayatPemeriksaan.index") }}" method="GET"
                class="examination-advanced-filter">
                @if ($careType)
                    <input type="hidden" name="status_lanjut" value="{{ $careType }}">
                @endif

                <div class="examination-advanced-filter-heading">
                    <span><i class="bi bi-funnel"></i></span>
                    <div>
                        <strong>Persempit hasil</strong>
                        <small>Pilih rentang tanggal dan dokter pemeriksa.</small>
                    </div>
                </div>

                <label class="examination-filter-field">
                    <span>Tanggal mulai</span>
                    <span class="examination-filter-control">
                        <i class="bi bi-calendar-event"></i>
                        <input type="date" name="tanggal_mulai"
                            value="{{ old("tanggal_mulai", $startDate) }}">
                    </span>
                    @error("tanggal_mulai")
                        <small class="examination-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="examination-filter-field">
                    <span>Tanggal selesai</span>
                    <span class="examination-filter-control">
                        <i class="bi bi-calendar-check"></i>
                        <input type="date" name="tanggal_selesai"
                            value="{{ old("tanggal_selesai", $endDate) }}">
                    </span>
                    @error("tanggal_selesai")
                        <small class="examination-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="examination-filter-field">
                    <span>Dokter</span>
                    <span class="examination-filter-control">
                        <i class="bi bi-person-badge"></i>
                        <select name="dokter">
                            <option value="">Semua dokter</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor["code"] }}"
                                    @selected(old("dokter", $doctorCode) === $doctor["code"])>
                                    {{ $doctor["name"] }}
                                </option>
                            @endforeach
                        </select>
                    </span>
                    @error("dokter")
                        <small class="examination-filter-error">{{ $message }}</small>
                    @enderror
                </label>

                <div class="examination-filter-actions">
                    @if ($hasDetailFilters)
                        <a href="{{ route("riwayatPemeriksaan.index", array_filter([
                            "status_lanjut" => $careType,
                        ])) }}">
                            Hapus
                        </a>
                    @endif
                    <button type="submit">
                        <i class="bi bi-search"></i>
                        Terapkan
                    </button>
                </div>
            </form>

            <div class="examination-list-heading">
                <div>
                    <h2>{{ $careType ? $filters[$careType]["label"] : "Semua Pemeriksaan" }}</h2>
                    <p>
                        Menampilkan kunjungan dengan status pemeriksaan <strong>Sudah</strong>{{ $hasDetailFilters ? " sesuai filter tanggal dan dokter." : "." }}
                    </p>
                </div>
                <span>{{ $examinations->total() }} hasil</span>
            </div>

            @if ($connectionError || ! $hasPatient)
                <div class="examination-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Riwayat belum dapat ditampilkan</strong>
                    <p>Data pasien atau koneksi Khanza belum siap.</p>
                </div>
            @elseif ($examinations->count() === 0)
                <div class="examination-empty">
                    <span><i class="bi bi-clipboard2-x"></i></span>
                    <strong>
                        {{ $hasDetailFilters
                            ? "Tidak ada riwayat yang sesuai filter"
                            : ($careType ? "Tidak ada riwayat ".$filters[$careType]["label"] : "Belum ada riwayat pemeriksaan") }}
                    </strong>
                    <p>
                        {{ $hasDetailFilters
                            ? "Ubah rentang tanggal atau dokter untuk menemukan riwayat lainnya."
                            : ($careType
                            ? "Coba pilih jenis perawatan lain untuk melihat riwayat Anda."
                            : "Kunjungan yang telah selesai akan muncul di halaman ini.") }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("riwayatPemeriksaan.index") }}">Tampilkan semua riwayat</a>
                    @endif
                </div>
            @else
                <div class="examination-list">
                    @foreach ($examinations as $examination)
                        <article class="examination-card tone-{{ $examination["layanan_tone"] }}">
                            <div class="examination-date" aria-label="{{ $examination["tanggal_lengkap"] }}">
                                <span>{{ $examination["hari_short"] }}</span>
                                <strong>{{ $examination["tanggal_angka"] }}</strong>
                                <small>{{ $examination["bulan_short"] }}</small>
                            </div>

                            <div class="examination-card-body">
                                <div class="examination-card-badges">
                                    <span class="examination-care-badge">
                                        <i class="bi {{ $examination["layanan_tone"] === "ranap" ? "bi-hospital" : "bi-person-walking" }}"></i>
                                        {{ $examination["jenis_layanan"] }}
                                    </span>
                                    <span class="examination-completed-badge">
                                        <i class="bi bi-check2-circle"></i>
                                        Selesai
                                    </span>
                                </div>

                                <h3>{{ $examination["poli"] }}</h3>
                                <p class="examination-doctor">
                                    <i class="bi bi-person-badge"></i>
                                    {{ $examination["dokter"] }}
                                </p>

                                <div class="examination-meta">
                                    <span>
                                        <i class="bi bi-calendar3"></i>
                                        <small>Tanggal</small>
                                        <strong>{{ $examination["tanggal_lengkap"] }}</strong>
                                    </span>
                                    <span>
                                        <i class="bi bi-clock"></i>
                                        <small>Waktu daftar</small>
                                        <strong>{{ $examination["jam"] }}</strong>
                                    </span>
                                    <span>
                                        <i class="bi bi-shield-check"></i>
                                        <small>Penjamin</small>
                                        <strong>{{ $examination["penjamin"] }}</strong>
                                    </span>
                                    <span>
                                        <i class="bi bi-wallet2"></i>
                                        <small>Status bayar</small>
                                        <strong>{{ $examination["status_bayar"] }}</strong>
                                    </span>
                                </div>
                            </div>

                            <div class="examination-reference">
                                <span>
                                    <small>No. Rawat</small>
                                    <strong>{{ $examination["no_rawat"] ?: "-" }}</strong>
                                </span>
                                <span>
                                    <small>No. Registrasi</small>
                                    <strong>{{ $examination["no_reg"] ?: "-" }}</strong>
                                </span>
                                <div class="examination-actions">
                                    <button type="button" class="examination-payment-action"
                                        data-bs-toggle="modal" data-bs-target="#examinationPaymentModal"
                                        data-payment-url="{{ route("riwayatPemeriksaan.payment", [
                                            "no_rawat" => $examination["no_rawat"],
                                        ]) }}"
                                        data-no-rawat="{{ $examination["no_rawat"] }}">
                                        <i class="bi bi-wallet2"></i>
                                        <span>Pembayaran</span>
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                    <button type="button" class="examination-resume-action"
                                        data-bs-toggle="modal" data-bs-target="#examinationResumeModal"
                                        data-resume-url="{{ route("riwayatPemeriksaan.resume", [
                                            "no_rawat" => $examination["no_rawat"],
                                            "status_lanjut" => $examination["status_lanjut"],
                                        ]) }}"
                                        data-no-rawat="{{ $examination["no_rawat"] }}"
                                        data-service-type="{{ $examination["jenis_layanan"] }}">
                                        <i class="bi bi-file-earmark-medical"></i>
                                        <span>Lihat Resume</span>
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($examinations->hasPages())
                    <nav class="examination-pagination" aria-label="Navigasi halaman riwayat">
                        @if ($examinations->previousPageUrl())
                            <a href="{{ $examinations->previousPageUrl() }}">
                                <i class="bi bi-chevron-left"></i>
                                Sebelumnya
                            </a>
                        @else
                            <span class="disabled"><i class="bi bi-chevron-left"></i> Sebelumnya</span>
                        @endif

                        <span class="examination-page-number">
                            Halaman <strong>{{ $examinations->currentPage() }}</strong>
                            dari <strong>{{ $examinations->lastPage() }}</strong>
                        </span>

                        @if ($examinations->nextPageUrl())
                            <a href="{{ $examinations->nextPageUrl() }}">
                                Berikutnya
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        @else
                            <span class="disabled">Berikutnya <i class="bi bi-chevron-right"></i></span>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    </div>

@endsection

@push("script")
    @include("e-pasien.menu.riwayatPemeriksaan.jsMain")
@endpush
