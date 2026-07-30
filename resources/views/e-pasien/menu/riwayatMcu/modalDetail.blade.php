<div class="modal fade mcu-detail-modal" id="mcuDetailModal" tabindex="-1"
    aria-labelledby="mcuDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <span class="mcu-modal-icon"><i class="bi bi-clipboard2-heart"></i></span>
                <div>
                    <span class="mcu-modal-eyebrow">Dokumen Medical Check Up</span>
                    <h5 class="modal-title" id="mcuDetailModalLabel">Hasil Penilaian MCU</h5>
                    <small id="mcuDetailSubtitle">Memuat dokumen pemeriksaan...</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="mcu-modal-loading" id="mcuDetailLoading">
                    <span class="spinner-border" role="status" aria-hidden="true"></span>
                    <strong>Menyiapkan dokumen MCU</strong>
                    <small>Mohon tunggu sebentar.</small>
                </div>

                <div class="mcu-modal-error" id="mcuDetailError" hidden>
                    <span><i class="bi bi-exclamation-triangle"></i></span>
                    <strong>Detail belum dapat dimuat</strong>
                    <p id="mcuDetailErrorMessage">Terjadi kendala saat mengambil data.</p>
                    <button type="button" id="mcuDetailRetry">
                        <i class="bi bi-arrow-clockwise"></i>
                        Coba Lagi
                    </button>
                </div>

                <div class="mcu-document" id="mcuDetailDocument" hidden>
                    <section class="mcu-document-head">
                        <div class="mcu-document-number">
                            <span>No. Rawat</span>
                            <strong id="mcuTreatmentNumber">-</strong>
                            <small id="mcuMedicalRecordNumber">No. RM -</small>
                        </div>
                        <div class="mcu-document-meta">
                            <div>
                                <small>Pasien</small>
                                <strong id="mcuPatientName">-</strong>
                            </div>
                            <div>
                                <small>Waktu pemeriksaan</small>
                                <strong id="mcuAssessedAt">-</strong>
                            </div>
                            <div>
                                <small>Dokter pemeriksa</small>
                                <strong id="mcuDoctorName">-</strong>
                            </div>
                            <div>
                                <small>Unit pelayanan</small>
                                <strong id="mcuClinicName">-</strong>
                            </div>
                        </div>
                    </section>

                    <section class="mcu-outcome">
                        <article>
                            <span><i class="bi bi-clipboard2-pulse"></i></span>
                            <div>
                                <small>Kesimpulan dokter</small>
                                <p id="mcuConclusion">-</p>
                            </div>
                        </article>
                        <article>
                            <span><i class="bi bi-lightbulb"></i></span>
                            <div>
                                <small>Anjuran tindak lanjut</small>
                                <p id="mcuRecommendation">-</p>
                            </div>
                        </article>
                    </section>

                    <section class="mcu-vital-section">
                        <header>
                            <span><i class="bi bi-heart-pulse"></i></span>
                            <div>
                                <h3>Tanda Vital dan Antropometri</h3>
                                <small>Nilai saat pemeriksaan MCU dilakukan</small>
                            </div>
                        </header>
                        <div class="mcu-vital-grid" id="mcuVitalGrid"></div>
                    </section>

                    <div class="mcu-document-toolbar">
                        <div>
                            <strong>Rincian pemeriksaan</strong>
                            <small>Bagian pemeriksaan fisik dan penunjang</small>
                        </div>
                        <button type="button" id="mcuToggleSections" data-action="expand">
                            <i class="bi bi-arrows-expand"></i>
                            <span>Buka semua</span>
                        </button>
                    </div>

                    <div class="mcu-detail-sections" id="mcuDetailSections"></div>

                    <div class="mcu-document-note">
                        <i class="bi bi-info-circle"></i>
                        <p>
                            Dokumen ini menampilkan catatan pemeriksaan dari fasilitas kesehatan.
                            Konsultasikan hasil dan tindak lanjut dengan tenaga medis.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="mcu-print-button" id="mcuDetailPrint" hidden>
                    <i class="bi bi-printer"></i>
                    Cetak
                </button>
                <button type="button" class="mcu-close-button" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
