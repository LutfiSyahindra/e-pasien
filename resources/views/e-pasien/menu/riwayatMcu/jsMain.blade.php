<script>
    document.addEventListener("DOMContentLoaded", () => {
        const filterPanel = document.querySelector(".mcu-filter-panel");

        if (
            filterPanel
            && window.matchMedia("(max-width: 767.98px)").matches
            && filterPanel.dataset.hasActiveFilters !== "true"
        ) {
            filterPanel.removeAttribute("open");
        }

        const modal = document.getElementById("mcuDetailModal");

        if (!modal) {
            return;
        }

        const element = (id) => document.getElementById(id);
        const loading = element("mcuDetailLoading");
        const error = element("mcuDetailError");
        const errorMessage = element("mcuDetailErrorMessage");
        const retry = element("mcuDetailRetry");
        const documentPanel = element("mcuDetailDocument");
        const subtitle = element("mcuDetailSubtitle");
        const vitalGrid = element("mcuVitalGrid");
        const sections = element("mcuDetailSections");
        const toggleSections = element("mcuToggleSections");
        const printButton = element("mcuDetailPrint");
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

        function setLoading(trigger) {
            lastTrigger = trigger;
            loading.hidden = false;
            error.hidden = true;
            documentPanel.hidden = true;
            printButton.hidden = true;
            subtitle.textContent = "Memuat dokumen pemeriksaan...";
            vitalGrid.replaceChildren();
            sections.replaceChildren();
            resetToggle();
        }

        function setError(message) {
            loading.hidden = true;
            error.hidden = false;
            documentPanel.hidden = true;
            printButton.hidden = true;
            subtitle.textContent = "Dokumen belum dapat ditampilkan";
            errorMessage.textContent = valueOrDash(message);
        }

        function createVital(item) {
            const card = document.createElement("article");
            const label = document.createElement("small");
            const value = document.createElement("strong");
            const unit = document.createElement("span");

            label.textContent = valueOrDash(item.label);
            value.textContent = valueOrDash(item.value);
            unit.textContent = item.value === "-" ? "" : valueOrDash(item.unit);
            card.append(label, value, unit);

            return card;
        }

        function createDetailItem(item) {
            const row = document.createElement("div");
            const label = document.createElement("small");
            const value = document.createElement("p");

            row.className = item.wide ? "is-wide" : "";
            label.textContent = valueOrDash(item.label);
            value.textContent = valueOrDash(item.value);
            row.append(label, value);

            return row;
        }

        function createSection(section, index) {
            const panel = document.createElement("details");
            const summary = document.createElement("summary");
            const icon = document.createElement("span");
            const title = document.createElement("strong");
            const count = document.createElement("small");
            const chevron = document.createElement("i");
            const body = document.createElement("div");
            const items = Array.isArray(section.items) ? section.items : [];

            panel.className = "mcu-detail-section";
            panel.dataset.section = valueOrDash(section.key);
            panel.open = index === 0;
            icon.className = "mcu-section-icon";
            icon.innerHTML = `<i class="bi ${valueOrDash(section.icon)}"></i>`;
            title.textContent = valueOrDash(section.title);
            count.textContent = `${items.length} item`;
            chevron.className = "bi bi-chevron-down";
            summary.append(icon, title, count, chevron);

            body.className = "mcu-section-grid";
            body.replaceChildren(...items.map(createDetailItem));
            panel.append(summary, body);

            return panel;
        }

        function resetToggle() {
            toggleSections.dataset.action = "expand";
            toggleSections.querySelector("i").className = "bi bi-arrows-expand";
            toggleSections.querySelector("span").textContent = "Buka semua";
        }

        function updateToggle() {
            const panels = [...sections.querySelectorAll("details")];
            const allOpen = panels.length > 0 && panels.every((panel) => panel.open);

            toggleSections.dataset.action = allOpen ? "collapse" : "expand";
            toggleSections.querySelector("i").className = allOpen
                ? "bi bi-arrows-collapse"
                : "bi bi-arrows-expand";
            toggleSections.querySelector("span").textContent = allOpen
                ? "Tutup semua"
                : "Buka semua";
        }

        function renderDetail(detail) {
            const meta = detail.meta || {};
            const outcome = detail.hasil_akhir || {};
            const vitals = Array.isArray(detail.vitals) ? detail.vitals : [];
            const detailSections = Array.isArray(detail.sections) ? detail.sections : [];

            loading.hidden = true;
            error.hidden = true;
            documentPanel.hidden = false;
            printButton.hidden = false;
            subtitle.textContent =
                `${valueOrDash(meta.tanggal_lengkap)} · ${valueOrDash(meta.jam)} WIB`;

            setText("mcuTreatmentNumber", meta.no_rawat);
            setText("mcuMedicalRecordNumber", `No. RM ${valueOrDash(meta.no_rkm_medis)}`);
            setText("mcuPatientName", meta.pasien);
            setText(
                "mcuAssessedAt",
                `${valueOrDash(meta.tanggal_lengkap)} · ${valueOrDash(meta.jam)} WIB`
            );
            setText("mcuDoctorName", meta.dokter);
            setText("mcuClinicName", meta.poli);
            setText("mcuConclusion", outcome.kesimpulan);
            setText("mcuRecommendation", outcome.anjuran);
            vitalGrid.replaceChildren(...vitals.map(createVital));
            sections.replaceChildren(...detailSections.map(createSection));
            updateToggle();
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
                    throw new Error(payload.message || "Detail MCU belum dapat dimuat.");
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
                setError("Data pemeriksaan tidak valid.");
                return;
            }

            loadDetail(trigger);
        });

        retry.addEventListener("click", () => {
            if (lastTrigger) {
                loadDetail(lastTrigger);
            }
        });

        toggleSections.addEventListener("click", () => {
            const shouldOpen = toggleSections.dataset.action === "expand";

            sections.querySelectorAll("details")
                .forEach((panel) => {
                    panel.open = shouldOpen;
                });
            updateToggle();
        });

        sections.addEventListener("toggle", updateToggle, true);

        printButton.addEventListener("click", () => {
            document.body.classList.add("mcu-detail-printing");
            sections.querySelectorAll("details")
                .forEach((panel) => {
                    panel.open = true;
                });
            window.print();
        });

        window.addEventListener("afterprint", () => {
            document.body.classList.remove("mcu-detail-printing");
            updateToggle();
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
            vitalGrid.replaceChildren();
            sections.replaceChildren();
            resetToggle();
            document.body.classList.remove("mcu-detail-printing");
        });
    });
</script>
