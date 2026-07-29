<div class="modal fade examination-resume-modal" id="examinationResumeModal" tabindex="-1"
    aria-labelledby="examinationResumeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <span class="examination-resume-modal-icon">
                    <i class="bi bi-file-earmark-medical"></i>
                </span>
                <div class="examination-resume-title">
                    <span class="examination-section-kicker">Ringkasan medis</span>
                    <h5 class="modal-title" id="examinationResumeModalLabel">Resume Pemeriksaan</h5>
                    <p>Informasi penting dari kunjungan dan perawatan Anda.</p>
                </div>
                <div class="examination-resume-header-actions">
                    <span class="examination-resume-security">
                        <i class="bi bi-shield-lock"></i>
                        Data terlindungi
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="examination-resume-overview" id="examinationResumeOverview" hidden>
                    <article>
                        <span class="examination-resume-overview-icon service">
                            <i class="bi bi-hospital"></i>
                        </span>
                        <span>
                            <small>Jenis layanan</small>
                            <strong id="examinationResumeService">-</strong>
                        </span>
                    </article>
                    <article>
                        <span class="examination-resume-overview-icon reference">
                            <i class="bi bi-upc-scan"></i>
                        </span>
                        <span>
                            <small>No. Rawat</small>
                            <strong id="examinationResumeNoRawat">-</strong>
                        </span>
                    </article>
                    <article>
                        <span class="examination-resume-overview-icon doctor">
                            <i class="bi bi-person-badge"></i>
                        </span>
                        <span>
                            <small>Dokter penanggung jawab</small>
                            <strong id="examinationResumeDoctor">-</strong>
                        </span>
                    </article>
                </div>

                <div class="examination-resume-state examination-resume-loading" id="examinationResumeLoading"
                    role="status" aria-live="polite">
                    <span class="examination-resume-loading-icon">
                        <i class="bi bi-file-earmark-medical"></i>
                        <span class="spinner-border" aria-hidden="true"></span>
                    </span>
                    <strong>Menyiapkan resume pemeriksaan</strong>
                    <small>Kami sedang menyusun rincian medis Anda.</small>
                    <div class="examination-resume-skeleton" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                <div class="examination-resume-state error" id="examinationResumeError" role="alert" hidden>
                    <span><i class="bi bi-exclamation-triangle"></i></span>
                    <strong>Resume belum dapat ditampilkan</strong>
                    <small id="examinationResumeErrorMessage">Silakan coba kembali.</small>
                    <button type="button" class="examination-resume-retry" id="examinationResumeRetry">
                        <i class="bi bi-arrow-clockwise"></i>
                        Coba lagi
                    </button>
                </div>

                <div class="examination-resume-state" id="examinationResumeEmpty" hidden>
                    <span><i class="bi bi-file-earmark-x"></i></span>
                    <strong>Rincian resume belum tersedia</strong>
                    <small>Belum ada rincian medis yang dapat ditampilkan.</small>
                </div>

                <div class="examination-resume-workspace" id="examinationResumeWorkspace" hidden>
                    <div class="examination-resume-toolbar">
                        <label class="examination-resume-search" for="examinationResumeSearch">
                            <i class="bi bi-search"></i>
                            <input type="search" id="examinationResumeSearch"
                                placeholder="Cari diagnosis, obat, tindakan..."
                                autocomplete="off" aria-label="Cari dalam resume" />
                            <button type="button" id="examinationResumeSearchClear"
                                aria-label="Hapus pencarian" hidden>
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </label>
                        <button type="button" class="examination-resume-toggle" id="examinationResumeToggle"
                            aria-label="Tutup semua bagian">
                            <i class="bi bi-arrows-collapse"></i>
                            <span>Tutup semua</span>
                        </button>
                    </div>

                    <nav class="examination-resume-navigation" aria-label="Daftar bagian resume">
                        <span><i class="bi bi-signpost-split"></i> Lompat ke</span>
                        <div id="examinationResumeNavigation"></div>
                    </nav>

                    <div class="examination-resume-search-empty" id="examinationResumeSearchEmpty"
                        role="status" aria-live="polite" hidden>
                        <span><i class="bi bi-search"></i></span>
                        <strong>Tidak ada informasi yang cocok</strong>
                        <small>Coba gunakan kata kunci lain atau hapus pencarian.</small>
                        <button type="button" id="examinationResumeResetSearch">Hapus pencarian</button>
                    </div>

                    <p class="visually-hidden" id="examinationResumeSearchStatus" aria-live="polite"></p>
                </div>

                <div class="examination-resume-sections" id="examinationResumeSections" hidden></div>
            </div>
            <div class="modal-footer">
                <span class="examination-resume-confidential">
                    <i class="bi bi-lock"></i>
                    Jaga kerahasiaan informasi medis Anda
                </span>
                <button type="button" class="examination-resume-close" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
