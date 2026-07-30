<div class="modal fade laboratory-result-modal operation-detail-modal"
    id="operationDetailModal" tabindex="-1"
    aria-labelledby="operationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <span class="laboratory-modal-icon"><i class="bi bi-bandaid"></i></span>
                <div>
                    <span class="laboratory-modal-eyebrow">Rincian layanan</span>
                    <h5 class="modal-title" id="operationDetailModalLabel">Operasi</h5>
                    <small id="operationDetailSubtitle">Memuat data operasi...</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="laboratory-modal-loading" id="operationDetailLoading">
                    <span class="spinner-border" role="status" aria-hidden="true"></span>
                    <strong>Menyiapkan detail operasi</strong>
                    <small>Mohon tunggu sebentar.</small>
                </div>

                <div class="laboratory-modal-error" id="operationDetailError" hidden>
                    <span><i class="bi bi-exclamation-triangle"></i></span>
                    <strong>Detail belum dapat dimuat</strong>
                    <p id="operationDetailErrorMessage">Terjadi kendala saat mengambil data.</p>
                    <button type="button" id="operationDetailRetry">
                        <i class="bi bi-arrow-clockwise"></i>
                        Coba Lagi
                    </button>
                </div>

                <div class="laboratory-result-document" id="operationDetailDocument" hidden>
                    <section class="laboratory-result-overview">
                        <div class="laboratory-result-order">
                            <span>No. Rawat</span>
                            <strong id="operationTreatmentNumber">-</strong>
                            <small id="operationDetailStatus">-</small>
                        </div>
                        <div class="laboratory-result-meta">
                            <div>
                                <small>Jadwal operasi</small>
                                <strong id="operationSchedule">-</strong>
                            </div>
                            <div>
                                <small>Dokter operator</small>
                                <strong id="operationDoctor">-</strong>
                            </div>
                            <div>
                                <small>Ruang operasi</small>
                                <strong id="operationRoom">-</strong>
                            </div>
                            <div>
                                <small>Jenis layanan</small>
                                <strong id="operationCareType">-</strong>
                            </div>
                        </div>
                    </section>

                    <section class="laboratory-result-summary">
                        <div>
                            <span><i class="bi bi-list-check"></i></span>
                            <p><small>Tindakan dijadwalkan</small><strong id="operationBookedCount">0</strong></p>
                        </div>
                        <div>
                            <span><i class="bi bi-heart-pulse"></i></span>
                            <p><small>Pelaksanaan</small><strong id="operationPerformedState">Belum</strong></p>
                        </div>
                        <div>
                            <span><i class="bi bi-file-earmark-medical"></i></span>
                            <p><small>Laporan</small><strong id="operationReportState">Belum</strong></p>
                        </div>
                    </section>

                    <section class="operation-detail-section">
                        <header>
                            <span><i class="bi bi-calendar2-check"></i></span>
                            <div>
                                <h3>Jadwal dan tindakan</h3>
                                <small>Data awal dari booking operasi</small>
                            </div>
                        </header>
                        <div class="operation-detail-grid">
                            <div>
                                <small>Unit pelayanan</small>
                                <strong id="operationClinic">-</strong>
                            </div>
                            <div>
                                <small>Kategori</small>
                                <strong id="operationCategory">-</strong>
                            </div>
                            <div>
                                <small>Durasi jadwal</small>
                                <strong id="operationScheduledDuration">-</strong>
                            </div>
                            <div>
                                <small>Status booking</small>
                                <strong id="operationBookingStatus">-</strong>
                            </div>
                        </div>
                        <div class="operation-procedure-list" id="operationBookedProcedures"></div>
                    </section>

                    <section class="operation-detail-section">
                        <header>
                            <span><i class="bi bi-heart-pulse"></i></span>
                            <div>
                                <h3>Pelaksanaan operasi</h3>
                                <small>Konfirmasi tindakan aktual dari catatan operasi</small>
                            </div>
                        </header>
                        <div class="operation-inline-empty" id="operationPerformedEmpty">
                            <i class="bi bi-clock-history"></i>
                            <span>Pelaksanaan operasi belum tercatat.</span>
                        </div>
                        <div id="operationPerformedContent" hidden>
                            <div class="operation-detail-grid">
                                <div>
                                    <small>Waktu pelaksanaan</small>
                                    <strong id="operationPerformedAt">-</strong>
                                </div>
                                <div>
                                    <small>Operator utama</small>
                                    <strong id="operationMainOperator">-</strong>
                                </div>
                                <div>
                                    <small>Dokter anestesi</small>
                                    <strong id="operationAnesthetist">-</strong>
                                </div>
                                <div>
                                    <small>Jenis anestesi</small>
                                    <strong id="operationAnesthesia">-</strong>
                                </div>
                            </div>
                            <div class="operation-procedure-list" id="operationPerformedProcedures"></div>
                        </div>
                    </section>

                    <section class="operation-detail-section operation-report-section">
                        <header>
                            <span><i class="bi bi-file-earmark-medical"></i></span>
                            <div>
                                <h3>Laporan operasi</h3>
                                <small>Catatan klinis setelah tindakan</small>
                            </div>
                        </header>
                        <div class="operation-inline-empty" id="operationReportEmpty">
                            <i class="bi bi-file-earmark-clock"></i>
                            <span>Laporan operasi belum tersedia.</span>
                        </div>
                        <div id="operationReportContent" hidden>
                            <div class="operation-detail-grid">
                                <div>
                                    <small>Mulai operasi</small>
                                    <strong id="operationReportStarted">-</strong>
                                </div>
                                <div>
                                    <small>Selesai operasi</small>
                                    <strong id="operationReportFinished">-</strong>
                                </div>
                                <div>
                                    <small>Durasi aktual</small>
                                    <strong id="operationActualDuration">-</strong>
                                </div>
                                <div>
                                    <small>Permintaan PA</small>
                                    <strong id="operationPathologyRequest">-</strong>
                                </div>
                            </div>
                            <div class="operation-diagnosis-grid">
                                <div>
                                    <small>Diagnosis preoperasi</small>
                                    <strong id="operationPreDiagnosis">-</strong>
                                </div>
                                <div>
                                    <small>Diagnosis postoperasi</small>
                                    <strong id="operationPostDiagnosis">-</strong>
                                </div>
                                <div>
                                    <small>Jaringan dieksekusi</small>
                                    <strong id="operationTissue">-</strong>
                                </div>
                            </div>
                            <article class="operation-report-narrative">
                                <small>Uraian laporan</small>
                                <p id="operationReportNarrative">-</p>
                            </article>
                        </div>
                    </section>

                    <div class="laboratory-result-note">
                        <i class="bi bi-shield-exclamation"></i>
                        <p>
                            <strong>Catatan untuk pasien</strong>
                            Informasi ini bukan pengganti penjelasan dokter. Konsultasikan hasil operasi dan rencana kontrol Anda.
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
                    id="operationDetailPrint" hidden>
                    <i class="bi bi-printer"></i>
                    Cetak Detail
                </button>
            </div>
        </div>
    </div>
</div>
