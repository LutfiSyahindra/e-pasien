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
                        <span><i class="bi bi-phone"></i></span>
                        <div>
                            <strong>Pendaftaran BPJS Kesehatan</strong>
                            <p>Silakan lakukan pendaftaran melalui aplikasi <b>Mobile JKN</b>.</p>
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
