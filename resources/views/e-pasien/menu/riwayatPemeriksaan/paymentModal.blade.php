<div class="modal fade examination-payment-modal" id="examinationPaymentModal" tabindex="-1"
    aria-labelledby="examinationPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="examination-payment-brand">
                    <span class="examination-payment-brand-icon">
                        <i class="bi bi-receipt-cutoff"></i>
                    </span>
                    <span>
                        <small>Patient financial statement</small>
                        <strong>E-Pasien</strong>
                    </span>
                </div>
                <div class="examination-payment-heading">
                    <span><i class="bi bi-patch-check-fill"></i> Nota resmi</span>
                    <h5 class="modal-title" id="examinationPaymentModalLabel">Nota Pembayaran</h5>
                    <p>Rincian transaksi layanan kesehatan Anda.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="examination-payment-state loading" id="examinationPaymentLoading"
                    role="status" aria-live="polite">
                    <span class="examination-payment-state-icon">
                        <i class="bi bi-receipt"></i>
                        <span class="spinner-border" aria-hidden="true"></span>
                    </span>
                    <strong>Menyiapkan nota pembayaran</strong>
                    <small>Seluruh rincian billing sedang disusun sesuai urutan transaksi.</small>
                    <div class="examination-payment-skeleton" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                <div class="examination-payment-state error" id="examinationPaymentError" role="alert" hidden>
                    <span class="examination-payment-state-icon"><i class="bi bi-exclamation-triangle"></i></span>
                    <strong>Nota belum dapat ditampilkan</strong>
                    <small id="examinationPaymentErrorMessage">Silakan coba kembali.</small>
                    <button type="button" id="examinationPaymentRetry">
                        <i class="bi bi-arrow-clockwise"></i>
                        Coba lagi
                    </button>
                </div>

                <div class="examination-payment-state" id="examinationPaymentEmpty" hidden>
                    <span class="examination-payment-state-icon"><i class="bi bi-receipt"></i></span>
                    <strong>Rincian billing belum tersedia</strong>
                    <small>Belum ada baris transaksi untuk nomor rawat ini.</small>
                </div>

                <article class="examination-payment-receipt" id="examinationPaymentReceipt" hidden>
                    <header class="examination-payment-receipt-header">
                        <div>
                            <span class="examination-payment-receipt-kicker">Nota pembayaran pasien</span>
                            <h2 id="examinationPaymentReceiptNumber">-</h2>
                            <p>
                                <i class="bi bi-calendar3"></i>
                                <span id="examinationPaymentDate">-</span>
                            </p>
                        </div>
                        <span class="examination-payment-status" id="examinationPaymentStatus">
                            <i class="bi bi-check-circle"></i>
                            <span>-</span>
                        </span>
                    </header>

                    <section class="examination-payment-overview" aria-label="Ringkasan kunjungan">
                        <article>
                            <span><i class="bi bi-person"></i></span>
                            <div>
                                <small>Pasien</small>
                                <strong id="examinationPaymentPatient">-</strong>
                                <p id="examinationPaymentMedicalRecord">No. RM -</p>
                            </div>
                        </article>
                        <article>
                            <span><i class="bi bi-hospital"></i></span>
                            <div>
                                <small>Layanan</small>
                                <strong id="examinationPaymentService">-</strong>
                                <p id="examinationPaymentClinic">-</p>
                            </div>
                        </article>
                        <article>
                            <span><i class="bi bi-upc-scan"></i></span>
                            <div>
                                <small>No. Rawat</small>
                                <strong id="examinationPaymentNoRawat">-</strong>
                                <p id="examinationPaymentDoctor">-</p>
                            </div>
                        </article>
                        <article>
                            <span><i class="bi bi-shield-check"></i></span>
                            <div>
                                <small>Penjamin</small>
                                <strong id="examinationPaymentGuarantor">-</strong>
                                <p>Informasi kunjungan terverifikasi</p>
                            </div>
                        </article>
                    </section>

                    <section class="examination-payment-information" id="examinationPaymentInformationSection">
                        <div class="examination-payment-section-title">
                            <span><i class="bi bi-info-circle"></i></span>
                            <div>
                                <small>Informasi transaksi</small>
                                <h3>Detail pada nota</h3>
                            </div>
                        </div>
                        <div class="examination-payment-information-grid" id="examinationPaymentInformation"></div>
                    </section>

                    <section class="examination-payment-details">
                        <div class="examination-payment-section-title">
                            <span><i class="bi bi-list-check"></i></span>
                            <div>
                                <small>Billing statement</small>
                                <h3>Rincian biaya</h3>
                            </div>
                            <em id="examinationPaymentRowCount">0 baris</em>
                        </div>

                        <div class="examination-payment-table-wrap">
                            <table class="examination-payment-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Uraian</th>
                                        <th scope="col">Kategori</th>
                                        <th scope="col">Perhitungan</th>
                                        <th scope="col" class="numeric">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="examinationPaymentRows"></tbody>
                            </table>
                        </div>
                    </section>

                    <section class="examination-payment-summary">
                        <dl>
                            <div>
                                <dt>
                                    <span>Subtotal layanan</span>
                                    <small>Akumulasi seluruh tindakan, obat, dan layanan</small>
                                </dt>
                                <dd id="examinationPaymentSubtotal">Rp0</dd>
                            </div>
                            <div>
                                <dt>
                                    <span>Tambahan biaya</span>
                                    <small>Biaya tambahan yang tercatat pada billing</small>
                                </dt>
                                <dd id="examinationPaymentAdditional">Rp0</dd>
                            </div>
                            <div class="deduction">
                                <dt>
                                    <span>Potongan / retur</span>
                                    <small>Nilai pengurang dari tagihan</small>
                                </dt>
                                <dd id="examinationPaymentDeduction">Rp0</dd>
                            </div>
                            <div class="grand-total">
                                <dt>Total tagihan</dt>
                                <dd id="examinationPaymentTotal">Rp0</dd>
                            </div>
                        </dl>
                    </section>

                    <footer class="examination-payment-receipt-footer">
                        <span><i class="bi bi-stars"></i> Terima kasih telah mempercayakan layanan kesehatan Anda.</span>
                        <small>Dicetak melalui E-Pasien</small>
                    </footer>
                </article>
            </div>

            <div class="modal-footer">
                <span>
                    <i class="bi bi-lock"></i>
                    Nota hanya dapat diakses oleh pasien terkait
                </span>
                <div>
                    <button type="button" class="examination-payment-print" id="examinationPaymentPrint" hidden>
                        <i class="bi bi-printer"></i>
                        Cetak Nota
                    </button>
                    <button type="button" class="examination-payment-close" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
