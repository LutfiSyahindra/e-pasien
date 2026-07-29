<div class="modal fade laboratory-result-modal radiology-result-modal" id="radiologyResultModal"
    tabindex="-1" aria-labelledby="radiologyResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <span class="laboratory-modal-icon"><i class="bi bi-radioactive"></i></span>
                <div>
                    <span class="laboratory-modal-eyebrow">Hasil pemeriksaan</span>
                    <h5 class="modal-title" id="radiologyResultModalLabel">Radiologi</h5>
                    <small id="radiologyResultSubtitle">Memuat data permintaan...</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="laboratory-modal-loading" id="radiologyResultLoading">
                    <span class="spinner-border" role="status" aria-hidden="true"></span>
                    <strong>Menyiapkan hasil radiologi</strong>
                    <small>Mohon tunggu sebentar.</small>
                </div>

                <div class="laboratory-modal-error" id="radiologyResultError" hidden>
                    <span><i class="bi bi-exclamation-triangle"></i></span>
                    <strong>Hasil belum dapat dimuat</strong>
                    <p id="radiologyResultErrorMessage">Terjadi kendala saat mengambil data.</p>
                    <button type="button" id="radiologyResultRetry">
                        <i class="bi bi-arrow-clockwise"></i>
                        Coba Lagi
                    </button>
                </div>

                <div class="laboratory-modal-empty" id="radiologyResultEmpty" hidden>
                    <span><i class="bi bi-clipboard2-x"></i></span>
                    <strong>Hasil radiologi belum tersedia</strong>
                    <p>Permintaan ditemukan, tetapi narasi hasil dan gambar belum tercatat.</p>
                </div>

                <div class="laboratory-result-document" id="radiologyResultDocument" hidden>
                    <section class="laboratory-result-overview">
                        <div class="laboratory-result-order">
                            <span>No. Permintaan</span>
                            <strong id="radiologyResultOrder">-</strong>
                            <small id="radiologyResultTreatment">-</small>
                        </div>
                        <div class="laboratory-result-meta">
                            <div>
                                <small>Tanggal hasil</small>
                                <strong id="radiologyResultDate">-</strong>
                            </div>
                            <div>
                                <small>Dokter perujuk</small>
                                <strong id="radiologyResultDoctor">-</strong>
                            </div>
                            <div>
                                <small>Unit pelayanan</small>
                                <strong id="radiologyResultClinic">-</strong>
                            </div>
                            <div>
                                <small>Jenis layanan</small>
                                <strong id="radiologyResultCare">-</strong>
                            </div>
                        </div>
                    </section>

                    <section class="laboratory-result-clinical">
                        <div>
                            <span><i class="bi bi-activity"></i></span>
                            <p>
                                <small>Diagnosa klinis</small>
                                <strong id="radiologyResultDiagnosis">-</strong>
                            </p>
                        </div>
                        <div>
                            <span><i class="bi bi-info-circle"></i></span>
                            <p>
                                <small>Informasi tambahan</small>
                                <strong id="radiologyResultInformation">-</strong>
                            </p>
                        </div>
                    </section>

                    <section class="laboratory-result-summary">
                        <div>
                            <span><i class="bi bi-bounding-box-circles"></i></span>
                            <p><small>Pemeriksaan</small><strong id="radiologyExaminationCount">0</strong></p>
                        </div>
                        <div>
                            <span><i class="bi bi-file-earmark-text"></i></span>
                            <p><small>Narasi hasil</small><strong id="radiologyReportCount">0</strong></p>
                        </div>
                        <div>
                            <span><i class="bi bi-images"></i></span>
                            <p><small>Gambar</small><strong id="radiologyImageCount">0</strong></p>
                        </div>
                    </section>

                    <section class="radiology-result-section" id="radiologyExaminationSection">
                        <header>
                            <span><i class="bi bi-list-check"></i></span>
                            <div>
                                <h3>Pemeriksaan yang diminta</h3>
                                <small>Rincian tindakan dari permintaan radiologi</small>
                            </div>
                        </header>
                        <div class="radiology-examination-list" id="radiologyExaminations"></div>
                    </section>

                    <section class="radiology-result-section" id="radiologyReportSection">
                        <header>
                            <span><i class="bi bi-file-earmark-medical"></i></span>
                            <div>
                                <h3>Hasil pembacaan</h3>
                                <small>Narasi hasil pemeriksaan radiologi</small>
                            </div>
                        </header>
                        <div class="radiology-report-list" id="radiologyReports"></div>
                    </section>

                    <section class="radiology-result-section" id="radiologyImageSection">
                        <header>
                            <span><i class="bi bi-images"></i></span>
                            <div>
                                <h3>Gambar radiologi</h3>
                                <small>Klik gambar untuk melihat ukuran penuh</small>
                            </div>
                        </header>
                        <div class="radiology-image-gallery" id="radiologyImages"></div>
                    </section>

                    <div class="laboratory-result-note">
                        <i class="bi bi-shield-exclamation"></i>
                        <p>
                            <strong>Catatan untuk pasien</strong>
                            Gambar dan narasi radiologi bukan diagnosis mandiri. Konsultasikan hasil ini kepada dokter.
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
                    id="radiologyResultPrint" hidden>
                    <i class="bi bi-printer"></i>
                    Cetak Hasil
                </button>
            </div>
        </div>
    </div>

    <div class="radiology-image-viewer" id="radiologyImageViewer" hidden
        role="dialog" aria-label="Penampil gambar radiologi">
        <div class="radiology-viewer-toolbar">
            <div>
                <strong id="radiologyViewerTitle">Gambar radiologi</strong>
                <small id="radiologyViewerCounter">1 dari 1</small>
            </div>
            <button type="button" id="radiologyViewerClose" aria-label="Tutup gambar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="radiology-viewer-stage">
            <button type="button" class="radiology-viewer-navigation previous"
                id="radiologyViewerPrevious" aria-label="Gambar sebelumnya">
                <i class="bi bi-chevron-left"></i>
            </button>

            <figure>
                <img id="radiologyViewerImage" src="" alt="Gambar radiologi">
                <figcaption id="radiologyViewerCaption"></figcaption>
            </figure>

            <button type="button" class="radiology-viewer-navigation next"
                id="radiologyViewerNext" aria-label="Gambar berikutnya">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
</div>
