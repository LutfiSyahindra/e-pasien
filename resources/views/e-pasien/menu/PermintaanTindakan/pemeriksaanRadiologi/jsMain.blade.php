<script>
    document.addEventListener("DOMContentLoaded", () => {
        const modal = document.getElementById("radiologyResultModal");

        if (!modal) {
            return;
        }

        const element = (id) => document.getElementById(id);
        const loading = element("radiologyResultLoading");
        const error = element("radiologyResultError");
        const errorMessage = element("radiologyResultErrorMessage");
        const retry = element("radiologyResultRetry");
        const empty = element("radiologyResultEmpty");
        const documentPanel = element("radiologyResultDocument");
        const printButton = element("radiologyResultPrint");
        const subtitle = element("radiologyResultSubtitle");
        const examinations = element("radiologyExaminations");
        const reports = element("radiologyReports");
        const images = element("radiologyImages");
        const examinationSection = element("radiologyExaminationSection");
        const reportSection = element("radiologyReportSection");
        const imageSection = element("radiologyImageSection");
        const imageViewer = element("radiologyImageViewer");
        const viewerClose = element("radiologyViewerClose");
        const viewerPrevious = element("radiologyViewerPrevious");
        const viewerNext = element("radiologyViewerNext");
        const viewerImage = element("radiologyViewerImage");
        const viewerTitle = element("radiologyViewerTitle");
        const viewerCounter = element("radiologyViewerCounter");
        const viewerCaption = element("radiologyViewerCaption");
        let activeRequest = null;
        let lastTrigger = null;
        let requestSequence = 0;
        let activeImages = [];
        let activeImageIndex = 0;
        let lastImageTrigger = null;

        function valueOrDash(value) {
            const text = String(value ?? "").trim();

            return text || "-";
        }

        function setText(id, value) {
            element(id).textContent = valueOrDash(value);
        }

        function resetCollections() {
            examinations.replaceChildren();
            reports.replaceChildren();
            images.replaceChildren();
        }

        function setLoading(trigger) {
            lastTrigger = trigger;
            loading.hidden = false;
            error.hidden = true;
            empty.hidden = true;
            documentPanel.hidden = true;
            printButton.hidden = true;
            subtitle.textContent = "Memuat data permintaan...";
            resetCollections();
        }

        function setError(message) {
            loading.hidden = true;
            error.hidden = false;
            empty.hidden = true;
            documentPanel.hidden = true;
            printButton.hidden = true;
            subtitle.textContent = "Data belum dapat ditampilkan";
            errorMessage.textContent = valueOrDash(message);
        }

        function createExamination(item) {
            const card = document.createElement("article");
            const icon = document.createElement("span");
            const content = document.createElement("div");
            const name = document.createElement("strong");
            const meta = document.createElement("small");

            icon.innerHTML = '<i class="bi bi-bounding-box-circles"></i>';
            name.textContent = valueOrDash(item.nama);
            meta.textContent = `Kode ${valueOrDash(item.kode)} · Pembayaran ${valueOrDash(item.status_bayar)}`;
            content.append(name, meta);
            card.append(icon, content);

            return card;
        }

        function createReport(item, index) {
            const card = document.createElement("article");
            const heading = document.createElement("header");
            const title = document.createElement("strong");
            const time = document.createElement("small");
            const narrative = document.createElement("p");

            title.textContent = `Hasil pembacaan ${index + 1}`;
            time.textContent = `${valueOrDash(item.tanggal_lengkap)} · ${valueOrDash(item.jam)} WIB`;
            narrative.textContent = valueOrDash(item.narasi);
            heading.append(title, time);
            card.append(heading, narrative);

            return card;
        }

        function createImage(item, index) {
            const button = document.createElement("button");
            const image = document.createElement("img");
            const caption = document.createElement("span");
            const name = document.createElement("strong");
            const meta = document.createElement("small");

            button.type = "button";
            button.className = "radiology-image-card";
            button.setAttribute("aria-label", `Buka gambar radiologi ${index + 1}`);
            image.src = item.url;
            image.alt = `Gambar radiologi ${index + 1}`;
            image.loading = "lazy";
            name.textContent = valueOrDash(item.nama_file);
            meta.textContent = `${valueOrDash(item.tanggal_lengkap)} · ${valueOrDash(item.jam)} WIB`;
            caption.append(name, meta);
            button.append(image, caption);
            button.addEventListener("click", () => {
                lastImageTrigger = button;
                openImageViewer(index);
            });

            return button;
        }

        function showViewerImage(index) {
            if (activeImages.length === 0) {
                return;
            }

            activeImageIndex = (index + activeImages.length) % activeImages.length;
            const item = activeImages[activeImageIndex];
            const hasMultipleImages = activeImages.length > 1;

            viewerImage.src = item.url;
            viewerImage.alt = `Gambar radiologi ${activeImageIndex + 1}`;
            viewerTitle.textContent = valueOrDash(item.nama_file);
            viewerCounter.textContent = `${activeImageIndex + 1} dari ${activeImages.length}`;
            viewerCaption.textContent = `${valueOrDash(item.tanggal_lengkap)} · ${valueOrDash(item.jam)} WIB`;
            viewerPrevious.hidden = !hasMultipleImages;
            viewerNext.hidden = !hasMultipleImages;
        }

        function openImageViewer(index) {
            showViewerImage(index);
            imageViewer.hidden = false;
            document.body.classList.add("radiology-viewer-open");
            viewerClose.focus();
        }

        function closeImageViewer(restoreFocus = true) {
            if (imageViewer.hidden) {
                return;
            }

            imageViewer.hidden = true;
            viewerImage.removeAttribute("src");
            document.body.classList.remove("radiology-viewer-open");

            if (restoreFocus) {
                lastImageTrigger?.focus();
            }
        }

        function renderResult(payload) {
            const request = payload.permintaan || {};
            const summary = payload.ringkasan || {};
            const examinationItems = Array.isArray(payload.pemeriksaan)
                ? payload.pemeriksaan
                : [];
            const reportItems = Array.isArray(payload.hasil) ? payload.hasil : [];
            const imageItems = Array.isArray(payload.gambar) ? payload.gambar : [];

            loading.hidden = true;
            error.hidden = true;
            activeImages = imageItems;

            if (reportItems.length === 0 && imageItems.length === 0) {
                empty.hidden = false;
                documentPanel.hidden = true;
                printButton.hidden = true;
                subtitle.textContent = valueOrDash(request.noorder);
                return;
            }

            empty.hidden = true;
            documentPanel.hidden = false;
            printButton.hidden = false;
            subtitle.textContent = `${valueOrDash(request.noorder)} · ${valueOrDash(request.poli)}`;
            setText("radiologyResultOrder", request.noorder);
            setText("radiologyResultTreatment", `No. Rawat ${valueOrDash(request.no_rawat)}`);
            setText(
                "radiologyResultDate",
                `${valueOrDash(request.tanggal_hasil_lengkap)} · ${valueOrDash(request.jam_hasil)} WIB`
            );
            setText("radiologyResultDoctor", request.dokter_perujuk);
            setText("radiologyResultClinic", request.poli);
            setText("radiologyResultCare", request.jenis_layanan);
            setText("radiologyResultDiagnosis", request.diagnosa_klinis);
            setText("radiologyResultInformation", request.informasi_tambahan);
            setText("radiologyExaminationCount", Number(summary.jumlah_pemeriksaan) || 0);
            setText("radiologyReportCount", Number(summary.jumlah_hasil) || 0);
            setText("radiologyImageCount", Number(summary.jumlah_gambar) || 0);

            examinationSection.hidden = examinationItems.length === 0;
            reportSection.hidden = reportItems.length === 0;
            imageSection.hidden = imageItems.length === 0;
            examinations.replaceChildren(...examinationItems.map(createExamination));
            reports.replaceChildren(...reportItems.map(createReport));
            images.replaceChildren(...imageItems.map(createImage));
        }

        async function loadResult(trigger) {
            activeRequest?.abort();
            activeRequest = new AbortController();
            const currentRequest = ++requestSequence;

            setLoading(trigger);
            trigger.disabled = true;
            trigger.setAttribute("aria-busy", "true");

            try {
                const response = await fetch(trigger.dataset.resultUrl, {
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
                    throw new Error(payload.message || "Hasil radiologi belum dapat dimuat.");
                }

                renderResult(payload.data || {});
            } catch (requestError) {
                if (requestError.name !== "AbortError" && currentRequest === requestSequence) {
                    setError(requestError.message);
                }
            } finally {
                trigger.disabled = false;
                trigger.removeAttribute("aria-busy");
            }
        }

        modal.addEventListener("show.bs.modal", (event) => {
            const trigger = event.relatedTarget;

            if (!(trigger instanceof HTMLElement) || !trigger.dataset.resultUrl) {
                setError("Data permintaan radiologi tidak valid.");
                return;
            }

            loadResult(trigger);
        });

        retry.addEventListener("click", () => {
            if (lastTrigger) {
                loadResult(lastTrigger);
            }
        });

        printButton.addEventListener("click", () => {
            const pdfUrl = lastTrigger?.dataset.resultPdfUrl;

            if (pdfUrl) {
                window.open(pdfUrl, "_blank", "noopener,noreferrer");
            }
        });

        viewerClose.addEventListener("click", () => closeImageViewer());
        viewerPrevious.addEventListener("click", () => {
            showViewerImage(activeImageIndex - 1);
        });
        viewerNext.addEventListener("click", () => {
            showViewerImage(activeImageIndex + 1);
        });
        imageViewer.addEventListener("click", (event) => {
            if (event.target === imageViewer) {
                closeImageViewer();
            }
        });

        document.addEventListener("keydown", (event) => {
            if (imageViewer.hidden) {
                return;
            }

            if (event.key === "Escape") {
                closeImageViewer();
            } else if (event.key === "ArrowLeft") {
                showViewerImage(activeImageIndex - 1);
            } else if (event.key === "ArrowRight") {
                showViewerImage(activeImageIndex + 1);
            }
        });

        modal.addEventListener("hidden.bs.modal", () => {
            closeImageViewer(false);
            requestSequence++;
            activeRequest?.abort();
            activeRequest = null;
            lastTrigger = null;
            lastImageTrigger = null;
            activeImages = [];
            activeImageIndex = 0;
            loading.hidden = false;
            error.hidden = true;
            empty.hidden = true;
            documentPanel.hidden = true;
            printButton.hidden = true;
            resetCollections();
        });
    });
</script>
