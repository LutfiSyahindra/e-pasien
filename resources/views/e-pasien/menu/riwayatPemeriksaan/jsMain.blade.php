<script>
    document.addEventListener("DOMContentLoaded", () => {
        const historyPage = document.querySelector(".examination-history-page");
        const mobileFilterToggle = document.getElementById("examinationMobileFilterToggle");
        const filterPanel = document.getElementById("examinationFilterPanel");

        if (historyPage && mobileFilterToggle && filterPanel) {
            const mobileFilterMedia = window.matchMedia("(max-width: 767.98px)");
            let mobileFilterExpanded = Boolean(
                filterPanel.querySelector(".examination-filter-error")
            );

            const syncMobileFilter = () => {
                const isMobile = mobileFilterMedia.matches;
                const isExpanded = !isMobile || mobileFilterExpanded;
                const chevron = mobileFilterToggle.querySelector(
                    ".examination-mobile-filter-chevron"
                );

                filterPanel.hidden = !isExpanded;
                mobileFilterToggle.setAttribute("aria-expanded", String(isExpanded));

                if (chevron) {
                    chevron.className = isExpanded
                        ? "bi bi-chevron-up examination-mobile-filter-chevron"
                        : "bi bi-chevron-down examination-mobile-filter-chevron";
                }
            };

            mobileFilterToggle.addEventListener("click", () => {
                mobileFilterExpanded = !mobileFilterExpanded;
                syncMobileFilter();
            });

            if (typeof mobileFilterMedia.addEventListener === "function") {
                mobileFilterMedia.addEventListener("change", syncMobileFilter);
            } else {
                mobileFilterMedia.addListener(syncMobileFilter);
            }

            historyPage.classList.add("is-filter-enhanced");
            syncMobileFilter();
        }

        document.querySelectorAll(".examination-filters a").forEach((filter) => {
            filter.addEventListener("click", () => {
                if (!filter.classList.contains("active")) {
                    filter.classList.add("is-loading");
                    filter.setAttribute("aria-busy", "true");
                }
            });
        });

        const modalElement = document.getElementById("examinationResumeModal");

        if (!modalElement) {
            return;
        }

        const overview = document.getElementById("examinationResumeOverview");
        const service = document.getElementById("examinationResumeService");
        const noRawat = document.getElementById("examinationResumeNoRawat");
        const doctor = document.getElementById("examinationResumeDoctor");
        const loading = document.getElementById("examinationResumeLoading");
        const error = document.getElementById("examinationResumeError");
        const errorMessage = document.getElementById("examinationResumeErrorMessage");
        const retry = document.getElementById("examinationResumeRetry");
        const empty = document.getElementById("examinationResumeEmpty");
        const workspace = document.getElementById("examinationResumeWorkspace");
        const search = document.getElementById("examinationResumeSearch");
        const searchClear = document.getElementById("examinationResumeSearchClear");
        const searchEmpty = document.getElementById("examinationResumeSearchEmpty");
        const resetSearch = document.getElementById("examinationResumeResetSearch");
        const searchStatus = document.getElementById("examinationResumeSearchStatus");
        const toggle = document.getElementById("examinationResumeToggle");
        const navigation = document.getElementById("examinationResumeNavigation");
        const sections = document.getElementById("examinationResumeSections");
        let activeRequest = null;
        let lastTrigger = null;
        let requestSequence = 0;

        function valueOrDash(value) {
            const normalizedValue = String(value ?? "").trim();

            return normalizedValue || "-";
        }

        function normalizeSearchValue(value) {
            return String(value ?? "")
                .toLocaleLowerCase("id-ID")
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");
        }

        function setSectionExpanded(sectionElement, expanded) {
            const button = sectionElement.querySelector(".examination-resume-section-heading");

            sectionElement.classList.toggle("is-collapsed", !expanded);
            button?.setAttribute("aria-expanded", String(expanded));
        }

        function updateToggleState() {
            const visibleSections = [...sections.querySelectorAll(".examination-resume-section:not([hidden])")];
            const allExpanded = visibleSections.length > 0 &&
                visibleSections.every((section) => !section.classList.contains("is-collapsed"));
            const icon = toggle.querySelector("i");
            const label = toggle.querySelector("span");

            toggle.dataset.action = allExpanded ? "collapse" : "expand";
            toggle.setAttribute("aria-label", allExpanded ? "Tutup semua bagian" : "Buka semua bagian");
            icon.className = allExpanded ? "bi bi-arrows-collapse" : "bi bi-arrows-expand";
            label.textContent = allExpanded ? "Tutup semua" : "Buka semua";
        }

        function resetSearchState() {
            search.value = "";
            searchClear.hidden = true;
            searchEmpty.hidden = true;
            searchStatus.textContent = "";
        }

        function showLoading(trigger) {
            lastTrigger = trigger;
            overview.hidden = false;
            service.textContent = valueOrDash(trigger.dataset.serviceType);
            noRawat.textContent = valueOrDash(trigger.dataset.noRawat);
            doctor.textContent = "-";
            loading.hidden = false;
            error.hidden = true;
            empty.hidden = true;
            workspace.hidden = true;
            sections.hidden = true;
            navigation.replaceChildren();
            sections.replaceChildren();
            resetSearchState();
        }

        function showError(message) {
            loading.hidden = true;
            error.hidden = false;
            empty.hidden = true;
            workspace.hidden = true;
            sections.hidden = true;
            errorMessage.textContent = valueOrDash(message);
        }

        function createResumeItem(item) {
            const itemElement = document.createElement("div");
            const heading = document.createElement("div");
            const label = document.createElement("strong");
            const value = document.createElement("p");

            itemElement.className = "examination-resume-item";
            itemElement.dataset.search = normalizeSearchValue([
                item.label,
                item.code,
                item.value,
            ].join(" "));
            heading.className = "examination-resume-item-heading";
            label.textContent = valueOrDash(item.label);
            heading.append(label);

            if (String(item.code ?? "").trim()) {
                const code = document.createElement("span");
                code.textContent = item.code;
                heading.append(code);
            }

            value.textContent = valueOrDash(item.value);
            itemElement.append(heading, value);

            return itemElement;
        }

        function createResumeSection(section, index) {
            const sectionElement = document.createElement("section");
            const heading = document.createElement("button");
            const icon = document.createElement("span");
            const iconGlyph = document.createElement("i");
            const titleGroup = document.createElement("span");
            const kicker = document.createElement("small");
            const title = document.createElement("strong");
            const itemCount = document.createElement("span");
            const chevron = document.createElement("i");
            const content = document.createElement("div");
            const itemGrid = document.createElement("div");
            const sectionId = `examinationResumeSection-${index + 1}`;
            const contentId = `${sectionId}-content`;
            const sectionItems = Array.isArray(section.items) ? section.items : [];

            sectionElement.className = "examination-resume-section";
            sectionElement.id = sectionId;
            sectionElement.dataset.title = normalizeSearchValue(section.title);
            sectionElement.setAttribute("aria-labelledby", `${sectionId}-title`);
            heading.type = "button";
            heading.className = "examination-resume-section-heading";
            heading.setAttribute("aria-expanded", "true");
            heading.setAttribute("aria-controls", contentId);
            icon.className = "examination-resume-section-icon";
            iconGlyph.className = `bi ${section.icon || "bi-file-medical"}`;
            kicker.textContent = `Bagian ${String(index + 1).padStart(2, "0")}`;
            title.id = `${sectionId}-title`;
            title.textContent = valueOrDash(section.title);
            itemCount.className = "examination-resume-section-count";
            itemCount.textContent = `${sectionItems.length} informasi`;
            chevron.className = "bi bi-chevron-down examination-resume-section-chevron";
            content.className = "examination-resume-section-content";
            content.id = contentId;
            itemGrid.className = "examination-resume-item-grid";

            icon.append(iconGlyph);
            titleGroup.className = "examination-resume-section-title";
            titleGroup.append(kicker, title);
            heading.append(icon, titleGroup, itemCount, chevron);
            sectionItems.forEach((item) => itemGrid.append(createResumeItem(item)));
            content.append(itemGrid);
            sectionElement.append(heading, content);

            heading.addEventListener("click", () => {
                setSectionExpanded(sectionElement, sectionElement.classList.contains("is-collapsed"));
                updateToggleState();
            });

            return sectionElement;
        }

        function createNavigationItem(section, index) {
            const item = document.createElement("button");
            const icon = document.createElement("i");
            const sectionId = `examinationResumeSection-${index + 1}`;

            item.type = "button";
            item.dataset.sectionTarget = sectionId;
            icon.className = `bi ${section.icon || "bi-file-medical"}`;
            item.append(icon, document.createTextNode(valueOrDash(section.title)));
            item.addEventListener("click", () => {
                const sectionElement = document.getElementById(sectionId);

                if (!sectionElement || sectionElement.hidden) {
                    return;
                }

                setSectionExpanded(sectionElement, true);
                updateToggleState();
                sectionElement.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
                sectionElement.classList.add("is-highlighted");
                window.setTimeout(() => sectionElement.classList.remove("is-highlighted"), 900);
            });

            return item;
        }

        function filterResume() {
            const query = normalizeSearchValue(search.value.trim());
            const sectionElements = [...sections.querySelectorAll(".examination-resume-section")];
            let visibleItemCount = 0;
            let visibleSectionCount = 0;

            searchClear.hidden = query === "";

            sectionElements.forEach((sectionElement) => {
                const items = [...sectionElement.querySelectorAll(".examination-resume-item")];
                const titleMatches = query !== "" && sectionElement.dataset.title.includes(query);
                let sectionItemCount = 0;

                items.forEach((item) => {
                    const matches = query === "" || titleMatches || item.dataset.search
                        .includes(query);

                    item.hidden = !matches;
                    sectionItemCount += matches ? 1 : 0;
                });

                sectionElement.hidden = sectionItemCount === 0;
                visibleItemCount += sectionItemCount;
                visibleSectionCount += sectionItemCount > 0 ? 1 : 0;

                if (query !== "" && sectionItemCount > 0) {
                    setSectionExpanded(sectionElement, true);
                }

                const count = sectionElement.querySelector(".examination-resume-section-count");

                if (count) {
                    count.textContent = query === "" ?
                        `${items.length} informasi` :
                        `${sectionItemCount} cocok`;
                }
            });

            navigation.querySelectorAll("[data-section-target]").forEach((item) => {
                const target = document.getElementById(item.dataset.sectionTarget);
                item.hidden = !target || target.hidden;
            });

            searchEmpty.hidden = visibleItemCount > 0;
            sections.hidden = visibleItemCount === 0;
            searchStatus.textContent = query === "" ?
                "" :
                `${visibleItemCount} informasi ditemukan dalam ${visibleSectionCount} bagian.`;
            updateToggleState();
        }

        function renderResume(resume) {
            const resumeSections = Array.isArray(resume.sections) ? resume.sections : [];

            loading.hidden = true;
            error.hidden = true;
            service.textContent = valueOrDash(resume.jenis_layanan);
            noRawat.textContent = valueOrDash(resume.no_rawat);
            doctor.textContent = valueOrDash(resume.dokter);

            if (resumeSections.length === 0) {
                empty.hidden = false;
                workspace.hidden = true;
                sections.hidden = true;
                return;
            }

            empty.hidden = true;
            workspace.hidden = false;
            navigation.replaceChildren(...resumeSections.map(createNavigationItem));
            sections.replaceChildren(...resumeSections.map(createResumeSection));
            sections.hidden = false;
            resetSearchState();
            updateToggleState();
        }

        async function loadResume(trigger) {
            activeRequest?.abort();
            activeRequest = new AbortController();
            const currentRequest = ++requestSequence;

            showLoading(trigger);
            trigger.disabled = true;
            trigger.setAttribute("aria-busy", "true");

            try {
                const response = await fetch(trigger.dataset.resumeUrl, {
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
                    throw new Error(payload.message || "Resume belum dapat dimuat.");
                }

                renderResume(payload.data || {});
            } catch (requestError) {
                if (requestError.name !== "AbortError" && currentRequest === requestSequence) {
                    showError(requestError.message);
                }
            } finally {
                trigger.disabled = false;
                trigger.removeAttribute("aria-busy");
            }
        }

        modalElement.addEventListener("show.bs.modal", (event) => {
            const trigger = event.relatedTarget;

            if (!(trigger instanceof HTMLElement)) {
                showError("Data kunjungan tidak valid.");
                return;
            }

            loadResume(trigger);
        });

        retry.addEventListener("click", () => {
            if (lastTrigger) {
                loadResume(lastTrigger);
            }
        });

        search.addEventListener("input", filterResume);
        searchClear.addEventListener("click", () => {
            resetSearchState();
            filterResume();
            search.focus();
        });
        resetSearch.addEventListener("click", () => {
            resetSearchState();
            filterResume();
            search.focus();
        });

        toggle.addEventListener("click", () => {
            const shouldExpand = toggle.dataset.action === "expand";

            sections.querySelectorAll(".examination-resume-section:not([hidden])")
                .forEach((sectionElement) => setSectionExpanded(sectionElement, shouldExpand));
            updateToggleState();
        });

        modalElement.addEventListener("hidden.bs.modal", () => {
            requestSequence++;
            activeRequest?.abort();
            activeRequest = null;
            lastTrigger = null;
            sections.replaceChildren();
            navigation.replaceChildren();
            overview.hidden = true;
            loading.hidden = false;
            error.hidden = true;
            empty.hidden = true;
            workspace.hidden = true;
            sections.hidden = true;
            resetSearchState();
        });

        const paymentModalElement = document.getElementById("examinationPaymentModal");

        if (!paymentModalElement) {
            return;
        }

        const paymentLoading = document.getElementById("examinationPaymentLoading");
        const paymentError = document.getElementById("examinationPaymentError");
        const paymentErrorMessage = document.getElementById("examinationPaymentErrorMessage");
        const paymentRetry = document.getElementById("examinationPaymentRetry");
        const paymentEmpty = document.getElementById("examinationPaymentEmpty");
        const paymentReceipt = document.getElementById("examinationPaymentReceipt");
        const paymentPrint = document.getElementById("examinationPaymentPrint");
        const paymentReceiptNumber = document.getElementById("examinationPaymentReceiptNumber");
        const paymentDate = document.getElementById("examinationPaymentDate");
        const paymentStatus = document.getElementById("examinationPaymentStatus");
        const paymentPatient = document.getElementById("examinationPaymentPatient");
        const paymentMedicalRecord = document.getElementById("examinationPaymentMedicalRecord");
        const paymentService = document.getElementById("examinationPaymentService");
        const paymentClinic = document.getElementById("examinationPaymentClinic");
        const paymentNoRawat = document.getElementById("examinationPaymentNoRawat");
        const paymentDoctor = document.getElementById("examinationPaymentDoctor");
        const paymentGuarantor = document.getElementById("examinationPaymentGuarantor");
        const paymentInformationSection = document.getElementById("examinationPaymentInformationSection");
        const paymentInformation = document.getElementById("examinationPaymentInformation");
        const paymentRowCount = document.getElementById("examinationPaymentRowCount");
        const paymentRows = document.getElementById("examinationPaymentRows");
        const paymentSubtotal = document.getElementById("examinationPaymentSubtotal");
        const paymentAdditional = document.getElementById("examinationPaymentAdditional");
        const paymentDeduction = document.getElementById("examinationPaymentDeduction");
        const paymentTotal = document.getElementById("examinationPaymentTotal");
        const currencyFormatter = new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });
        const numberFormatter = new Intl.NumberFormat("id-ID", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });
        let paymentRequest = null;
        let paymentLastTrigger = null;
        let paymentRequestSequence = 0;

        function formatCurrency(value) {
            const amount = Number(value);

            return currencyFormatter.format(Number.isFinite(amount) ? amount : 0);
        }

        function formatNumber(value) {
            const amount = Number(value);

            return numberFormatter.format(Number.isFinite(amount) ? amount : 0);
        }

        function setPaymentLoading(trigger) {
            paymentLastTrigger = trigger;
            paymentLoading.hidden = false;
            paymentError.hidden = true;
            paymentEmpty.hidden = true;
            paymentReceipt.hidden = true;
            paymentPrint.hidden = true;
            paymentInformation.replaceChildren();
            paymentRows.replaceChildren();
        }

        function setPaymentError(message) {
            paymentLoading.hidden = true;
            paymentError.hidden = false;
            paymentEmpty.hidden = true;
            paymentReceipt.hidden = true;
            paymentPrint.hidden = true;
            paymentErrorMessage.textContent = valueOrDash(message);
        }

        function createPaymentInformation(row) {
            const item = document.createElement("article");
            const content = document.createElement("div");
            const label = document.createElement("small");
            const value = document.createElement("strong");

            label.textContent = valueOrDash(row.label);
            value.textContent = valueOrDash(row.description || row.nm_perawatan);
            content.append(label, value);
            item.append(content);

            return item;
        }

        function appendPaymentTextCell(rowElement, value, className = "") {
            const cell = document.createElement("td");

            cell.textContent = value;

            if (className) {
                cell.className = className;
            }

            rowElement.append(cell);
        }

        function appendPaymentCalculation(rowElement, row) {
            const cell = document.createElement("td");
            const price = Number(row.biaya) || 0;
            const quantity = Number(row.jumlah) || 0;
            const additional = Number(row.tambahan) || 0;

            cell.className = "calculation";

            if (price === 0 && quantity === 0 && additional === 0) {
                cell.textContent = "-";
                rowElement.append(cell);
                return;
            }

            const priceValue = document.createElement("strong");
            const quantityValue = document.createElement("span");

            priceValue.textContent = Number(row.totalbiaya) < 0 ?
                `− ${formatCurrency(price)}` :
                formatCurrency(price);
            quantityValue.textContent = `× ${formatNumber(quantity)}`;
            cell.append(priceValue, quantityValue);

            if (additional !== 0) {
                const additionalValue = document.createElement("em");

                additionalValue.textContent = `+ ${formatCurrency(additional)}`;
                cell.append(additionalValue);
            }

            rowElement.append(cell);
        }

        function paymentSectionLabel(row) {
            const label = [row.label, row.description]
                .filter((value, index, values) => value && values.indexOf(value) === index)
                .join(" - ")
                .trim();

            return label || valueOrDash(row.status);
        }

        function rowsWithSectionSubtotals(rows) {
            const renderedRows = [];
            let activeSection = null;

            const appendSectionSubtotal = () => {
                if (!activeSection) {
                    return;
                }

                renderedRows.push({
                    type: "computed-subtotal",
                    section_label: activeSection.label,
                    item_count: activeSection.itemCount,
                    totalbiaya: activeSection.total,
                });
                activeSection = null;
            };

            rows.forEach((row) => {
                if (row.type === "subtotal") {
                    return;
                }

                if (row.type === "heading") {
                    appendSectionSubtotal();
                    activeSection = {
                        label: paymentSectionLabel(row),
                        itemCount: 0,
                        total: 0,
                    };
                    renderedRows.push(row);
                    return;
                }

                renderedRows.push(row);

                if (row.type !== "detail") {
                    return;
                }

                if (!activeSection) {
                    activeSection = {
                        label: valueOrDash(row.status),
                        itemCount: 0,
                        total: 0,
                    };
                }

                activeSection.itemCount += 1;
                activeSection.total += Number(row.totalbiaya) || 0;
            });

            appendSectionSubtotal();

            return renderedRows;
        }

        function createPaymentRow(row) {
            const rowElement = document.createElement("tr");
            const description = [row.label, row.description]
                .filter((value, index, values) => value && values.indexOf(value) === index)
                .join(" - ");

            rowElement.className = `is-${row.type || "note"}`;

            if (row.type === "heading") {
                const headingCell = document.createElement("td");
                const icon = document.createElement("i");
                const copy = document.createElement("span");

                headingCell.colSpan = 4;
                icon.className = "bi bi-grid";
                copy.textContent = description || valueOrDash(row.status);
                headingCell.append(icon, copy);
                rowElement.append(headingCell);

                return rowElement;
            }

            if (row.type === "computed-subtotal") {
                appendPaymentTextCell(
                    rowElement,
                    `Subtotal ${valueOrDash(row.section_label)}`,
                    "description"
                );
                appendPaymentTextCell(
                    rowElement,
                    `${Number(row.item_count) || 0} rincian`,
                    "category"
                );
                appendPaymentTextCell(rowElement, "Total bagian", "calculation");
                appendPaymentTextCell(
                    rowElement,
                    formatCurrency(row.totalbiaya),
                    `numeric total${Number(row.totalbiaya) < 0 ? " is-negative" : ""}`
                );

                return rowElement;
            }

            appendPaymentTextCell(rowElement, description || "-", "description");
            appendPaymentTextCell(rowElement, valueOrDash(row.status), "category");
            appendPaymentCalculation(rowElement, row);
            appendPaymentTextCell(
                rowElement,
                formatCurrency(row.totalbiaya),
                `numeric total${Number(row.totalbiaya) < 0 ? " is-negative" : ""}`
            );

            return rowElement;
        }

        function renderPayment(payment) {
            const rows = Array.isArray(payment.rows) ? payment.rows : [];
            const informationRows = rows.filter((row) => row.type === "information");
            const statementRows = rows.filter((row) => row.type !== "information");
            const summary = payment.summary || {};
            const statusText = valueOrDash(payment.status_bayar);
            const isPaid = statusText.toLocaleLowerCase("id-ID").includes("sudah");

            paymentLoading.hidden = true;
            paymentError.hidden = true;

            if (rows.length === 0) {
                paymentEmpty.hidden = false;
                paymentReceipt.hidden = true;
                paymentPrint.hidden = true;
                return;
            }

            paymentEmpty.hidden = true;
            paymentReceipt.hidden = false;
            paymentPrint.hidden = false;
            paymentReceiptNumber.textContent = valueOrDash(payment.nomor_nota);
            paymentDate.textContent = valueOrDash(payment.tanggal_bayar_lengkap);
            paymentStatus.classList.toggle("is-pending", !isPaid);
            paymentStatus.querySelector("i").className = isPaid ?
                "bi bi-check-circle" :
                "bi bi-hourglass-split";
            paymentStatus.querySelector("span").textContent = statusText;
            paymentPatient.textContent = valueOrDash(payment.pasien);
            paymentMedicalRecord.textContent = `No. RM ${valueOrDash(payment.no_rkm_medis)}`;
            paymentService.textContent = valueOrDash(payment.jenis_layanan);
            paymentClinic.textContent = valueOrDash(payment.poli);
            paymentNoRawat.textContent = valueOrDash(payment.no_rawat);
            paymentDoctor.textContent = valueOrDash(payment.dokter);
            paymentGuarantor.textContent = valueOrDash(payment.penjamin);
            paymentInformationSection.hidden = informationRows.length === 0;
            paymentInformation.replaceChildren(...informationRows.map(createPaymentInformation));
            paymentRows.replaceChildren(
                ...rowsWithSectionSubtotals(statementRows).map(createPaymentRow)
            );
            paymentRowCount.textContent = `${Number(summary.jumlah_item || 0)} rincian biaya`;
            paymentSubtotal.textContent = formatCurrency(summary.subtotal);
            paymentAdditional.textContent = formatCurrency(summary.tambahan);
            paymentDeduction.textContent = Number(summary.pengurang) > 0 ?
                `− ${formatCurrency(summary.pengurang)}` :
                formatCurrency(0);
            paymentTotal.textContent = formatCurrency(summary.total);
        }

        async function loadPayment(trigger) {
            paymentRequest?.abort();
            paymentRequest = new AbortController();
            const currentRequest = ++paymentRequestSequence;

            setPaymentLoading(trigger);
            trigger.disabled = true;
            trigger.setAttribute("aria-busy", "true");

            try {
                const response = await fetch(trigger.dataset.paymentUrl, {
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    signal: paymentRequest.signal,
                });
                const payload = await response.json().catch(() => ({}));

                if (currentRequest !== paymentRequestSequence) {
                    return;
                }

                if (!response.ok) {
                    throw new Error(payload.message || "Nota pembayaran belum dapat dimuat.");
                }

                renderPayment(payload.data || {});
            } catch (requestError) {
                if (requestError.name !== "AbortError" && currentRequest === paymentRequestSequence) {
                    setPaymentError(requestError.message);
                }
            } finally {
                trigger.disabled = false;
                trigger.removeAttribute("aria-busy");
            }
        }

        paymentModalElement.addEventListener("show.bs.modal", (event) => {
            const trigger = event.relatedTarget;

            if (!(trigger instanceof HTMLElement) || !trigger.dataset.paymentUrl) {
                setPaymentError("Data kunjungan tidak valid.");
                return;
            }

            loadPayment(trigger);
        });

        paymentRetry.addEventListener("click", () => {
            if (paymentLastTrigger) {
                loadPayment(paymentLastTrigger);
            }
        });

        paymentPrint.addEventListener("click", () => {
            document.body.classList.add("examination-payment-printing");
            window.print();
        });

        window.addEventListener("afterprint", () => {
            document.body.classList.remove("examination-payment-printing");
        });

        paymentModalElement.addEventListener("hidden.bs.modal", () => {
            paymentRequestSequence++;
            paymentRequest?.abort();
            paymentRequest = null;
            paymentLastTrigger = null;
            paymentLoading.hidden = false;
            paymentError.hidden = true;
            paymentEmpty.hidden = true;
            paymentReceipt.hidden = true;
            paymentPrint.hidden = true;
            paymentInformation.replaceChildren();
            paymentRows.replaceChildren();
            document.body.classList.remove("examination-payment-printing");
        });
    });
</script>
