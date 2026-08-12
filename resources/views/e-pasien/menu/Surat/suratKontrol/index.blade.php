@extends("template.epasien.appPasien")

@section("title", "Surat Kontrol | E-Pasien")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/surat-kontrol.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $user = auth()->user();
        $patientName = trim((string) ($patient->nm_pasien ?? $user->name ?? "-"));
        $medicalRecordNumber = trim((string) ($patient->no_rkm_medis ?? $user->username ?? "-"));
        $hasPatient = (bool) $patient;
        $statusFilters = [
            "" => [
                "label" => "Semua",
                "icon" => "bi-grid",
                "count" => $generalCounts["all"],
                "tone" => "all",
            ],
            "Menunggu" => [
                "label" => "Menunggu",
                "icon" => "bi-hourglass-split",
                "count" => $generalCounts["waiting"],
                "tone" => "waiting",
            ],
            "Sudah Periksa" => [
                "label" => "Selesai",
                "icon" => "bi-check2-circle",
                "count" => $generalCounts["examined"],
                "tone" => "examined",
            ],
            "Batal Periksa" => [
                "label" => "Batal",
                "icon" => "bi-x-circle",
                "count" => $generalCounts["cancelled"],
                "tone" => "cancelled",
            ],
        ];
    @endphp

    <div class="control-letter-page">
        <nav class="control-letter-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right"></i>
            <span>Surat</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Surat Kontrol</span>
        </nav>

        <section class="control-letter-hero">
            <div class="control-letter-hero-copy">
                <span class="control-letter-eyebrow">
                    <i class="bi bi-shield-check"></i>
                    Dokumen kesehatan pribadi
                </span>
                <h1>Surat Kontrol</h1>
                <p>
                    Lihat jadwal kontrol umum dari rumah sakit dan surat kontrol BPJS
                    dalam satu halaman yang ringkas.
                </p>
                <div class="control-letter-patient">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>No. RM {{ $medicalRecordNumber }}</span>
                </div>
            </div>

            <div class="control-letter-hero-summary" aria-label="Ringkasan surat kontrol umum">
                <span class="control-letter-hero-icon">
                    <i class="bi bi-file-earmark-medical"></i>
                </span>
                <span>
                    <small>Surat kontrol umum</small>
                    <strong>{{ number_format($generalCounts["all"], 0, ",", ".") }}</strong>
                    <em>dokumen tersimpan</em>
                </span>
            </div>
        </section>

        @if ($connectionError)
            <div class="control-letter-alert danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ $connectionError }}</span>
            </div>
        @elseif (! $hasPatient)
            <div class="control-letter-alert warning" role="alert">
                <i class="bi bi-person-x"></i>
                <span>Data pasien dengan nomor rekam medis {{ $medicalRecordNumber }} tidak ditemukan.</span>
            </div>
        @endif

        <section class="control-letter-content">
            <div class="control-letter-heading">
                <div>
                    <span class="control-letter-section-kicker">Pilih sumber surat</span>
                    <h2>Dokumen kontrol Anda</h2>
                </div>
                <span class="control-letter-security">
                    <i class="bi bi-lock-fill"></i>
                    Hanya dapat dilihat oleh akun Anda
                </span>
            </div>

            <div class="control-letter-tabs" role="tablist" aria-label="Jenis surat kontrol">
                <button type="button" id="generalControlTab" role="tab"
                    data-control-tab="umum"
                    aria-controls="generalControlPanel"
                    aria-selected="{{ $activeTab === "umum" ? "true" : "false" }}"
                    class="{{ $activeTab === "umum" ? "active" : "" }}">
                    <span class="control-letter-tab-icon umum">
                        <i class="bi bi-hospital"></i>
                    </span>
                    <span>
                        <strong>UMUM</strong>
                        <small>Data rumah sakit</small>
                    </span>
                    <span class="control-letter-tab-count">{{ $generalCounts["all"] }}</span>
                </button>
                <button type="button" id="bpjsControlTab" role="tab"
                    data-control-tab="bpjs"
                    aria-controls="bpjsControlPanel"
                    aria-selected="{{ $activeTab === "bpjs" ? "true" : "false" }}"
                    class="{{ $activeTab === "bpjs" ? "active" : "" }}">
                    <span class="control-letter-tab-icon bpjs">
                        <i class="bi bi-shield-plus"></i>
                    </span>
                    <span>
                        <strong>BPJS</strong>
                        <small>Bridging VClaim</small>
                    </span>
                    <i class="bi bi-chevron-right control-letter-tab-arrow"></i>
                </button>
            </div>

            <div id="generalControlPanel" class="control-letter-panel"
                role="tabpanel" aria-labelledby="generalControlTab"
                @if ($activeTab !== "umum") hidden @endif>
                <div class="control-letter-panel-heading">
                    <div>
                        <span class="control-letter-panel-icon umum">
                            <i class="bi bi-hospital"></i>
                        </span>
                        <span>
                            <strong>Surat Kontrol UMUM</strong>
                            <small>Jadwal dan rencana tindak lanjut dari rumah sakit</small>
                        </span>
                    </div>
                    @if ($status)
                        <a href="{{ route("suratKontrol.index", ["tab" => "umum"]) }}"
                            class="control-letter-reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Reset filter
                        </a>
                    @endif
                </div>

                <nav class="control-letter-filters" aria-label="Filter status surat kontrol umum">
                    @foreach ($statusFilters as $value => $filter)
                        @php
                            $isActive = ($status ?? "") === $value;
                            $query = ["tab" => "umum"];
                            if ($value !== "") {
                                $query["status"] = $value;
                            }
                        @endphp
                        <a href="{{ route("suratKontrol.index", $query) }}"
                            class="tone-{{ $filter["tone"] }} {{ $isActive ? "active" : "" }}"
                            @if ($isActive) aria-current="page" @endif>
                            <span><i class="bi {{ $filter["icon"] }}"></i></span>
                            <strong>{{ $filter["label"] }}</strong>
                            <small>{{ $filter["count"] }}</small>
                        </a>
                    @endforeach
                </nav>

                @if ($connectionError || ! $hasPatient)
                    <div class="control-letter-empty compact">
                        <span><i class="bi bi-cloud-slash"></i></span>
                        <strong>Data belum dapat ditampilkan</strong>
                        <p>Silakan coba kembali setelah data pasien tersedia.</p>
                    </div>
                @elseif ($generalLetters->isEmpty())
                    <div class="control-letter-empty">
                        <span><i class="bi bi-file-earmark-text"></i></span>
                        <strong>
                            {{ $status
                                ? "Tidak ada surat dengan status ".$status
                                : "Belum ada surat kontrol umum" }}
                        </strong>
                        <p>
                            {{ $status
                                ? "Pilih status lain untuk melihat dokumen kontrol Anda."
                                : "Surat kontrol dari rumah sakit akan muncul di bagian ini." }}
                        </p>
                    </div>
                @else
                    <div class="control-letter-list">
                        @foreach ($generalLetters as $letter)
                            <article class="control-letter-card tone-{{ $letter["status_tone"] }}">
                                <details>
                                    <summary>
                                        <span class="control-letter-date">
                                            <i class="bi bi-calendar2-check"></i>
                                            <span>
                                                <small>Tanggal kontrol</small>
                                                <strong>{{ $letter["tanggal_datang"]["date_label"] }}</strong>
                                                @if ($letter["tanggal_datang"]["time_label"])
                                                    <em>{{ $letter["tanggal_datang"]["time_label"] }}</em>
                                                @endif
                                            </span>
                                        </span>

                                        <span class="control-letter-card-main">
                                            <span class="control-letter-status {{ $letter["status_tone"] }}">
                                                <i class="bi {{
                                                    $letter["status_tone"] === "waiting"
                                                        ? "bi-hourglass-split"
                                                        : ($letter["status_tone"] === "examined"
                                                            ? "bi-check2-circle"
                                                            : ($letter["status_tone"] === "cancelled"
                                                                ? "bi-x-circle"
                                                                : "bi-info-circle"))
                                                }}"></i>
                                                {{ $letter["status"] }}
                                            </span>
                                            <strong>{{ $letter["diagnosa"] ?: "Diagnosis belum dicatat" }}</strong>
                                            <small>
                                                <i class="bi bi-person-badge"></i>
                                                {{ $letter["nama_dokter"] ?: ($letter["kd_dokter"] ?: "Dokter belum ditentukan") }}
                                            </small>
                                        </span>

                                        <span class="control-letter-expand">
                                            <span class="desktop-label">Lihat rincian</span>
                                            <i class="bi bi-chevron-down"></i>
                                        </span>
                                    </summary>

                                    <div class="control-letter-card-detail">
                                        @if ($letter["is_upcoming"])
                                            <div class="control-letter-next-visit">
                                                <i class="bi bi-bell"></i>
                                                <span>
                                                    <strong>Jadwal kontrol mendatang</strong>
                                                    <small>Datang sesuai tanggal dan waktu yang tertera.</small>
                                                </span>
                                            </div>
                                        @endif

                                        <div class="control-letter-detail-grid">
                                            <section>
                                                <span class="control-letter-detail-icon therapy">
                                                    <i class="bi bi-capsule-pill"></i>
                                                </span>
                                                <div>
                                                    <small>Terapi</small>
                                                    <p>{{ $letter["terapi"] ?: "Belum ada catatan terapi." }}</p>
                                                </div>
                                            </section>
                                            <section>
                                                <span class="control-letter-detail-icon reason">
                                                    <i class="bi bi-chat-left-text"></i>
                                                </span>
                                                <div>
                                                    <small>Alasan kontrol</small>
                                                    @if ($letter["alasan1"] || $letter["alasan2"])
                                                        <p>{{ collect([$letter["alasan1"], $letter["alasan2"]])->filter()->join(" · ") }}</p>
                                                    @else
                                                        <p>Belum ada catatan alasan kontrol.</p>
                                                    @endif
                                                </div>
                                            </section>
                                            <section>
                                                <span class="control-letter-detail-icon plan">
                                                    <i class="bi bi-signpost-split"></i>
                                                </span>
                                                <div>
                                                    <small>Rencana tindak lanjut</small>
                                                    @if ($letter["rtl1"] || $letter["rtl2"])
                                                        <p>{{ collect([$letter["rtl1"], $letter["rtl2"]])->filter()->join(" · ") }}</p>
                                                    @else
                                                        <p>Belum ada rencana tindak lanjut.</p>
                                                    @endif
                                                </div>
                                            </section>
                                        </div>

                                        <div class="control-letter-meta">
                                            <span>
                                                <small>Tanggal dibuat/rujukan</small>
                                                <strong>{{ $letter["tanggal_rujukan"]["date_label"] }}</strong>
                                            </span>
                                            <span>
                                                <small>Dokter</small>
                                                <strong>{{ $letter["nama_dokter"] ?: "-" }}</strong>
                                                @if ($letter["kd_dokter"])
                                                    <em>Kode {{ $letter["kd_dokter"] }}</em>
                                                @endif
                                            </span>
                                            <span>
                                                <small>Tahun dokumen</small>
                                                <strong>{{ $letter["tahun"] ?: "-" }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </details>
                            </article>
                        @endforeach
                    </div>

                    @if ($generalLetters->hasPages())
                        <nav class="control-letter-pagination" aria-label="Navigasi surat kontrol umum">
                            @if ($generalLetters->previousPageUrl())
                                <a href="{{ $generalLetters->previousPageUrl() }}">
                                    <i class="bi bi-chevron-left"></i>
                                    Sebelumnya
                                </a>
                            @else
                                <span class="disabled">
                                    <i class="bi bi-chevron-left"></i>
                                    Sebelumnya
                                </span>
                            @endif

                            <span>
                                Halaman <strong>{{ $generalLetters->currentPage() }}</strong>
                                dari <strong>{{ $generalLetters->lastPage() }}</strong>
                            </span>

                            @if ($generalLetters->nextPageUrl())
                                <a href="{{ $generalLetters->nextPageUrl() }}">
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
            </div>

            <div id="bpjsControlPanel" class="control-letter-panel bpjs-panel"
                role="tabpanel" aria-labelledby="bpjsControlTab"
                @if ($activeTab !== "bpjs") hidden @endif>
                <div class="control-letter-panel-heading bpjs-heading">
                    <div>
                        <span class="control-letter-panel-icon bpjs">
                            <i class="bi bi-shield-plus"></i>
                        </span>
                        <span>
                            <strong>Surat Kontrol BPJS</strong>
                            <small>Data terkini dari layanan bridging VClaim</small>
                        </span>
                    </div>
                    <span class="control-letter-card-number" id="bpjsMaskedCard" hidden>
                        <i class="bi bi-credit-card-2-front"></i>
                        <span>
                            <small>No. kartu</small>
                            <strong></strong>
                        </span>
                    </span>
                </div>

                <fieldset class="control-letter-search-mode">
                    <legend>Cara mencari jadwal</legend>
                    <label class="active">
                        <input type="radio" name="bpjs_search_mode" value="automatic"
                            checked @disabled(! $hasPatient)>
                        <span class="control-letter-search-mode-icon automatic">
                            <i class="bi bi-stars"></i>
                        </span>
                        <span>
                            <strong>Cari otomatis</strong>
                            <small>3 bulan lalu sampai 3 bulan ke depan</small>
                        </span>
                        <i class="bi bi-check-circle-fill control-letter-search-mode-check"></i>
                    </label>
                    <label>
                        <input type="radio" name="bpjs_search_mode" value="period"
                            @disabled(! $hasPatient)>
                        <span class="control-letter-search-mode-icon period">
                            <i class="bi bi-calendar3"></i>
                        </span>
                        <span>
                            <strong>Pilih bulan</strong>
                            <small>Untuk mencari surat kontrol yang lebih lama</small>
                        </span>
                        <i class="bi bi-check-circle-fill control-letter-search-mode-check"></i>
                    </label>
                </fieldset>

                <div class="control-letter-bpjs-tools">
                    <div class="control-letter-auto-search" id="bpjsAutomaticSearchHelp">
                        <span><i class="bi bi-search-heart"></i></span>
                        <span>
                            <strong>Tidak perlu mengingat bulan kontrol</strong>
                            <small>Sistem akan memeriksa tujuh bulan sekaligus.</small>
                        </span>
                    </div>
                    <label for="bpjsControlPeriod" id="bpjsPeriodGroup" hidden>
                        <span>Bulan yang ingin diperiksa</span>
                        <span class="control-letter-period-field">
                            <i class="bi bi-calendar3"></i>
                            <input type="month" id="bpjsControlPeriod"
                                value="{{ $defaultBpjsPeriod }}"
                                aria-describedby="bpjsPeriodHelp"
                                @disabled(! $hasPatient)>
                        </span>
                        <small id="bpjsPeriodHelp">Pencarian juga mencakup satu bulan sebelumnya.</small>
                    </label>
                    <button type="button" id="loadBpjsControlLetters"
                        @disabled(! $hasPatient)>
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Cari jadwal otomatis</span>
                    </button>
                </div>

                <div class="control-letter-bpjs-note">
                    <i class="bi bi-info-circle"></i>
                    <span>
                        Nomor kartu BPJS diambil otomatis dari data pasien Anda dan
                        disamarkan untuk menjaga privasi.
                    </span>
                </div>

                <div id="bpjsControlStatus" class="control-letter-bpjs-status"
                    role="status" aria-live="polite"></div>
                <div id="bpjsSearchSummary" class="control-letter-search-summary"
                    aria-live="polite" hidden></div>
                <div id="bpjsControlList" class="control-letter-list bpjs-list"></div>
            </div>
        </section>

        <div class="control-letter-disclaimer">
            <i class="bi bi-info-circle"></i>
            <p>
                <strong>Informasi penting</strong>
                Bawa identitas dan dokumen pendukung saat kontrol. Bila jadwal berubah,
                hubungi rumah sakit sebelum tanggal kunjungan.
            </p>
        </div>
    </div>
@endsection

@push("script")
    <script>
        window.suratKontrolConfig = {
            bpjsUrl: @json(route("suratKontrol.bpjs")),
            initialTab: @json($activeTab),
            hasPatient: @json($hasPatient)
        };
    </script>
    <script src="{{ versioned_asset("epasien/assets/js/surat-kontrol.js") }}"></script>
@endpush
