<div class="modal fade online-notice-modal" id="onlineRegistrationNoticeModal" tabindex="-1"
    aria-labelledby="onlineRegistrationNoticeModalLabel" aria-describedby="onlineRegistrationNoticeDescription"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="online-notice-icon"><i class="bi bi-info-lg"></i></span>
                <div>
                    <span class="online-notice-eyebrow">Penting untuk diketahui</span>
                    <h5 class="modal-title" id="onlineRegistrationNoticeModalLabel">Informasi Pendaftaran Online</h5>
                </div>
            </div>
            <div class="modal-body">
                <p id="onlineRegistrationNoticeDescription" class="online-notice-lead">
                    Pendaftaran ini tersedia untuk pasien dengan jenis penjamin berikut:
                </p>

                <div class="online-notice-eligibility" aria-label="Penjamin yang dapat didaftarkan">
                    <div>
                        <span><i class="bi bi-check2"></i></span>
                        <div>
                            <strong>UMUM</strong>
                            <small>Pasien dengan pembayaran pribadi.</small>
                        </div>
                    </div>
                    <div>
                        <span><i class="bi bi-check2"></i></span>
                        <div>
                            <strong>Asuransi selain BPJS Kesehatan</strong>
                            <small>Pilih nama asuransi pada bagian penjamin.</small>
                        </div>
                    </div>
                </div>

                @if ($isRegistrationStaff)
                    <div class="online-notice-bpjs">
                        <span><i class="bi bi-shield-check"></i></span>
                        <div>
                            <strong>Pendaftaran BPJS Kesehatan</strong>
                            <p>Role Anda telah diizinkan untuk memilih penjamin BPJS.</p>
                        </div>
                    </div>
                @else
                    <div class="online-notice-bpjs">
                        <span><i class="bi bi-shield-check"></i></span>
                        <div>
                            <strong>Pendaftaran BPJS Kesehatan</strong>
                            <p>
                                Dapat didaftarkan langsung tanpa surat kontrol atau rujukan,
                                kecuali jika poli yang dipilih adalah <b>IRM</b>.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="online-button primary w-100" data-bs-dismiss="modal">
                    <i class="bi bi-check2-circle"></i>
                    <span>Saya Mengerti</span>
                </button>
            </div>
        </div>
    </div>
</div>

