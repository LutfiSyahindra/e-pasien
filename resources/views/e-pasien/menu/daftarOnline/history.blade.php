@extends("template.epasien.appPasien")

@section("title", "Riwayat Pendaftaran Online | E-Pasien")

@push("style")
    <link href="{{ asset("epasien/assets/plugins/datetimepicker/css/classic.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/plugins/datetimepicker/css/classic.date.css") }}" rel="stylesheet" />
    <link href="{{ asset("epasien/assets/css/daftar-online.css") }}" rel="stylesheet" />
@endpush

@section("content")
    @php
        $user = auth()->user();
        $patientName = $viewAllPatients
            ? "Semua Pasien"
            : trim((string) ($patient->nm_pasien ?? $user->name ?? "-"));
        $medicalRecordNumber = $viewAllPatients
            ? "Akses role pendaftaran"
            : trim((string) ($patient->no_rkm_medis ?? $user->username ?? "-"));
        $patientPhone = $viewAllPatients ? "Seluruh penjamin" : trim((string) ($patient->no_tlp ?? "-"));
        $historyItems = collect($registrations->items())->values();
        $hasDateRange = $startDate !== "" || $endDate !== "";
        $hasFilter = $searchQuery !== "" || $guarantorCode !== "" || $hasDateRange;
        $activeFilterCount = ($searchQuery !== "" ? 1 : 0)
            + ($guarantorCode !== "" ? 1 : 0)
            + ($hasDateRange ? 1 : 0);
        $historyReady = $viewAllPatients || (bool) $patient;
    @endphp

    <div class="online-registration-page online-history-page">
        <div class="online-breadcrumb">
            <a href="{{ route("dashboard") }}" aria-label="Dashboard"><i class="bi bi-house-door"></i></a>
            <i class="bi bi-chevron-right"></i>
            <span>Menu</span>
            <i class="bi bi-chevron-right"></i>
            <span class="active">Riwayat Pendaftaran Online</span>
        </div>

        <nav class="online-page-tabs" aria-label="Navigasi pendaftaran online">
            <a href="{{ route("daftarOnline.index") }}">
                <i class="bi bi-calendar2-plus"></i>
                <span>Daftar Baru</span>
            </a>
            <a class="active" href="{{ route("daftarOnline.history") }}" aria-current="page">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat</span>
            </a>
        </nav>

        <section class="online-header">
            <div class="online-header-copy">
                <span class="online-eyebrow"><i class="bi bi-clock-history"></i> Rawat Jalan</span>
                <h1>Riwayat Pendaftaran Online</h1>
                <div class="online-patient-line">
                    <span><i class="bi bi-person-vcard"></i>{{ $patientName }}</span>
                    <span><i class="bi bi-upc-scan"></i>{{ $medicalRecordNumber }}</span>
                    <span><i class="bi bi-telephone"></i>{{ $patientPhone }}</span>
                </div>
            </div>
            <div class="online-status-cluster">
                <div class="online-status-item tone-blue">
                    <i class="bi bi-collection"></i>
                    <span>
                        <small>Total riwayat</small>
                        <strong>{{ $registrations->total() }} pendaftaran</strong>
                    </span>
                </div>
                <a href="{{ route("daftarOnline.index") }}" class="online-status-item online-status-link tone-green">
                    <i class="bi bi-calendar2-plus"></i>
                    <span>
                        <small>Pendaftaran</small>
                        <strong>Daftar Baru</strong>
                    </span>
                </a>
            </div>
        </section>

        @if ($connectionError)
            <div class="online-alert danger">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ $connectionError }}</span>
            </div>
        @elseif (! $historyReady)
            <div class="online-alert warning">
                <i class="bi bi-person-x"></i>
                <span>Data pasien belum ditemukan untuk nomor rekam medis {{ $medicalRecordNumber }}.</span>
            </div>
        @endif

        <section class="online-history-panel">
            <button type="button" class="online-history-mobile-filter-toggle"
                id="onlineHistoryMobileFilterToggle" aria-controls="onlineHistoryFilterPanel"
                aria-expanded="true">
                <span class="online-history-mobile-filter-icon"><i class="bi bi-sliders"></i></span>
                <span class="online-history-mobile-filter-copy">
                    <strong>Filter riwayat</strong>
                    <small>
                        {{ $hasFilter
                            ? $activeFilterCount." filter sedang aktif"
                            : "Semua data" }}
                    </small>
                </span>
                @if ($hasFilter)
                    <span class="online-history-mobile-filter-count">{{ $activeFilterCount }}</span>
                @endif
                <i class="bi bi-chevron-up online-history-mobile-filter-chevron"></i>
            </button>

            <div class="online-history-filter-panel" id="onlineHistoryFilterPanel">
                <form class="online-history-filter" method="GET" action="{{ route("daftarOnline.history") }}">
                    <div class="online-history-filter-field">
                        <label for="onlineHistorySearch">Pencarian</label>
                        <div class="online-field-control">
                            <i class="bi bi-search"></i>
                            <input type="search" id="onlineHistorySearch" name="q" class="form-control"
                                value="{{ $searchQuery }}"
                                placeholder="{{ $viewAllPatients ? "Cari pasien, no. RM, no. rawat, poli, dokter" : "Cari no. rawat, poli, dokter, status" }}"
                                @disabled($connectionError || ! $historyReady)>
                        </div>
                    </div>
                    <div class="online-history-filter-field">
                        <label for="onlineHistoryGuarantor">Penjamin</label>
                        <div class="online-field-control">
                            <i class="bi bi-shield-check"></i>
                            <select id="onlineHistoryGuarantor" name="kd_pj" class="form-select"
                                @disabled($connectionError || ! $historyReady)>
                                <option value="">Semua Penjamin</option>
                                @foreach ($penjaminOptions as $penjamin)
                                    <option value="{{ $penjamin["kd_pj"] }}" @selected($guarantorCode === $penjamin["kd_pj"])>
                                        {{ $penjamin["png_jawab"] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="online-history-date-range" role="group" aria-label="Rentang tanggal pendaftaran">
                        <div class="online-history-filter-field">
                            <label for="onlineHistoryStartDate">Dari tanggal</label>
                            <div class="online-field-control online-history-date-control">
                                <i class="bi bi-calendar-event"></i>
                                <input type="text" id="onlineHistoryStartDate" name="tanggal_mulai"
                                    class="form-control online-history-date-picker" value="{{ $startDate }}"
                                    data-value="{{ $startDate }}" placeholder="Pilih tanggal"
                                    autocomplete="off" inputmode="none"
                                    @disabled($connectionError || ! $historyReady)>
                            </div>
                        </div>
                        <span class="online-history-date-range-separator">s.d.</span>
                        <div class="online-history-filter-field">
                            <label for="onlineHistoryEndDate">Sampai tanggal</label>
                            <div class="online-field-control online-history-date-control">
                                <i class="bi bi-calendar-check"></i>
                                <input type="text" id="onlineHistoryEndDate" name="tanggal_selesai"
                                    class="form-control online-history-date-picker" value="{{ $endDate }}"
                                    data-value="{{ $endDate }}" placeholder="Pilih tanggal"
                                    autocomplete="off" inputmode="none"
                                    @disabled($connectionError || ! $historyReady)>
                            </div>
                        </div>
                    </div>
                    <div class="online-history-filter-actions">
                        <button type="submit" class="online-button primary" @disabled($connectionError || ! $historyReady)>
                            <i class="bi bi-funnel"></i>
                            <span>Terapkan Filter</span>
                        </button>
                        @if ($hasFilter)
                            <a href="{{ route("daftarOnline.history") }}" class="online-button secondary">
                                <i class="bi bi-x-lg"></i>
                                <span>Reset</span>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="online-history-list-heading">
                <div>
                    <span>{{ $hasFilter ? "Hasil filter" : "Riwayat terbaru" }}</span>
                    <h2>{{ $hasFilter ? "Pendaftaran yang ditemukan" : "Daftar Pendaftaran" }}</h2>
                    <p>{{ $hasFilter ? "Menampilkan riwayat yang sesuai pencarian Anda." : "Diurutkan dari kunjungan yang paling baru." }}</p>
                </div>
                <strong>{{ $registrations->total() }} hasil</strong>
            </div>

            @if ($connectionError || ! $historyReady)
                <div class="online-empty-state">
                    <i class="bi bi-inbox"></i>
                    <strong>Riwayat belum dapat ditampilkan</strong>
                    <small>Data pasien atau koneksi Khanza belum siap.</small>
                </div>
            @elseif ($registrations->count() === 0)
                <div class="online-empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <strong>{{ $hasFilter ? "Riwayat tidak ditemukan" : "Belum ada riwayat pendaftaran" }}</strong>
                    <small>{{ $hasFilter ? "Coba gunakan kata kunci atau penjamin lain." : "Pendaftaran yang sudah dibuat akan tampil di sini." }}</small>
                </div>
            @else
                <div class="online-history-list">
                    @foreach ($registrations as $registration)
                        <article class="online-history-card tone-{{ $registration["status_tone"] }}">
                            <div class="online-history-date">
                                <span>{{ $registration["hari_short"] }}</span>
                                <strong>{{ $registration["tanggal_angka"] }}</strong>
                                <small>{{ $registration["bulan_short"] }}</small>
                            </div>
                            <div class="online-history-content">
                                <div class="online-history-heading">
                                    <span class="online-history-status tone-{{ $registration["status_tone"] }}">
                                        <i class="bi bi-circle-fill"></i>
                                        {{ $registration["status"] }}
                                    </span>
                                    <span class="online-history-payment">
                                        <i class="bi bi-wallet2"></i>
                                        {{ $registration["status_bayar"] }}
                                    </span>
                                </div>
                                <h2>{{ $registration["poli"] }}</h2>
                                <p>{{ $registration["dokter"] }}</p>
                                <div class="online-history-meta">
                                    @if ($viewAllPatients)
                                        <span class="meta-patient"><i class="bi bi-person-vcard"></i>{{ $registration["nama_pasien"] }} · {{ $registration["no_rkm_medis"] }}</span>
                                    @endif
                                    <span class="meta-date"><i class="bi bi-calendar3"></i>{{ $registration["tanggal_lengkap"] }}</span>
                                    <span class="meta-time"><i class="bi bi-clock"></i>{{ $registration["jam"] }}</span>
                                    <span class="meta-guarantor"><i class="bi bi-shield-check"></i>{{ $registration["penjamin"] }}</span>
                                    @if ($registration["didaftarkan_oleh"] !== "-")
                                        <span class="meta-registrant"><i class="bi bi-person-check"></i>{{ $registration["didaftarkan_oleh"] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="online-history-action">
                                <span>
                                    <small>No. Reg</small>
                                    <strong>{{ $registration["no_reg"] ?: "-" }}</strong>
                                    <em>{{ $registration["no_rawat"] ?: "-" }}</em>
                                </span>
                                <button type="button" class="online-button secondary online-history-detail-button"
                                    data-history-index="{{ $loop->index }}">
                                    <i class="bi bi-eye"></i>
                                    <span>Lihat Detail</span>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($registrations->hasPages())
                    <nav class="online-history-pagination" aria-label="Navigasi riwayat pendaftaran">
                        @if ($registrations->previousPageUrl())
                            <a href="{{ $registrations->previousPageUrl() }}" class="online-button secondary">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </a>
                        @else
                            <span class="online-button secondary disabled">
                                <i class="bi bi-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </span>
                        @endif

                        <span class="online-history-page-count">
                            Halaman {{ $registrations->currentPage() }} dari {{ $registrations->lastPage() }}
                        </span>

                        @if ($registrations->nextPageUrl())
                            <a href="{{ $registrations->nextPageUrl() }}" class="online-button secondary">
                                <span>Berikutnya</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        @else
                            <span class="online-button secondary disabled">
                                <span>Berikutnya</span>
                                <i class="bi bi-chevron-right"></i>
                            </span>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    </div>

    <div class="modal fade online-result-modal online-history-modal" id="onlineHistoryDetailModal" tabindex="-1"
        aria-labelledby="onlineHistoryDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="online-result-icon"><i class="bi bi-clipboard2-pulse"></i></span>
                    <div>
                        <h5 class="modal-title" id="onlineHistoryDetailModalLabel">Detail Pendaftaran</h5>
                        <small id="historyDetailSubtitle">-</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="online-ticket">
                        <span>No. Reg</span>
                        <strong id="historyDetailNoReg">-</strong>
                        <small id="historyDetailNoRawat">-</small>
                    </div>
                    <dl class="online-result-list online-history-detail-list">
                        <div>
                            <dt>Pasien</dt>
                            <dd id="historyDetailPatient">-</dd>
                        </div>
                        <div>
                            <dt>Tanggal</dt>
                            <dd id="historyDetailDate">-</dd>
                        </div>
                        <div>
                            <dt>Poli</dt>
                            <dd id="historyDetailClinic">-</dd>
                        </div>
                        <div>
                            <dt>Dokter</dt>
                            <dd id="historyDetailDoctor">-</dd>
                        </div>
                        <div>
                            <dt>Penjamin</dt>
                            <dd id="historyDetailGuarantor">-</dd>
                        </div>
                        <div>
                            <dt>Status Kunjungan</dt>
                            <dd id="historyDetailStatus">-</dd>
                        </div>
                        <div>
                            <dt>Status Bayar</dt>
                            <dd id="historyDetailPayment">-</dd>
                        </div>
                        <div>
                            <dt>Jenis Daftar</dt>
                            <dd id="historyDetailRegisterType">-</dd>
                        </div>
                        <div>
                            <dt>Layanan</dt>
                            <dd id="historyDetailServiceType">-</dd>
                        </div>
                        <div>
                            <dt>Status Poli</dt>
                            <dd id="historyDetailClinicStatus">-</dd>
                        </div>
                        <div>
                            <dt>Umur Daftar</dt>
                            <dd id="historyDetailAge">-</dd>
                        </div>
                        <div>
                            <dt>Biaya Registrasi</dt>
                            <dd id="historyDetailFee">-</dd>
                        </div>
                        <div>
                            <dt>Penanggung Jawab</dt>
                            <dd id="historyDetailResponsible">-</dd>
                        </div>
                        <div class="wide">
                            <dt>Alamat Penanggung Jawab</dt>
                            <dd id="historyDetailAddress">-</dd>
                        </div>
                        <div class="wide">
                            <dt>Didaftarkan Oleh</dt>
                            <dd id="historyDetailRegisteredBy">-</dd>
                        </div>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="online-button primary w-100" data-bs-dismiss="modal">
                        <i class="bi bi-check2"></i>
                        <span>Tutup</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("script")
    <script src="{{ asset("epasien/assets/plugins/datetimepicker/js/picker.js") }}"></script>
    <script src="{{ asset("epasien/assets/plugins/datetimepicker/js/picker.date.js") }}"></script>
    <script>
        $(document).ready(function() {
            const historyItems = @json($historyItems);
            const detailModal = $('#onlineHistoryDetailModal');
            const historyPage = document.querySelector('.online-history-page');
            const mobileFilterToggle = document.getElementById('onlineHistoryMobileFilterToggle');
            const filterPanel = document.getElementById('onlineHistoryFilterPanel');

            $('.online-history-date-picker').pickadate({
                monthsFull: [
                    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                ],
                monthsShort: [
                    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
                ],
                weekdaysFull: [
                    'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
                ],
                weekdaysShort: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                today: 'Hari ini',
                clear: 'Hapus',
                close: 'Tutup',
                firstDay: 1,
                format: 'dd/mm/yyyy',
                formatSubmit: 'yyyy-mm-dd',
                hiddenName: true,
                selectMonths: true,
                selectYears: 100
            });

            if (historyPage && mobileFilterToggle && filterPanel) {
                const mobileFilterMedia = window.matchMedia('(max-width: 767.98px)');
                let mobileFilterExpanded = false;

                function syncMobileFilter() {
                    const isExpanded = !mobileFilterMedia.matches || mobileFilterExpanded;
                    const chevron = mobileFilterToggle.querySelector(
                        '.online-history-mobile-filter-chevron'
                    );

                    filterPanel.hidden = !isExpanded;
                    mobileFilterToggle.setAttribute('aria-expanded', String(isExpanded));

                    if (chevron) {
                        chevron.className = isExpanded
                            ? 'bi bi-chevron-up online-history-mobile-filter-chevron'
                            : 'bi bi-chevron-down online-history-mobile-filter-chevron';
                    }
                }

                mobileFilterToggle.addEventListener('click', function() {
                    mobileFilterExpanded = !mobileFilterExpanded;
                    syncMobileFilter();
                });

                if (typeof mobileFilterMedia.addEventListener === 'function') {
                    mobileFilterMedia.addEventListener('change', syncMobileFilter);
                } else {
                    mobileFilterMedia.addListener(syncMobileFilter);
                }

                historyPage.classList.add('is-filter-enhanced');
                syncMobileFilter();
            }

            function valueOrDash(value) {
                const text = String(value ?? '').trim();

                return text || '-';
            }

            function fillHistoryDetail(registration) {
                $('#historyDetailSubtitle').text(`${valueOrDash(registration.poli)} - ${valueOrDash(registration.tanggal_lengkap)}`);
                $('#historyDetailNoReg').text(valueOrDash(registration.no_reg));
                $('#historyDetailNoRawat').text(valueOrDash(registration.no_rawat));
                $('#historyDetailPatient').text(
                    `${valueOrDash(registration.nama_pasien)} (${valueOrDash(registration.no_rkm_medis)})`
                );
                $('#historyDetailDate').text(`${valueOrDash(registration.tanggal_lengkap)} ${valueOrDash(registration.jam)}`);
                $('#historyDetailClinic').text(valueOrDash(registration.poli));
                $('#historyDetailDoctor').text(valueOrDash(registration.dokter));
                $('#historyDetailGuarantor').text(valueOrDash(registration.penjamin));
                $('#historyDetailStatus').text(valueOrDash(registration.status));
                $('#historyDetailPayment').text(valueOrDash(registration.status_bayar));
                $('#historyDetailRegisterType').text(valueOrDash(registration.stts_daftar));
                $('#historyDetailServiceType').text(valueOrDash(registration.status_lanjut));
                $('#historyDetailClinicStatus').text(valueOrDash(registration.status_poli));
                $('#historyDetailAge').text(valueOrDash(registration.umurdaftar));
                $('#historyDetailFee').text(valueOrDash(registration.biaya_reg_label));
                $('#historyDetailResponsible').text(
                    `${valueOrDash(registration.penanggung_jawab)} (${valueOrDash(registration.hubungan_penanggung_jawab)})`
                );
                $('#historyDetailAddress').text(valueOrDash(registration.alamat_penanggung_jawab));
                $('#historyDetailRegisteredBy').text(
                    registration.didaftarkan_oleh === '-'
                        ? '-'
                        : `${valueOrDash(registration.didaftarkan_oleh)} · ${valueOrDash(registration.role_pendaftar)}`
                );
            }

            $('.online-history-detail-button').on('click', function() {
                const index = Number($(this).data('history-index'));
                const registration = historyItems[index];

                if (!registration) {
                    return;
                }

                fillHistoryDetail(registration);
                detailModal.modal('show');
            });
        });
    </script>
@endpush
