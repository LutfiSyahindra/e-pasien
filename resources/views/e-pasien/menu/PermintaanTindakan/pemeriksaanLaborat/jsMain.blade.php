<script>
    document.addEventListener("DOMContentLoaded", () => {
        const modalElement = document.getElementById("laboratoryResultModal");

        if (!modalElement) {
            return;
        }

        const loading = document.getElementById("laboratoryResultLoading");
        const error = document.getElementById("laboratoryResultError");
        const errorMessage = document.getElementById("laboratoryResultErrorMessage");
        const retry = document.getElementById("laboratoryResultRetry");
        const empty = document.getElementById("laboratoryResultEmpty");
        const documentPanel = document.getElementById("laboratoryResultDocument");
        const printButton = document.getElementById("laboratoryResultPrint");
        const subtitle = document.getElementById("laboratoryResultSubtitle");
        const order = document.getElementById("laboratoryResultOrder");
        const treatment = document.getElementById("laboratoryResultTreatment");
        const resultDate = document.getElementById("laboratoryResultDate");
        const doctor = document.getElementById("laboratoryResultDoctor");
        const clinic = document.getElementById("laboratoryResultClinic");
        const care = document.getElementById("laboratoryResultCare");
        const diagnosis = document.getElementById("laboratoryResultDiagnosis");
        const information = document.getElementById("laboratoryResultInformation");
        const groupCount = document.getElementById("laboratoryResultGroupCount");
        const parameterCount = document.getElementById("laboratoryResultParameterCount");
        const noteCount = document.getElementById("laboratoryResultNoteCount");
        const groups = document.getElementById("laboratoryResultGroups");
        let activeRequest = null;
        let lastTrigger = null;
        let requestSequence = 0;

        function valueOrDash(value) {
            const text = String(value ?? "").trim();

            return text || "-";
        }

        function setLoading(trigger) {
            lastTrigger = trigger;
            loading.hidden = false;
            error.hidden = true;
            empty.hidden = true;
            documentPanel.hidden = true;
            printButton.hidden = true;
            subtitle.textContent = "Memuat data permintaan...";
            groups.replaceChildren();
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

        function appendCell(row, value, className = "", label = "") {
            const cell = document.createElement("td");

            cell.textContent = valueOrDash(value);

            if (className) {
                cell.className = className;
            }

            if (label) {
                cell.dataset.label = label;
            }

            row.append(cell);
        }

        function createResultRow(parameter, index) {
            const row = document.createElement("tr");
            const numberCell = document.createElement("td");
            const number = document.createElement("span");
            const resultCell = document.createElement("td");
            const resultValue = document.createElement("strong");
            const unit = document.createElement("small");
            const noteCell = document.createElement("td");
            const noteBadge = document.createElement("span");

            number.textContent = String(index + 1);
            numberCell.className = "laboratory-result-number";
            numberCell.dataset.label = "Nomor";
            numberCell.append(number);
            row.append(numberCell);
            appendCell(row, parameter.nama, "laboratory-result-parameter", "Parameter");

            resultValue.textContent = valueOrDash(parameter.nilai);
            unit.textContent = parameter.satuan === "-" ? "" : valueOrDash(parameter.satuan);
            resultCell.className = "laboratory-result-value";
            resultCell.dataset.label = "Hasil";
            resultCell.append(resultValue, unit);
            row.append(resultCell);
            appendCell(
                row,
                parameter.nilai_rujukan,
                "laboratory-result-reference",
                "Nilai Rujukan"
            );

            noteBadge.textContent = valueOrDash(parameter.keterangan);
            noteBadge.className = parameter.memiliki_catatan ? "has-note" : "";
            noteCell.className = "laboratory-result-remark";
            noteCell.dataset.label = "Keterangan";
            noteCell.append(noteBadge);
            row.append(noteCell);

            return row;
        }

        function createResultGroup(group) {
            const section = document.createElement("section");
            const heading = document.createElement("header");
            const icon = document.createElement("span");
            const iconElement = document.createElement("i");
            const title = document.createElement("div");
            const name = document.createElement("h3");
            const meta = document.createElement("small");
            const tableWrap = document.createElement("div");
            const table = document.createElement("table");
            const tableHead = document.createElement("thead");
            const headRow = document.createElement("tr");
            const tableBody = document.createElement("tbody");
            const headings = ["No.", "Parameter", "Hasil", "Nilai Rujukan", "Keterangan"];
            const parameters = Array.isArray(group.parameter) ? group.parameter : [];

            section.className = "laboratory-result-group";
            iconElement.className = "bi bi-clipboard2-pulse";
            icon.append(iconElement);
            name.textContent = valueOrDash(group.nama);
            meta.textContent = `${parameters.length} parameter`;
            title.append(name, meta);
            heading.append(icon, title);

            headings.forEach((label) => {
                const cell = document.createElement("th");

                cell.scope = "col";
                cell.textContent = label;
                headRow.append(cell);
            });
            tableHead.append(headRow);
            tableBody.replaceChildren(
                ...parameters.map((parameter, index) => createResultRow(parameter, index))
            );
            table.append(tableHead, tableBody);
            tableWrap.className = "laboratory-result-table-wrap";
            tableWrap.append(table);
            section.append(heading, tableWrap);

            return section;
        }

        function renderResult(payload) {
            const request = payload.permintaan || {};
            const summary = payload.ringkasan || {};
            const resultGroups = Array.isArray(payload.kelompok_hasil)
                ? payload.kelompok_hasil
                : [];

            loading.hidden = true;
            error.hidden = true;

            if (resultGroups.length === 0) {
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
            order.textContent = valueOrDash(request.noorder);
            treatment.textContent = `No. Rawat ${valueOrDash(request.no_rawat)}`;
            resultDate.textContent = `${valueOrDash(request.tanggal_hasil_lengkap)} · ${valueOrDash(request.jam_hasil_aktual || request.jam_hasil)} WIB`;
            doctor.textContent = valueOrDash(request.dokter_perujuk);
            clinic.textContent = valueOrDash(request.poli);
            care.textContent = valueOrDash(request.jenis_layanan);
            diagnosis.textContent = valueOrDash(request.diagnosa_klinis);
            information.textContent = valueOrDash(request.informasi_tambahan);
            groupCount.textContent = String(Number(summary.jumlah_jenis) || 0);
            parameterCount.textContent = String(Number(summary.jumlah_parameter) || 0);
            noteCount.textContent = String(Number(summary.jumlah_catatan) || 0);
            groups.replaceChildren(...resultGroups.map(createResultGroup));
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
                    throw new Error(payload.message || "Hasil laboratorium belum dapat dimuat.");
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

        modalElement.addEventListener("show.bs.modal", (event) => {
            const trigger = event.relatedTarget;

            if (!(trigger instanceof HTMLElement) || !trigger.dataset.resultUrl) {
                setError("Data permintaan laboratorium tidak valid.");
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

        modalElement.addEventListener("hidden.bs.modal", () => {
            requestSequence++;
            activeRequest?.abort();
            activeRequest = null;
            lastTrigger = null;
            loading.hidden = false;
            error.hidden = true;
            empty.hidden = true;
            documentPanel.hidden = true;
            printButton.hidden = true;
            groups.replaceChildren();
        });
    });
</script>
