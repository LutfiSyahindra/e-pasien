@extends("template.epasien.appPasien")

@section("title", "Riwayat MCU | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/riwayat-mcu.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $user = auth()->user();
        $patientName = trim((string) ($patient->nm_pasien ?? $user->name ?? "-"));
        $medicalRecordNumber = trim((string) ($patient->no_rkm_medis ?? $user->username ?? "-"));
        $hasPatient = (bool) $patient;
        $hasActiveFilters = filled($startDate)
            || filled($endDate)
            || filled($doctorCode)
            || filled($search);
        $activeFilterCount = collect([
            $startDate,
            $endDate,
            $doctorCode,
            $search,
        ])->filter(fn ($value) => filled($value))->count();
    @endphp

    @include("e-pasien.menu.riwayatMcu.modalDetail")

    <div class="mcu-page">
        <nav class="mcu-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right"></i>
            <span>Menu</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Riwayat MCU</span>
        </nav>

        <section class="mcu-hero">
            <div class="mcu-hero-copy">
                <span class="mcu-eyebrow">
                    <i class="bi bi-shield-check"></i>
                    Dokumen kesehatan pribadi
                </span>
                <h1>Riwayat Medical Check Up</h1>
                <p>
                    Lihat rangkuman pemeriksaan, tanda vital, hasil pemeriksaan fisik,
                    kesimpulan, dan anjuran dokter dalam satu dokumen.
                </p>
                <div class="mcu-patient">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>No. RM {{ $medicalRecordNumber }}</span>
                </div>
            </div>

            <div class="mcu-hero-total">
                <span class="mcu-hero-total-icon"><i class="bi bi-clipboard2-heart"></i></span>
                <span>
                    <small>Total MCU</small>
                    <strong>{{ number_format($summary["all"], 0, ",", ".") }}</strong>
                    <em>pemeriksaan</em>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="mcu-alert danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ $connectionError }}</span>
            </div>
        @elseif (! $hasPatient)
            <div class="mcu-alert warning" role="alert">
                <i class="bi bi-person-x"></i>
                <span>
                    Data pasien dengan nomor rekam medis
                    {{ $medicalRecordNumber }} tidak ditemukan.
                </span>
            </div>
        @endif

        <section class="mcu-summary" aria-label="Ringkasan riwayat MCU">
            <article>
                <span class="tone-primary"><i class="bi bi-journal-medical"></i></span>
                <div>
                    <small>Seluruh riwayat</small>
                    <strong>{{ number_format($summary["all"], 0, ",", ".") }}</strong>
                    <em>dokumen MCU</em>
                </div>
            </article>
            <article>
                <span class="tone-success"><i class="bi bi-calendar2-check"></i></span>
                <div>
                    <small>Tahun {{ now()->year }}</small>
                    <strong>{{ number_format($summary["current_year"], 0, ",", ".") }}</strong>
                    <em>pemeriksaan</em>
                </div>
            </article>
            <article class="mcu-summary-latest">
                <span class="tone-warm"><i class="bi bi-clock-history"></i></span>
                <div>
                    <small>Pemeriksaan terakhir</small>
                    <strong>{{ $summary["latest_label"] }}</strong>
                    <em>{{ $summary["latest_at"] ? "Data terbaru Anda" : "Belum ada riwayat" }}</em>
                </div>
            </article>
        </section>

        <section class="mcu-content">
            <div class="mcu-section-heading">
                <div>
                    <span>Dokumen pemeriksaan</span>
                    <h2>Riwayat MCU Anda</h2>
                    <p>Urutan data berdasarkan penilaian terbaru.</p>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route("riwayatMcu.index") }}" class="mcu-reset-all">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset semua filter
                    </a>
                @endif
            </div>

            <details class="mcu-filter-panel" open
                data-has-active-filters="{{ $hasActiveFilters || $errors->any() ? "true" : "false" }}">
                <summary>
                    <span class="mcu-filter-summary-icon"><i class="bi bi-sliders"></i></span>
                    <span>
                        <strong>Filter dan pencarian</strong>
                        <small>
                            {{ $hasActiveFilters
                                ? $activeFilterCount." filter sedang aktif"
                                : "Cari berdasarkan dokter atau tanggal" }}
                        </small>
                    </span>
                    @if ($hasActiveFilters)
                        <em>{{ $activeFilterCount }}</em>
                    @endif
                    <i class="bi bi-chevron-down mcu-filter-chevron"></i>
                </summary>

                <form action="{{ route("riwayatMcu.index") }}" method="GET"
                    class="mcu-filter">
                    <label class="mcu-filter-field mcu-filter-search">
                        <span>Pencarian</span>
                        <span class="mcu-filter-control">
                            <i class="bi bi-search"></i>
                            <input type="search" name="q" value="{{ old("q", $search) }}"
                                placeholder="Dokter, unit, kesimpulan..."
                                @disabled($connectionError || ! $hasPatient)>
                        </span>
                        @error("q")
                            <small class="mcu-filter-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="mcu-filter-field">
                        <span>Dokter pemeriksa</span>
                        <span class="mcu-filter-control">
                            <i class="bi bi-person-badge"></i>
                            <select name="dokter" @disabled($connectionError || ! $hasPatient)>
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
                            <small class="mcu-filter-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="mcu-filter-field">
                        <span>Tanggal mulai</span>
                        <span class="mcu-filter-control">
                            <i class="bi bi-calendar-event"></i>
                            <input type="date" name="tanggal_mulai"
                                value="{{ old("tanggal_mulai", $startDate) }}"
                                @disabled($connectionError || ! $hasPatient)>
                        </span>
                        @error("tanggal_mulai")
                            <small class="mcu-filter-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="mcu-filter-field">
                        <span>Tanggal selesai</span>
                        <span class="mcu-filter-control">
                            <i class="bi bi-calendar-check"></i>
                            <input type="date" name="tanggal_selesai"
                                value="{{ old("tanggal_selesai", $endDate) }}"
                                @disabled($connectionError || ! $hasPatient)>
                        </span>
                        @error("tanggal_selesai")
                            <small class="mcu-filter-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <div class="mcu-filter-actions">
                        @if ($hasActiveFilters)
                            <a href="{{ route("riwayatMcu.index") }}">Hapus filter</a>
                        @endif
                        <button type="submit" @disabled($connectionError || ! $hasPatient)>
                            <i class="bi bi-funnel"></i>
                            Tampilkan Hasil
                        </button>
                    </div>
                </form>
            </details>

            <div class="mcu-list-heading">
                <div>
                    <h3>{{ $hasActiveFilters ? "Hasil Pencarian" : "Semua Pemeriksaan" }}</h3>
                    <p>
                        {{ $hasActiveFilters
                            ? "Menampilkan riwayat sesuai filter yang dipilih."
                            : "Pilih salah satu pemeriksaan untuk melihat dokumen lengkap." }}
                    </p>
                </div>
                <span>{{ number_format($assessments->total(), 0, ",", ".") }} hasil</span>
            </div>

            @if ($connectionError || ! $hasPatient)
                <div class="mcu-empty">
                    <span><i class="bi bi-cloud-slash"></i></span>
                    <strong>Riwayat MCU belum dapat ditampilkan</strong>
                    <p>Data pasien atau koneksi Khanza belum siap.</p>
                </div>
            @elseif ($assessments->count() === 0)
                <div class="mcu-empty">
                    <span><i class="bi bi-clipboard2-x"></i></span>
                    <strong>
                        {{ $hasActiveFilters
                            ? "Pemeriksaan tidak ditemukan"
                            : "Belum ada riwayat MCU" }}
                    </strong>
                    <p>
                        {{ $hasActiveFilters
                            ? "Ubah kata kunci, dokter, atau rentang tanggal pencarian."
                            : "Hasil Medical Check Up akan muncul pada halaman ini setelah dicatat." }}
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route("riwayatMcu.index") }}">Tampilkan semua riwayat</a>
                    @endif
                </div>
            @else
                <div class="mcu-list">
                    @foreach ($assessments as $assessment)
                        <article class="mcu-card">
                            <div class="mcu-card-accent"></div>

                            <div class="mcu-date" aria-label="{{ $assessment["tanggal_lengkap"] }}">
                                <span>{{ $assessment["hari_short"] }}</span>
                                <strong>{{ $assessment["tanggal_angka"] }}</strong>
                                <small>{{ $assessment["bulan_short"] }}</small>
                            </div>

                            <div class="mcu-card-body">
                                <div class="mcu-card-top">
                                    <div>
                                        <div class="mcu-card-badges">
                                            <em class="mcu-card-mobile-date">
                                                <i class="bi bi-calendar2-check"></i>
                                                {{ $assessment["tanggal_lengkap"] }}
                                            </em>
                                            <span>
                                                <i class="bi bi-check-circle"></i>
                                                Penilaian tersedia
                                            </span>
                                            <em class="mcu-card-time">
                                                <i class="bi bi-clock"></i>
                                                {{ $assessment["jam"] }} WIB
                                            </em>
                                        </div>
                                        <h3>{{ $assessment["poli"] }}</h3>
                                        <p>
                                            <i class="bi bi-person-badge"></i>
                                            {{ $assessment["dokter"] }}
                                        </p>
                                    </div>
                                    <div class="mcu-treatment-number">
                                        <small>No. Rawat</small>
                                        <strong>{{ $assessment["no_rawat"] ?: "-" }}</strong>
                                    </div>
                                </div>

                                <div class="mcu-vitals">
                                    <div>
                                        <small>Tekanan darah</small>
                                        <strong>{{ $assessment["vitals"]["tekanan_darah"] }}</strong>
                                    </div>
                                    <div>
                                        <small>Nadi</small>
                                        <strong>{{ $assessment["vitals"]["nadi"] }}</strong>
                                    </div>
                                    <div>
                                        <small>Suhu</small>
                                        <strong>{{ $assessment["vitals"]["suhu"] }}</strong>
                                    </div>
                                    <div>
                                        <small>Keadaan umum</small>
                                        <strong>{{ $assessment["keadaan"] }}</strong>
                                    </div>
                                </div>

                                <div class="mcu-card-result">
                                    <div>
                                        <small><i class="bi bi-clipboard2-pulse"></i> Kesimpulan</small>
                                        <p>{{ $assessment["kesimpulan"] }}</p>
                                    </div>
                                    <div>
                                        <small><i class="bi bi-lightbulb"></i> Anjuran</small>
                                        <p>{{ $assessment["anjuran"] }}</p>
                                    </div>
                                </div>
                            </div>

                            <aside class="mcu-card-action">
                                <span><i class="bi bi-file-earmark-medical"></i></span>
                                <small>Dokumen MCU</small>
                                <strong>Hasil lengkap</strong>
                                <button type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#mcuDetailModal"
                                    data-detail-url="{{ route("riwayatMcu.detail", [
                                        "no_rawat" => $assessment["no_rawat"],
                                    ]) }}">
                                    Lihat Detail
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </aside>
                        </article>
                    @endforeach
                </div>

                @if ($assessments->hasPages())
                    <div class="mcu-pagination">
                        <span>
                            Menampilkan {{ $assessments->firstItem() }}–{{ $assessments->lastItem() }}
                            dari {{ $assessments->total() }} hasil
                        </span>
                        {{ $assessments->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection

@push("script")
    @include("e-pasien.menu.riwayatMcu.jsMain")
@endpush
