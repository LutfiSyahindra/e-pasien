@extends("template.epasien.appPasien")

@section("title", "Pendaftaran Online | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/css/daftar-online.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $user = auth()->user();
        $patientName = trim((string) ($patient->nm_pasien ?? ($isRegistrationStaff ? "Belum dipilih" : ($user->name ?? "-"))));
        $medicalRecordNumber = trim((string) ($patient->no_rkm_medis ?? ($isRegistrationStaff ? $selectedMedicalRecordNumber : ($user->username ?? "-"))));
        $patientPhone = trim((string) ($patient->no_tlp ?? "-"));
        $patientAddress = trim((string) ($patient->alamat ?? "-"));
        $formatPatientBirthDate = static function ($birthDate): string {
            $rawBirthDate = trim((string) $birthDate);

            if ($rawBirthDate === "" || $rawBirthDate === "0000-00-00") {
                return "-";
            }

            try {
                return \Illuminate\Support\Carbon::parse($rawBirthDate)
                    ->locale("id")
                    ->translatedFormat("d F Y");
            } catch (\Throwable) {
                return $rawBirthDate;
            }
        };
        $patientBirthDate = $formatPatientBirthDate($patient->tgl_lahir ?? null);
        $patientSearchSummary = $patientSearchResults->count()." hasil ditemukan untuk “".$patientSearchQuery."”";
        if ($patientSearchBirthDate !== "") {
            $patientSearchSummary .= " dengan tanggal lahir ".$formatPatientBirthDate($patientSearchBirthDate);
        }
        $today = now()->toDateString();
        $hasPendingRegistration = ! empty($pendingRegistration);
    @endphp

    @include("e-pasien.menu.daftarOnline.modalMain")

    <div class="online-registration-page">
        <div class="online-breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right"></i>
            <span>Menu</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Pendaftaran Online</span>
        </div>

        <nav class="online-page-tabs" aria-label="Navigasi pendaftaran online">
            <a class="active" href="{{ route("daftarOnline.index") }}" aria-current="page">
                <i class="bi bi-calendar2-plus"></i>
                <span>Daftar Baru</span>
            </a>
            <a href="{{ route("daftarOnline.history") }}">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat</span>
            </a>
        </nav>

        <section class="online-header">
            <div class="online-header-copy">
                <span class="online-eyebrow"><i class="bi bi-calendar2-plus"></i> Rawat Jalan</span>
                <h1>Pendaftaran Online</h1>
                <div class="online-patient-line">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>{{ $medicalRecordNumber }}</span>
                    <span><i class="bi bi-telephone"></i>{{ $patientPhone }}</span>
                </div>
            </div>
            <div class="online-status-cluster">
                <div class="online-status-item tone-blue">
                    <i class="bi bi-clipboard2-check"></i>
                    <span>
                        <small>Status pendaftaran</small>
                        <strong>{{ $pendingRegistration["status"] ?? "Siap Daftar" }}</strong>
                    </span>
                </div>
                <div class="online-status-item tone-green">
                    <i class="bi bi-wallet2"></i>
                    <span>
                        <small>Pembayaran</small>
                        <strong>{{ $pendingRegistration["status_bayar"] ?? "Belum Ada" }}</strong>
                    </span>
                </div>
            </div>
        </section>

        @if ($isRegistrationStaff)
            <section class="online-history-panel mb-3">
                <form class="online-history-filter" method="GET" action="{{ route("daftarOnline.index") }}">
                    <div class="online-field-control">
                        <i class="bi bi-search"></i>
                        <input type="search" name="patient_search" class="form-control"
                            value="{{ $patientSearchQuery }}" maxlength="100"
                            placeholder="Masukkan No. RM atau nama pasien"
                            aria-label="Nomor rekam medis atau nama pasien" @disabled($connectionError)>
                    </div>
                    <div class="online-field-control">
                        <i class="bi bi-calendar3"></i>
                        <input type="date" name="patient_birth_date" class="form-control"
                            value="{{ $patientSearchBirthDate }}" max="{{ $today }}"
                            aria-label="Tanggal lahir pasien untuk pencarian berdasarkan nama"
                            title="Tanggal lahir wajib diisi saat mencari berdasarkan nama"
                            @disabled($connectionError)>
                    </div>
                    <button type="submit" class="online-button primary" @disabled($connectionError)>
                        <i class="bi bi-search"></i>
                        <span>Pilih Pasien</span>
                    </button>
                    @if ($patientSearchPerformed)
                        <a href="{{ route("daftarOnline.index") }}" class="online-button secondary">
                            <i class="bi bi-x-lg"></i>
                            <span>Ganti</span>
                        </a>
                    @endif
                </form>
                <div class="online-patient-search-help">
                    <small>
                        <i class="bi bi-info-circle"></i>
                        No. RM dapat dicari langsung. Pencarian nama wajib disertai tanggal lahir.
                    </small>
                    <small>Role Anda dapat mendaftarkan pasien lain, termasuk dengan penjamin BPJS Kesehatan.</small>
                </div>
                @if ($patientSearchResults->isNotEmpty())
                    <div class="online-patient-search-results" aria-label="Hasil pencarian pasien">
                        <div class="online-patient-search-heading">
                            <strong>Pilih pasien</strong>
                            <small>{{ $patientSearchSummary }}.</small>
                        </div>
                        <div class="online-patient-search-list">
                            @foreach ($patientSearchResults as $patientResult)
                                <a href="{{ route("daftarOnline.index", ["no_rkm_medis" => $patientResult->no_rkm_medis]) }}"
                                    class="online-patient-search-item">
                                    <div class="online-patient-search-identity">
                                        <strong>{{ trim((string) $patientResult->nm_pasien) }}</strong>
                                        <small>No. RM {{ trim((string) $patientResult->no_rkm_medis) }}</small>
                                    </div>
                                    <div class="online-patient-search-birth">
                                        <i class="bi bi-calendar3"></i>
                                        <span>
                                            <small>Tanggal lahir</small>
                                            <strong>{{ $formatPatientBirthDate($patientResult->tgl_lahir ?? null) }}</strong>
                                        </span>
                                    </div>
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @if ($connectionError)
            <div class="online-alert danger">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ $connectionError }}</span>
            </div>
        @elseif ($isRegistrationStaff && ! $patientSearchPerformed)
            <div class="online-alert warning">
                <i class="bi bi-person-vcard"></i>
                <span>Masukkan nomor rekam medis atau nama untuk memilih pasien yang akan didaftarkan.</span>
            </div>
        @elseif ($isRegistrationStaff && $patientSearchResults->isNotEmpty())
            <div class="online-alert warning">
                <i class="bi bi-people"></i>
                <span>Pilih salah satu pasien dari hasil pencarian di atas.</span>
            </div>
        @elseif (! $patient)
            <div class="online-alert warning">
                <i class="bi bi-person-x"></i>
                @if ($patientSearchBirthDate === "")
                    <span>
                        No. RM “{{ $patientSearchQuery ?: $medicalRecordNumber }}” tidak ditemukan.
                        Jika mencari berdasarkan nama, pilih tanggal lahir pasien.
                    </span>
                @else
                    <span>
                        Data pasien belum ditemukan untuk “{{ $patientSearchQuery }}” dengan tanggal lahir
                        {{ $formatPatientBirthDate($patientSearchBirthDate) }}.
                    </span>
                @endif
            </div>
        @endif

        <div @class(["online-layout", "single-panel" => $hasPendingRegistration])>
            <section class="online-main-panel">
                @if ($hasPendingRegistration)
                    <div class="online-active-visit">
                        <div class="online-active-heading">
                            <div class="online-section-header">
                                <span class="online-section-icon tone-green"><i class="bi bi-clipboard2-check"></i></span>
                                <div>
                                    <h2>Kunjungan Aktif</h2>
                                    <p>Pendaftaran baru tersedia setelah kunjungan ini diproses.</p>
                                </div>
                            </div>
                            <span class="online-status-pill"><i class="bi bi-hourglass-split"></i>{{ $pendingRegistration["status"] }}</span>
                        </div>

                        <div class="online-visit-banner">
                            <span class="online-visit-emblem"><i class="bi bi-hospital"></i></span>
                            <div class="online-visit-copy">
                                <span>Kunjungan rawat jalan</span>
                                <h2>{{ $pendingRegistration["poli"] }}</h2>
                                <p>{{ $pendingRegistration["dokter"] }}</p>
                            </div>
                            <div class="online-visit-number">
                                <span>No. Registrasi</span>
                                <strong>{{ $pendingRegistration["no_reg"] }}</strong>
                                <small>{{ $pendingRegistration["no_rawat"] }}</small>
                            </div>
                        </div>

                        <dl class="online-visit-details">
                            <div>
                                <dt><i class="bi bi-calendar3"></i>Tanggal kunjungan</dt>
                                <dd>{{ $pendingRegistration["hari"] }}, {{ $pendingRegistration["tanggal_label"] }}</dd>
                            </div>
                            <div>
                                <dt><i class="bi bi-clock"></i>Jam registrasi</dt>
                                <dd>{{ $pendingRegistration["jam"] }}</dd>
                            </div>
                            <div>
                                <dt><i class="bi bi-hospital"></i>Poli tujuan</dt>
                                <dd>{{ $pendingRegistration["poli"] }}</dd>
                            </div>
                            <div>
                                <dt><i class="bi bi-person-heart"></i>Dokter</dt>
                                <dd>{{ $pendingRegistration["dokter"] }}</dd>
                            </div>
                            <div>
                                <dt><i class="bi bi-shield-check"></i>Penjamin</dt>
                                <dd>{{ $pendingRegistration["penjamin"] }}</dd>
                            </div>
                            <div>
                                <dt><i class="bi bi-wallet2"></i>Status pembayaran</dt>
                                <dd>{{ $pendingRegistration["status_bayar"] }}</dd>
                            </div>
                        </dl>

                        <div class="online-visit-progress">
                            <h3>Progres Kunjungan</h3>
                            <div class="online-visit-timeline">
                                <div class="online-timeline-step done">
                                    <span><i class="bi bi-check2"></i></span>
                                    <div>
                                        <strong>Pendaftaran dibuat</strong>
                                        <small>No. {{ $pendingRegistration["no_reg"] }}</small>
                                    </div>
                                </div>
                                <div class="online-timeline-step active">
                                    <span><i class="bi bi-person-check"></i></span>
                                    <div>
                                        <strong>Verifikasi pendaftaran</strong>
                                        <small>Tunjukkan bukti kepada petugas.</small>
                                    </div>
                                </div>
                                <div class="online-timeline-step">
                                    <span><i class="bi bi-megaphone"></i></span>
                                    <div>
                                        <strong>Pelayanan poli</strong>
                                        <small>Menunggu proses berikutnya.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="online-visit-actions">
                            @if ($pendingRegistration["can_cancel"] ?? false)
                                <button type="button" id="cancelPendingRegistration" class="online-button danger">
                                    <i class="bi bi-x-circle"></i>
                                    <span>Batal Pendaftaran</span>
                                </button>
                            @endif
                            <button type="button" id="showPendingRegistrationModal" class="online-button primary">
                                <i class="bi bi-qr-code-scan"></i>
                                <span>Tampilkan Bukti Pendaftaran</span>
                            </button>
                        </div>
                    </div>
                @else
                    <form id="onlineRegistrationForm" class="online-form" autocomplete="off">
                        @if ($isRegistrationStaff)
                            <input type="hidden" id="no_rkm_medis" name="no_rkm_medis"
                                value="{{ $patient->no_rkm_medis ?? "" }}">
                        @endif
                        <div class="online-form-heading">
                            <div class="online-section-header">
                                <span class="online-section-icon tone-blue"><i class="bi bi-calendar-date"></i></span>
                                <div>
                                    <h2>Detail Kunjungan</h2>
                                    <p id="scheduleCountLabel">Pilih tanggal untuk memuat poli tersedia.</p>
                                </div>
                            </div>
                            <span class="online-required-note">
                                <i class="bi bi-asterisk"></i>
                                {{ $isRegistrationStaff ? "5" : "4" }} data wajib
                            </span>
                        </div>

                        <div class="online-choice-flow">
                            <div class="online-choice-card active" data-choice="date">
                                <div class="online-choice-title">
                                    <span>1</span>
                                    <div>
                                        <label for="tgl_registrasi">Tanggal kunjungan</label>
                                        <small id="selectedDayLabel">Hari layanan akan menyesuaikan tanggal.</small>
                                    </div>
                                </div>
                                <div class="online-field-control">
                                    <i class="bi bi-calendar2-week"></i>
                                    <input type="date" id="tgl_registrasi" name="tgl_registrasi" class="form-control"
                                        min="{{ $today }}" value="{{ $today }}" aria-describedby="selectedDayLabel error-tgl_registrasi"
                                        required @disabled($connectionError || ! $patient)>
                                    <button type="button" id="openDatePicker" class="online-picker-button"
                                        aria-label="Buka kalender" @disabled($connectionError || ! $patient)>
                                        <i class="bi bi-calendar-event"></i>
                                    </button>
                                </div>
                                <span class="invalid-feedback d-block" id="error-tgl_registrasi"></span>
                            </div>

                            <div class="online-choice-card" data-choice="clinic">
                                <div class="online-choice-title">
                                    <span>2</span>
                                    <div>
                                        <label for="kd_poli">Poli tujuan</label>
                                        <small>Pilih poliklinik sebelum memilih dokter.</small>
                                    </div>
                                </div>
                                <div class="online-field-control">
                                    <i class="bi bi-hospital"></i>
                                    <select id="kd_poli" name="kd_poli" class="form-select online-select"
                                        data-placeholder="Pilih poliklinik" aria-describedby="error-kd_poli" required
                                        @disabled($connectionError || ! $patient)>
                                        <option></option>
                                    </select>
                                </div>
                                <span class="invalid-feedback d-block" id="error-kd_poli"></span>
                            </div>

                            <div class="online-choice-card" data-choice="doctor">
                                <div class="online-choice-title">
                                    <span>3</span>
                                    <div>
                                        <label for="kd_dokter">Dokter</label>
                                        <small>Daftar dokter mengikuti poli yang dipilih.</small>
                                    </div>
                                </div>
                                <div class="online-field-control">
                                    <i class="bi bi-person-heart"></i>
                                    <select id="kd_dokter" name="kd_dokter" class="form-select online-select"
                                        data-placeholder="Pilih dokter" aria-describedby="error-kd_dokter error-jadwal"
                                        required disabled>
                                        <option></option>
                                    </select>
                                </div>
                                <span class="invalid-feedback d-block" id="error-kd_dokter"></span>
                                <span class="invalid-feedback d-block" id="error-jadwal"></span>
                            </div>

                            <div class="online-choice-card" data-choice="guarantor">
                                <div class="online-choice-title">
                                    <span>4</span>
                                    <div>
                                        <label for="kd_pj">Penjamin</label>
                                        <small>
                                            {{ $isRegistrationStaff
                                                ? "BPJS Kesehatan tersedia untuk role Anda."
                                                : "BPJS Kesehatan tidak ditampilkan." }}
                                        </small>
                                    </div>
                                </div>
                                <div class="online-field-control">
                                    <i class="bi bi-shield-check"></i>
                                    <select id="kd_pj" name="kd_pj" class="form-select online-select"
                                        data-placeholder="Pilih penjamin" aria-describedby="error-kd_pj" required
                                        @disabled($connectionError || ! $patient || empty($penjaminOptions))>
                                        <option></option>
                                        @foreach ($penjaminOptions as $penjamin)
                                            <option value="{{ $penjamin["kd_pj"] }}" data-name="{{ $penjamin["png_jawab"] }}">
                                                {{ $penjamin["png_jawab"] }} ({{ $penjamin["kd_pj"] }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <span class="invalid-feedback d-block" id="error-kd_pj"></span>

                                <div id="bpjsCardNumberField" class="online-bpjs-card-number" hidden>
                                    <div>
                                        <label for="no_peserta">No. Kartu BPJS</label>
                                        <small id="bpjsCardNumberHelp">Digunakan untuk mencari surat kontrol, lalu rujukan PCare dan RS.</small>
                                    </div>
                                    <div class="online-field-control">
                                        <i class="bi bi-credit-card-2-front"></i>
                                        <input type="text" id="no_peserta" name="no_peserta"
                                            class="form-control" maxlength="25" inputmode="numeric" pattern="[0-9]*"
                                            autocomplete="off" value="{{ $patient->no_peserta ?? "" }}"
                                            aria-describedby="bpjsCardNumberHelp error-no_peserta" disabled>
                                    </div>
                                    <span class="invalid-feedback d-block" id="error-no_peserta"></span>
                                </div>
                            </div>
                        </div>

                        <div id="scheduleList" class="online-flow-note">
                            @if (empty($penjaminOptions))
                                <div class="online-empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <strong>Penjamin belum tersedia</strong>
                                    <small>Data penjamin Khanza belum dapat dimuat.</small>
                                </div>
                            @else
                                <div class="online-empty-state compact">
                                    <i class="bi bi-calendar2-check"></i>
                                    <strong>Siapkan tanggal kunjungan</strong>
                                    <small>Poli dan dokter akan difilter otomatis.</small>
                                </div>
                            @endif
                        </div>
                        <span class="invalid-feedback d-block" id="error-pasien"></span>
                        <span class="invalid-feedback d-block" id="error-pendaftaran"></span>

                        <div class="online-actions">
                            <button type="button" id="resetOnlineRegistration" class="online-button secondary">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reset</span>
                            </button>
                            <button type="submit" id="submitOnlineRegistration" class="online-button primary"
                                @disabled($connectionError || ! $patient || empty($penjaminOptions))>
                                <i class="bi bi-send-check"></i>
                                <span>Simpan Pendaftaran</span>
                            </button>
                        </div>
                    </form>
                @endif
            </section>

            @unless ($hasPendingRegistration)
                <aside class="online-side-panel">
                <section class="online-patient-panel">
                    <div class="online-panel-title">
                        <i class="bi bi-person-lines-fill"></i>
                        <div class="online-panel-title-copy">
                            <h2>Data Pasien</h2>
                            <small>Identitas pasien yang akan didaftarkan</small>
                        </div>
                        <span @class([
                            "online-patient-selection-status",
                            "selected" => $patient,
                        ])>
                            <i class="bi {{ $patient ? "bi-check-circle-fill" : "bi-hourglass-split" }}"></i>
                            {{ $patient ? "Terpilih" : "Belum dipilih" }}
                        </span>
                    </div>

                    <div @class(["online-patient-profile", "empty" => ! $patient])>
                        <span class="online-patient-avatar">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <div class="online-patient-identity">
                            <small>Nama pasien</small>
                            <strong>{{ $patientName }}</strong>
                            <span>
                                <i class="bi bi-upc-scan"></i>
                                No. RM {{ $medicalRecordNumber ?: "-" }}
                            </span>
                        </div>
                    </div>

                    <dl class="online-patient-meta">
                        <div>
                            <dt><i class="bi bi-calendar3"></i>Tanggal lahir</dt>
                            <dd>{{ $patientBirthDate }}</dd>
                        </div>
                        <div>
                            <dt><i class="bi bi-telephone"></i>Telepon</dt>
                            <dd>{{ $patientPhone }}</dd>
                        </div>
                        <div class="wide">
                            <dt><i class="bi bi-geo-alt"></i>Alamat</dt>
                            <dd>{{ $patientAddress }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="online-summary">
                    <div class="online-summary-header">
                        <span><i class="bi bi-clipboard2-pulse"></i></span>
                        <div>
                            <h2>Ringkasan Kunjungan</h2>
                            <small id="summaryState">{{ $hasPendingRegistration ? "Pendaftaran aktif." : "Menunggu pilihan." }}</small>
                        </div>
                    </div>
                    <dl class="online-summary-list">
                        <div>
                            <dt>Tanggal</dt>
                            <dd id="summaryDate">
                                {{ $hasPendingRegistration ? $pendingRegistration["hari"].", ".$pendingRegistration["tanggal_label"] : "-" }}
                            </dd>
                        </div>
                        <div>
                            <dt>Poli</dt>
                            <dd id="summaryClinic">{{ $pendingRegistration["poli"] ?? "-" }}</dd>
                        </div>
                        <div>
                            <dt>Dokter</dt>
                            <dd id="summaryDoctor">{{ $pendingRegistration["dokter"] ?? "-" }}</dd>
                        </div>
                        <div>
                            <dt>{{ $hasPendingRegistration ? "Jam Registrasi" : "Jam Praktek" }}</dt>
                            <dd id="summaryTime">{{ $pendingRegistration["jam"] ?? "-" }}</dd>
                        </div>
                        <div>
                            <dt>Penjamin</dt>
                            <dd id="summaryGuarantor">{{ $pendingRegistration["penjamin"] ?? "-" }}</dd>
                        </div>
                        <div>
                            <dt>{{ $hasPendingRegistration ? "No. Registrasi" : "Estimasi No. Reg" }}</dt>
                            <dd id="summaryQueue">{{ $hasPendingRegistration ? "No. ".$pendingRegistration["no_reg"] : "-" }}</dd>
                        </div>
                    </dl>
                </section>
                </aside>
            @endunless
        </div>
    </div>
@endsection

@push("script")
    <script>
        window.daftarOnlineConfig = {
            schedulesUrl: @json(route("daftarOnline.schedules")),
            controlLettersUrl: @json(route("daftarOnline.suratKontrol")),
            controlLetterDetailUrl: @json(route("daftarOnline.suratKontrol.show", ["controlLetterNumber" => "__NUMBER__"])),
            antrolPreviewUrl: @json(route("daftarOnline.antrol.preview")),
            storeUrl: @json(route("daftarOnline.store")),
            cancelUrl: @json(route("daftarOnline.cancel")),
            csrfToken: @json(csrf_token()),
            today: @json($today),
            patientReady: @json((bool) $patient && ! $connectionError),
            isRegistrationStaff: @json($isRegistrationStaff),
            hasPendingRegistration: @json($hasPendingRegistration),
            pendingRegistration: @json($pendingRegistration),
            selectedMedicalRecordNumber: @json($patient->no_rkm_medis ?? null),
            showNotice: @json(! $isRegistrationStaff && ! $hasPendingRegistration),
        };
    </script>
    @include("e-pasien.menu.daftarOnline.jsMain")
@endpush
