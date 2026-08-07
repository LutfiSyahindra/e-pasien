<div class="modal fade laboratory-result-modal" id="laboratoryResultModal" tabindex="-1"
    aria-labelledby="laboratoryResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <span class="laboratory-modal-icon"><i class="bi bi-file-earmark-medical"></i></span>
                <div>
                    <span class="laboratory-modal-eyebrow">Hasil pemeriksaan</span>
                    <h5 class="modal-title" id="laboratoryResultModalLabel">Laboratorium</h5>
                    <small id="laboratoryResultSubtitle">Memuat data permintaan...</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="laboratory-modal-loading" id="laboratoryResultLoading">
                    <span class="spinner-border" role="status" aria-hidden="true"></span>
                    <strong>Menyiapkan hasil laboratorium</strong>
                    <small>Mohon tunggu sebentar.</small>
                </div>

                <div class="laboratory-modal-error" id="laboratoryResultError" hidden>
                    <span><i class="bi bi-exclamation-triangle"></i></span>
                    <strong>Hasil belum dapat dimuat</strong>
                    <p id="laboratoryResultErrorMessage">Terjadi kendala saat mengambil data.</p>
                    <button type="button" id="laboratoryResultRetry">
                        <i class="bi bi-arrow-clockwise"></i>
                        Coba Lagi
                    </button>
                </div>

                <div class="laboratory-modal-empty" id="laboratoryResultEmpty" hidden>
                    <span><i class="bi bi-clipboard2-x"></i></span>
                    <strong>Rincian hasil belum tersedia</strong>
                    <p>Waktu hasil telah tercatat, tetapi parameter hasil belum ditemukan.</p>
                </div>

                <div class="laboratory-result-document" id="laboratoryResultDocument" hidden>
                    <section class="laboratory-result-overview">
                        <div class="laboratory-result-order">
                            <span>No. Permintaan</span>
                            <strong id="laboratoryResultOrder">-</strong>
                            <small id="laboratoryResultTreatment">-</small>
                        </div>
                        <div class="laboratory-result-meta">
                            <div>
                                <small>Tanggal hasil</small>
                                <strong id="laboratoryResultDate">-</strong>
                            </div>
                            <div>
                                <small>Dokter perujuk</small>
                                <strong id="laboratoryResultDoctor">-</strong>
                            </div>
                            <div>
                                <small>Unit pelayanan</small>
                                <strong id="laboratoryResultClinic">-</strong>
                            </div>
                            <div>
                                <small>Jenis layanan</small>
                                <strong id="laboratoryResultCare">-</strong>
                            </div>
                        </div>
                    </section>

                    <section class="laboratory-result-clinical">
                        <div>
                            <span><i class="bi bi-activity"></i></span>
                            <p>
                                <small>Diagnosa klinis</small>
                                <strong id="laboratoryResultDiagnosis">-</strong>
                            </p>
                        </div>
                        <div>
                            <span><i class="bi bi-info-circle"></i></span>
                            <p>
                                <small>Informasi tambahan</small>
                                <strong id="laboratoryResultInformation">-</strong>
                            </p>
                        </div>
                    </section>

                    <section class="laboratory-result-summary">
                        <div>
                            <span><i class="bi bi-collection"></i></span>
                            <p><small>Jenis pemeriksaan</small><strong id="laboratoryResultGroupCount">0</strong></p>
                        </div>
                        <div>
                            <span><i class="bi bi-list-check"></i></span>
                            <p><small>Parameter hasil</small><strong id="laboratoryResultParameterCount">0</strong></p>
                        </div>
                        <div>
                            <span><i class="bi bi-flag"></i></span>
                            <p><small>Dengan catatan</small><strong id="laboratoryResultNoteCount">0</strong></p>
                        </div>
                    </section>

                    <div class="laboratory-result-groups" id="laboratoryResultGroups"></div>

                    <div class="laboratory-result-note">
                        <i class="bi bi-shield-exclamation"></i>
                        <p>
                            <strong>Catatan untuk pasien</strong>
                            Nilai rujukan dapat berbeda menurut metode pemeriksaan, usia, dan kondisi pasien.
                            Konsultasikan hasil ini kepada dokter.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="laboratory-modal-secondary"
                    data-bs-dismiss="modal">
                    Tutup
                </button>
                <button type="button" class="laboratory-modal-primary"
                    id="laboratoryResultPrint" hidden>
                    <i class="bi bi-file-earmark-pdf"></i>
                    Cetak / Unduh PDF
                </button>
            </div>
        </div>
    </div>
</div>