@if ($isRegistrationStaff)
    <div class="modal fade online-mjkn-wizard-modal" id="mjknRegistrationWizardModal" tabindex="-1"
        aria-labelledby="mjknRegistrationWizardModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="online-antrol-preview-icon"><i class="bi bi-phone"></i></span>
                    <div>
                        <span class="online-notice-eyebrow">BPJS Mobile JKN (Antrol)</span>
                        <h5 class="modal-title" id="mjknRegistrationWizardModalLabel">Proses Daftar MJKN</h5>
                        <small>Cari dokumen BPJS, pilih referensi, lalu periksa payload antrean.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="online-mjkn-stepper" aria-label="Tahap proses daftar MJKN">
                        <span class="active" data-mjkn-step="search">
                            <i class="bi bi-search"></i>
                            <strong>1. Cari Dokumen</strong>
                        </span>
                        <span data-mjkn-step="document">
                            <i class="bi bi-file-earmark-check"></i>
                            <strong>2. Pilih Dokumen</strong>
                        </span>
                        <span data-mjkn-step="preview">
                            <i class="bi bi-clipboard2-check"></i>
                            <strong>3. Data Final</strong>
                        </span>
                    </div>

                    <section class="online-mjkn-panel active" data-mjkn-panel="document">
                        <div class="online-mjkn-panel-heading">
                            <div>
                                <h6 id="bpjsControlLetterTitle">Mencari Dokumen BPJS</h6>
                                <p id="bpjsControlLetterPeriod">Surat kontrol diperiksa lebih dahulu.</p>
                            </div>
                            <button type="button" id="retryBpjsDocumentSearch" class="online-button secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                                <span>Cari Ulang</span>
                            </button>
                        </div>

                        <div id="bpjsSearchJourney" class="online-bpjs-search-journey">
                            <article data-search-source="surat_kontrol">
                                <span><i class="bi bi-file-earmark-medical"></i></span>
                                <div><small>Tahap 1</small><strong>Surat Kontrol</strong><p>Menunggu pencarian.</p></div>
                            </article>
                            <article data-search-source="rujukan_pcare">
                                <span><i class="bi bi-building"></i></span>
                                <div><small>Tahap 2</small><strong>Rujukan PCare</strong><p>Dijalankan bila surat kontrol kosong.</p></div>
                            </article>
                            <article data-search-source="rujukan_rumah_sakit">
                                <span><i class="bi bi-hospital"></i></span>
                                <div><small>Tahap 3</small><strong>Rujukan RS</strong><p>Dijalankan bila PCare kosong.</p></div>
                            </article>
                        </div>

                        <div id="bpjsControlLetterListView">
                            <div id="bpjsControlLetterList" class="online-control-list"></div>
                        </div>
                        <div class="online-control-selection-status">
                            <p id="bpjsDocumentChoiceHint"><i class="bi bi-info-circle"></i> Pilih satu dokumen BPJS.</p>
                            <small>Data respons dokumen akan dipakai untuk melengkapi payload tambah antrean.</small>
                        </div>

                        <div class="online-actions">
                            <button type="button" class="online-button secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg"></i>
                                <span>Tutup</span>
                            </button>
                            <button type="button" class="online-button primary"
                                id="confirmBpjsDocumentChoice" disabled>
                                <i class="bi bi-arrow-right"></i>
                                <span>Lanjut Lihat Data Final</span>
                            </button>
                        </div>
                    </section>

                    <section class="online-mjkn-panel" data-mjkn-panel="preview" hidden>
                        <div class="online-antrol-json-heading">
                            <div>
                                <strong>Data final yang akan dikirim</strong>
                                <small>Respons BPJS dilengkapi dengan data pasien, mapping, jadwal, dan kuota Khanza.</small>
                            </div>
                        </div>
                        <pre id="antrolPayloadJson" class="online-antrol-json"
                            aria-label="Payload JSON Antrol">{}</pre>

                        <div class="online-actions">
                            <button type="button" id="backToBpjsDocumentChoice" class="online-button secondary">
                                <i class="bi bi-arrow-left"></i>
                                <span>Kembali Pilih Dokumen</span>
                            </button>
                            <button type="button" id="copyAntrolPayload" class="online-button secondary">
                                <i class="bi bi-copy"></i>
                                <span>Salin JSON</span>
                            </button>
                            <button type="button" id="submitMjknRegistration" class="online-button primary">
                                <i class="bi bi-send-check"></i>
                                <span>Daftarkan &amp; Kirim ke BPJS</span>
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="modal fade online-control-modal" id="bpjsControlLetterModal" tabindex="-1"
    aria-labelledby="bpjsControlLetterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <span class="online-control-icon"><i class="bi bi-file-earmark-medical"></i></span>
                <div>
                    <h5 class="modal-title" id="bpjsControlLetterModalLabel">Detail Surat Kontrol BPJS</h5>
                    <small>Informasi lengkap dokumen terpilih</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="bpjsControlLetterDetailView">
                    <div id="bpjsControlLetterDetail" class="online-control-detail"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="online-button secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                    <span>Tutup Detail</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade online-result-modal" id="onlineRegistrationResultModal" tabindex="-1"
    aria-labelledby="onlineRegistrationResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="online-result-icon"><i class="bi bi-check2-circle"></i></span>
                <div>
                    <h5 class="modal-title" id="onlineRegistrationResultModalLabel">Bukti Pendaftaran Online</h5>
                    <small>Tunjukkan bukti ini kepada petugas pendaftaran.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="online-ticket">
                    <span>No. Reg</span>
                    <strong id="resultNoReg">-</strong>
                    <small id="resultNoRawat">-</small>
                </div>
                <dl class="online-result-list">
                    <div>
                        <dt>Tanggal</dt>
                        <dd id="resultDate">-</dd>
                    </div>
                    <div>
                        <dt>Poli</dt>
                        <dd id="resultClinic">-</dd>
                    </div>
                    <div>
                        <dt>Dokter</dt>
                        <dd id="resultDoctor">-</dd>
                    </div>
                    <div>
                        <dt>Penjamin</dt>
                        <dd id="resultGuarantor">-</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd id="resultStatus">-</dd>
                    </div>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="online-button primary w-100" data-bs-dismiss="modal">
                    <i class="bi bi-check2"></i>
                    <span>Selesai</span>
                </button>
            </div>
        </div>
    </div>
</div>
