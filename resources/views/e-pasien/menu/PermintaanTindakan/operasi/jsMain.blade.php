<script>
    document.addEventListener("DOMContentLoaded", () => {
        const modal = document.getElementById("operationDetailModal");

        if (!modal) {
            return;
        }

        const element = (id) => document.getElementById(id);
        const loading = element("operationDetailLoading");
        const error = element("operationDetailError");
        const errorMessage = element("operationDetailErrorMessage");
        const retry = element("operationDetailRetry");
        const documentPanel = element("operationDetailDocument");
        const printButton = element("operationDetailPrint");
        const subtitle = element("operationDetailSubtitle");
        const bookedProcedures = element("operationBookedProcedures");
        const performedProcedures = element("operationPerformedProcedures");
        const performedEmpty = element("operationPerformedEmpty");
        const performedContent = element("operationPerformedContent");
        const reportEmpty = element("operationReportEmpty");
        const reportContent = element("operationReportContent");
        let activeRequest = null;
        let lastTrigger = null;
        let requestSequence = 0;

        function valueOrDash(value) {
            const text = String(value ?? "").trim();

            return text || "-";
        }

        function setText(id, value) {
            element(id).textContent = valueOrDash(value);
        }

        function resetCollections() {
            bookedProcedures.replaceChildren();
            performedProcedures.replaceChildren();
        }

        function setLoading(trigger) {
            lastTrigger = trigger;
            loading.hidden = false;
            error.hidden = true;
            documentPanel.hidden = true;
            printButton.hidden = true;
            subtitle.textContent = "Memuat data operasi...";
            resetCollections();
        }

        function setError(message) {
            loading.hidden = true;
            error.hidden = false;
            documentPanel.hidden = true;
            printButton.hidden = true;
            subtitle.textContent = "Data belum dapat ditampilkan";
            errorMessage.textContent = valueOrDash(message);
        }

        function createProcedure(item) {
            const card = document.createElement("article");
            const icon = document.createElement("span");
            const content = document.createElement("div");
            const name = document.createElement("strong");
            const meta = document.createElement("small");

            icon.innerHTML = '<i class="bi bi-bandaid"></i>';
            name.textContent = valueOrDash(item.nama);
            meta.textContent = `Kode ${valueOrDash(item.kode)} · Kategori ${valueOrDash(item.kategori)}`;
            content.append(name, meta);
            card.append(icon, content);

            return card;
        }

        function renderDetail(payload) {
            const booking = payload.booking || {};
            const performed = payload.pelaksanaan || {};
            const report = payload.laporan || {};
            const bookedItems = Array.isArray(booking.tindakan)
                ? booking.tindakan
                : [];
            const performedItems = Array.isArray(performed.tindakan)
                ? performed.tindakan
                : [];

            loading.hidden = true;
            error.hidden = true;
            documentPanel.hidden = false;
            printButton.hidden = false;
            subtitle.textContent = `${valueOrDash(booking.judul)} · ${valueOrDash(booking.no_rawat)}`;

            setText("operationTreatmentNumber", booking.no_rawat);
            setText("operationDetailStatus", booking.status_label);
            setText(
                "operationSchedule",
                `${valueOrDash(booking.tanggal_booking_lengkap)} · ${valueOrDash(booking.jam_mulai)}–${valueOrDash(booking.jam_selesai)} WIB`
            );
            setText("operationDoctor", booking.dokter_operator);
            setText("operationRoom", booking.ruang_operasi);
            setText("operationCareType", booking.jenis_layanan);
            setText("operationBookedCount", Number(booking.jumlah_tindakan) || 0);
            setText("operationPerformedState", performed.tersedia ? "Tercatat" : "Belum");
            setText("operationReportState", report.tersedia ? "Tersedia" : "Belum");
            setText("operationClinic", booking.poli);
            setText("operationCategory", booking.kategori);
            setText("operationScheduledDuration", booking.durasi_jadwal);
            setText("operationBookingStatus", booking.status_booking);
            bookedProcedures.replaceChildren(...bookedItems.map(createProcedure));

            performedEmpty.hidden = Boolean(performed.tersedia);
            performedContent.hidden = !performed.tersedia;
            if (performed.tersedia) {
                setText(
                    "operationPerformedAt",
                    `${valueOrDash(performed.tanggal_lengkap)} · ${valueOrDash(performed.jam)} WIB`
                );
                setText("operationMainOperator", performed.operator_utama);
                setText("operationAnesthetist", performed.dokter_anestesi);
                setText("operationAnesthesia", performed.jenis_anestesi);
                performedProcedures.replaceChildren(
                    ...performedItems.map(createProcedure)
                );
            } else {
                performedProcedures.replaceChildren();
            }

            reportEmpty.hidden = Boolean(report.tersedia);
            reportContent.hidden = !report.tersedia;
            if (report.tersedia) {
                setText("operationReportStarted", report.tanggal_mulai_lengkap);
                setText("operationReportFinished", report.tanggal_selesai_lengkap);
                setText("operationActualDuration", report.durasi);
                setText("operationPathologyRequest", report.permintaan_pa);
                setText("operationPreDiagnosis", report.diagnosa_preoperasi);
                setText("operationPostDiagnosis", report.diagnosa_postoperasi);
                setText("operationTissue", report.jaringan_dieksekusi);
                setText("operationReportNarrative", report.narasi);
            }
        }

        async function loadDetail(trigger) {
            activeRequest?.abort();
            activeRequest = new AbortController();
            const currentRequest = ++requestSequence;

            setLoading(trigger);
            trigger.disabled = true;
            trigger.setAttribute("aria-busy", "true");

            try {
                const response = await fetch(trigger.dataset.detailUrl, {
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    signal: activeRequest.signal,
                });
                const payload = await response.json().catch(() => ({}));

                if (currentRequest !== requestSequence) {
                    return;
                }

                if (!response.ok) {
                    throw new Error(payload.message || "Detail operasi belum dapat dimuat.");
                }

                renderDetail(payload.data || {});
            } catch (requestError) {
                if (
                    requestError.name !== "AbortError"
                    && currentRequest === requestSequence
                ) {
                    setError(requestError.message);
                }
            } finally {
                trigger.disabled = false;
                trigger.removeAttribute("aria-busy");
            }
        }

        modal.addEventListener("show.bs.modal", (event) => {
            const trigger = event.relatedTarget;

            if (!(trigger instanceof HTMLElement) || !trigger.dataset.detailUrl) {
                setError("Data operasi tidak valid.");
                return;
            }

            loadDetail(trigger);
        });

        retry.addEventListener("click", () => {
            if (lastTrigger) {
                loadDetail(lastTrigger);
            }
        });

        printButton.addEventListener("click", () => {
            document.body.classList.add("operation-detail-printing");
            window.print();
        });

        window.addEventListener("afterprint", () => {
            document.body.classList.remove("operation-detail-printing");
        });

        modal.addEventListener("hidden.bs.modal", () => {
            requestSequence++;
            activeRequest?.abort();
            activeRequest = null;
            lastTrigger = null;
            loading.hidden = false;
            error.hidden = true;
            documentPanel.hidden = true;
            printButton.hidden = true;
            performedEmpty.hidden = false;
            performedContent.hidden = true;
            reportEmpty.hidden = false;
            reportContent.hidden = true;
            resetCollections();
            document.body.classList.remove("operation-detail-printing");
        });
    });
</script>
