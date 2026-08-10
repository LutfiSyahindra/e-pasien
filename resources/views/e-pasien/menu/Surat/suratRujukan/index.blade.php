@extends("template.epasien.appPasien")

@section("title", "Surat Rujukan | E-Pasien")

@push("style")
    <link href="{{ versioned_asset("epasien/assets/css/surat-rujukan.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $user = auth()->user();
        $patientName = trim((string) ($patient->nm_pasien ?? $user->name ?? "-"));
        $medicalRecordNumber = trim((string) ($patient->no_rkm_medis ?? $user->username ?? "-"));
        $hasPatient = (bool) $patient;
        $hasGeneralDateFilter = filled($startDate) || filled($endDate);
        $referralPageConfig = [
            "initialTab" => $activeTab,
            "initialOutgoingType" => $activeOutgoingType,
            "hasPatient" => $hasPatient,
            "incomingUrl" => route("suratRujukan.bpjs.masuk"),
            "outgoingUrl" => route("suratRujukan.bpjs.keluar"),
        ];
    @endphp

    <div class="referral-letter-page">
        <nav class="referral-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            <i class="bi bi-chevron-right"></i>
            <span>Surat</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Surat Rujukan</span>
        </nav>

        <section class="referral-hero">
            <div class="referral-hero-copy">
                <span class="referral-eyebrow">
                    <i class="bi bi-shield-check"></i>
                    Dokumen kesehatan pribadi
                </span>
                <h1>Surat Rujukan</h1>
                <p>
                    Temukan rujukan BPJS dari PCare atau rumah sakit, serta riwayat
                    rujukan keluar Anda dalam satu tempat.
                </p>
                <div class="referral-patient">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>No. RM {{ $medicalRecordNumber }}</span>
                </div>
            </div>

            <div class="referral-hero-summary" aria-label="Ringkasan rujukan keluar umum">
                <span class="referral-hero-icon">
                    <i class="bi bi-send-check"></i>
                </span>
                <span>
                    <small>Rujukan keluar umum</small>
                    <strong>{{ number_format($generalReferrals->total(), 0, ",", ".") }}</strong>
                    <em>dokumen tersimpan</em>
                </span>
            </div>
        </section>

        <div class="referral-main-tabs" role="tablist" aria-label="Jenis surat rujukan">
            <button type="button"
                data-referral-tab="masuk"
                role="tab"
                aria-controls="incomingReferralPanel">
                <span class="referral-tab-icon"><i class="bi bi-box-arrow-in-down"></i></span>
                <span>
                    <strong>Rujukan BPJS</strong>
                    <small>Masuk dari PCare &amp; RS</small>
                </span>
            </button>
            <button type="button"
                data-referral-tab="keluar"
                role="tab"
                aria-controls="outgoingReferralPanel">
                <span class="referral-tab-icon"><i class="bi bi-box-arrow-up-right"></i></span>
                <span>
                    <strong>Rujukan Keluar</strong>
                    <small>Umum &amp; BPJS</small>
                </span>
            </button>
        </div>

        <section id="incomingReferralPanel"
            class="referral-panel"
            role="tabpanel"
            aria-label="Rujukan BPJS">
            <div class="referral-section-heading">
                <div>
                    <span class="referral-kicker">Rujukan masuk</span>
                    <h2>Rujukan BPJS Anda</h2>
                    <p>
                        Sistem memeriksa PCare dan fasilitas rumah sakit secara
                        lengkap dalam satu pencarian menggunakan nomor kartu pasien.
                    </p>
                </div>
                <div id="incomingMaskedCard" class="referral-card-number" hidden>
                    <i class="bi bi-credit-card-2-front"></i>
                    <span>
                        <small>Kartu BPJS</small>
                        <strong></strong>
                    </span>
                </div>
            </div>

            <div class="referral-source-grid" aria-label="Sumber pencarian BPJS">
                <div class="referral-source" data-source-card="pcare">
                    <span><i class="bi bi-heart-pulse"></i></span>
                    <div>
                        <small>Fasilitas tingkat pertama</small>
                        <strong>PCare</strong>
                    </div>
                    <em data-source-status>Menunggu</em>
                </div>
                <div class="referral-source" data-source-card="rumah_sakit">
                    <span><i class="bi bi-hospital"></i></span>
                    <div>
                        <small>Fasilitas lanjutan</small>
                        <strong>Rumah Sakit</strong>
                    </div>
                    <em data-source-status>Menunggu</em>
                </div>
            </div>

            <div id="incomingReferralStatus" class="referral-state"></div>
            <div id="incomingReferralList" class="referral-list" aria-live="polite"></div>
        </section>

        <section id="outgoingReferralPanel"
            class="referral-panel"
            role="tabpanel"
            aria-label="Rujukan keluar"
            hidden>
            <div class="referral-section-heading">
                <div>
                    <span class="referral-kicker">Riwayat rujukan</span>
                    <h2>Rujukan Keluar</h2>
                    <p>
                        Pilih Umum untuk data rumah sakit atau BPJS untuk data
                        VClaim.
                    </p>
                </div>
            </div>

            <div class="referral-subtabs" role="tablist" aria-label="Jenis rujukan keluar">
                <button type="button"
                    data-outgoing-tab="umum"
                    role="tab"
                    aria-controls="generalOutgoingPanel">
                    <i class="bi bi-file-earmark-medical"></i>
                    <span>
                        <strong>Umum</strong>
                        <small>Penjamin selain BPJ</small>
                    </span>
                </button>
                <button type="button"
                    data-outgoing-tab="bpjs"
                    role="tab"
                    aria-controls="bpjsOutgoingPanel">
                    <i class="bi bi-shield-plus"></i>
                    <span>
                        <strong>BPJS</strong>
                        <small>Bridging VClaim</small>
                    </span>
                </button>
            </div>

            <div id="generalOutgoingPanel" class="referral-subpanel" role="tabpanel">
                <form method="GET"
                    action="{{ route("suratRujukan.index") }}"
                    class="referral-filter-card">
                    <input type="hidden" name="tab" value="keluar">
                    <input type="hidden" name="jenis" value="umum">
                    <div class="referral-filter-title">
                        <span><i class="bi bi-calendar3"></i></span>
                        <div>
                            <strong>Filter tanggal</strong>
                            <small>Kosongkan untuk melihat seluruh riwayat</small>
                        </div>
                    </div>
                    <label>
                        <span>Dari tanggal</span>
                        <input type="date" name="dari" value="{{ $startDate }}">
                    </label>
                    <label>
                        <span>Sampai tanggal</span>
                        <input type="date" name="sampai" value="{{ $endDate }}">
                    </label>
                    <button type="submit">
                        <i class="bi bi-search"></i>
                        Terapkan
                    </button>
                    @if ($hasGeneralDateFilter)
                        <a href="{{ route("suratRujukan.index", ["tab" => "keluar", "jenis" => "umum"]) }}">
                            Reset
                        </a>
                    @endif
                </form>

                <div class="referral-result-bar">
                    <span>
                        <i class="bi bi-folder2-open"></i>
                        <strong>{{ number_format($generalReferrals->total(), 0, ",", ".") }}</strong>
                        rujukan Umum
                    </span>
                    <small>Data dengan penjamin selain BPJ</small>
                </div>

                @if ($connectionError)
                    <div class="referral-state is-error">
                        <span class="referral-state-icon"><i class="bi bi-database-x"></i></span>
                        <strong>Data belum dapat dimuat</strong>
                        <p>{{ $connectionError }}</p>
                    </div>
                @elseif (! $hasPatient)
                    <div class="referral-state is-warning">
                        <span class="referral-state-icon"><i class="bi bi-person-x"></i></span>
                        <strong>Data pasien belum ditemukan</strong>
                        <p>Hubungi petugas untuk memeriksa keterhubungan akun dengan nomor rekam medis.</p>
                    </div>
                @elseif ($generalReferrals->isEmpty())
                    <div class="referral-state is-empty">
                        <span class="referral-state-icon"><i class="bi bi-file-earmark-x"></i></span>
                        <strong>Rujukan Umum belum ditemukan</strong>
                        <p>
                            {{ $hasGeneralDateFilter
                                ? "Tidak ada rujukan keluar pada rentang tanggal tersebut."
                                : "Belum ada riwayat rujukan keluar dengan penjamin selain BPJ." }}
                        </p>
                    </div>
                @else
                    <div class="referral-list">
                        @foreach ($generalReferrals as $referral)
                            <article class="referral-card tone-general">
                                <details>
                                    <summary>
                                        <span class="referral-date-block">
                                            <i class="bi bi-calendar2-week"></i>
                                            <span>
                                                <small>Tanggal rujukan</small>
                                                <strong>{{ $referral["tanggal"]["date_label"] }}</strong>
                                                @if ($referral["tanggal"]["time_label"])
                                                    <em>{{ $referral["tanggal"]["time_label"] }}</em>
                                                @endif
                                            </span>
                                        </span>
                                        <span class="referral-card-main">
                                            <span class="referral-badge general">
                                                <i class="bi bi-file-earmark-medical"></i>
                                                Rujukan Umum
                                            </span>
                                            <strong>{{ $referral["tujuan"] ?: "Tujuan belum dicatat" }}</strong>
                                            <small>
                                                <i class="bi bi-person-badge"></i>
                                                {{ $referral["nama_dokter"] ?: "Dokter belum dicatat" }}
                                            </small>
                                        </span>
                                        <span class="referral-expand">
                                            <span>Lihat rincian</span>
                                            <i class="bi bi-chevron-down"></i>
                                        </span>
                                    </summary>

                                    <div class="referral-card-details">
                                        <div class="referral-number-box">
                                            <span>
                                                <small>Nomor rujukan</small>
                                                <strong>{{ $referral["no_rujukan"] ?: "-" }}</strong>
                                            </span>
                                            <span>
                                                <small>Penjamin</small>
                                                <strong>{{ $referral["penjamin"] ?: $referral["kode_penjamin"] ?: "-" }}</strong>
                                            </span>
                                        </div>

                                        <div class="referral-detail-grid">
                                            <span>
                                                <small>Diagnosis</small>
                                                <strong>{{ $referral["diagnosa"] ?: "-" }}</strong>
                                            </span>
                                            <span>
                                                <small>Indikasi rujuk</small>
                                                <strong>{{ $referral["indikasi"] ?: "-" }}</strong>
                                            </span>
                                            <span>
                                                <small>Terapi</small>
                                                <strong>{{ $referral["terapi"] ?: "-" }}</strong>
                                            </span>
                                            <span>
                                                <small>Keterangan</small>
                                                <strong>{{ $referral["keterangan"] ?: "-" }}</strong>
                                            </span>
                                            <span>
                                                <small>Kategori</small>
                                                <strong>{{ $referral["kategori"] ?: "-" }}</strong>
                                            </span>
                                            <span>
                                                <small>Transportasi</small>
                                                <strong>{{ $referral["ambulans"] ?: "-" }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </details>
                            </article>
                        @endforeach
                    </div>

                    @if ($generalReferrals->hasPages())
                        <div class="referral-pagination">
                            {{ $generalReferrals->onEachSide(1)->links() }}
                        </div>
                    @endif
                @endif
            </div>

            <div id="bpjsOutgoingPanel"
                class="referral-subpanel"
                role="tabpanel"
                hidden>
                <form id="bpjsOutgoingForm" class="referral-filter-card">
                    <div class="referral-filter-title">
                        <span><i class="bi bi-calendar-range"></i></span>
                        <div>
                            <strong>Periode VClaim</strong>
                            <small>Pilih tanggal awal dan akhir pencarian</small>
                        </div>
                    </div>
                    <label>
                        <span>Tanggal mulai</span>
                        <input id="bpjsOutgoingStart"
                            type="date"
                            value="{{ $defaultBpjsStartDate }}"
                            required>
                    </label>
                    <label>
                        <span>Tanggal akhir</span>
                        <input id="bpjsOutgoingEnd"
                            type="date"
                            value="{{ $defaultBpjsEndDate }}"
                            required>
                    </label>
                    <button id="loadBpjsOutgoing" type="submit">
                        <i class="bi bi-search"></i>
                        <span>Cari rujukan</span>
                    </button>
                </form>

                <div id="outgoingMaskedCard" class="referral-card-number inline" hidden>
                    <i class="bi bi-credit-card-2-front"></i>
                    <span>
                        <small>Kartu BPJS</small>
                        <strong></strong>
                    </span>
                </div>
                <div id="bpjsOutgoingStatus" class="referral-state"></div>
                <div id="bpjsOutgoingList" class="referral-list" aria-live="polite"></div>
            </div>
        </section>
    </div>
@endsection

@push("script")
    <script>
        window.suratRujukanConfig = @json($referralPageConfig);
    </script>
    <script src="{{ versioned_asset("epasien/assets/js/surat-rujukan.js") }}"></script>
@endpush
