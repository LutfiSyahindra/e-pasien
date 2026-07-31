(function () {
    "use strict";

    const config = window.suratKontrolConfig || {};
    const tabs = Array.from(document.querySelectorAll("[data-control-tab]"));
    const panels = {
        umum: document.getElementById("generalControlPanel"),
        bpjs: document.getElementById("bpjsControlPanel")
    };
    const searchModeInputs = Array.from(
        document.querySelectorAll('input[name="bpjs_search_mode"]')
    );
    const automaticSearchHelp = document.getElementById("bpjsAutomaticSearchHelp");
    const periodGroup = document.getElementById("bpjsPeriodGroup");
    const periodInput = document.getElementById("bpjsControlPeriod");
    const loadButton = document.getElementById("loadBpjsControlLetters");
    const statusBox = document.getElementById("bpjsControlStatus");
    const searchSummary = document.getElementById("bpjsSearchSummary");
    const listBox = document.getElementById("bpjsControlList");
    const maskedCard = document.getElementById("bpjsMaskedCard");
    let bpjsLoaded = false;
    let activeRequest = null;

    if (!tabs.length || !panels.umum || !panels.bpjs) {
        return;
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function text(value, fallback = "-") {
        const normalized = String(value ?? "").trim();
        return normalized || fallback;
    }

    function formatDate(value, fallback = "Belum ditentukan") {
        const normalized = String(value ?? "").trim();

        if (!normalized || normalized.startsWith("0000-00-00")) {
            return fallback;
        }

        const date = new Date(`${normalized.substring(0, 10)}T00:00:00`);

        if (Number.isNaN(date.getTime())) {
            return normalized;
        }

        return new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "long",
            year: "numeric"
        }).format(date);
    }

    function isUpcoming(value) {
        const normalized = String(value ?? "").trim();

        if (!/^\d{4}-\d{2}-\d{2}/.test(normalized)) {
            return false;
        }

        const date = new Date(`${normalized.substring(0, 10)}T00:00:00`);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        return !Number.isNaN(date.getTime()) && date >= today;
    }

    function setTab(name, updateUrl) {
        const selected = name === "bpjs" ? "bpjs" : "umum";

        tabs.forEach((tab) => {
            const active = tab.dataset.controlTab === selected;
            tab.classList.toggle("active", active);
            tab.setAttribute("aria-selected", active ? "true" : "false");
            tab.tabIndex = active ? 0 : -1;
        });

        Object.entries(panels).forEach(([panelName, panel]) => {
            panel.hidden = panelName !== selected;
        });

        if (updateUrl && window.history && window.URL) {
            const url = new URL(window.location.href);
            url.searchParams.set("tab", selected);
            window.history.replaceState({ controlLetterTab: selected }, "", url);
        }

        if (selected === "bpjs" && !bpjsLoaded && config.hasPatient !== false) {
            loadBpjsLetters();
        }
    }

    function selectedSearchMode() {
        return searchModeInputs.find((input) => input.checked)?.value === "period"
            ? "period"
            : "automatic";
    }

    function updateSearchMode() {
        const mode = selectedSearchMode();

        searchModeInputs.forEach((input) => {
            input.closest("label")?.classList.toggle("active", input.checked);
        });

        if (automaticSearchHelp) {
            automaticSearchHelp.hidden = mode !== "automatic";
        }

        if (periodGroup) {
            periodGroup.hidden = mode !== "period";
        }

        const buttonLabel = loadButton?.querySelector("span");
        if (buttonLabel) {
            buttonLabel.textContent = mode === "automatic"
                ? "Cari jadwal otomatis"
                : "Cari pada bulan ini";
        }

        bpjsLoaded = false;
    }

    function hideSearchSummary() {
        if (!searchSummary) {
            return;
        }

        searchSummary.hidden = true;
        searchSummary.innerHTML = "";
    }

    function showSearchSummary(data, count) {
        if (!searchSummary) {
            return;
        }

        const periodLabel = text(data.periode?.label, "rentang pencarian");
        const monthCount = Number(data.periode?.jumlah_bulan || 0);
        searchSummary.innerHTML = `
            <i class="bi bi-check2-circle"></i>
            <span>
                Ditemukan <strong>${escapeHtml(count)} surat</strong> setelah memeriksa
                ${monthCount > 0 ? `${escapeHtml(monthCount)} bulan · ` : ""}
                ${escapeHtml(periodLabel)}.
            </span>
            <button type="button" data-bpjs-edit-search>
                <i class="bi bi-sliders"></i>
                Ubah
            </button>
        `;
        searchSummary.hidden = false;
    }

    function loadingState(mode) {
        statusBox.className = "control-letter-bpjs-status";
        statusBox.innerHTML = `
            <div class="control-letter-loading" aria-label="Memuat surat kontrol BPJS">
                <span class="control-letter-loading-mark" aria-hidden="true"></span>
                <strong>${mode === "automatic"
                    ? "Mencari jadwal kontrol Anda..."
                    : "Menghubungkan ke layanan BPJS..."}</strong>
                <p>${mode === "automatic"
                    ? "Sistem sedang memeriksa tujuh bulan. Proses ini mungkin memerlukan beberapa saat."
                    : "Mohon tunggu, data surat kontrol sedang diperiksa."}</p>
                <span class="control-letter-loading-lines" aria-hidden="true">
                    <span></span><span></span>
                </span>
            </div>
        `;
    }

    function stateMessage(type, icon, title, message, retry) {
        hideSearchSummary();
        statusBox.className = `control-letter-bpjs-status is-${type}`;
        statusBox.innerHTML = `
            <span class="control-letter-state-icon">
                <i class="bi ${escapeHtml(icon)}"></i>
            </span>
            <strong>${escapeHtml(title)}</strong>
            <p>${escapeHtml(message)}</p>
            ${retry ? `
                <button type="button" class="control-letter-state-action" data-bpjs-retry>
                    <i class="bi bi-arrow-clockwise"></i>
                    Coba lagi
                </button>
            ` : ""}
        `;
    }

    function controlTone(letter) {
        if (isUpcoming(letter.tgl_rencana_kontrol)) {
            return {
                card: "waiting",
                label: "Jadwal mendatang",
                icon: "bi-calendar2-check"
            };
        }

        if (String(letter.terbit_sep ?? "").trim() === "1") {
            return {
                card: "examined",
                label: "SEP sudah terbit",
                icon: "bi-check2-circle"
            };
        }

        return {
            card: "neutral",
            label: "Jadwal terdahulu",
            icon: "bi-clock-history"
        };
    }

    function renderBpjsCard(letter) {
        const tone = controlTone(letter);
        const controlType = text(
            letter.nama_jenis_kontrol,
            text(letter.jenis_kontrol, "Surat Kontrol")
        );
        const destinationClinic = text(
            letter.nama_poli_tujuan,
            text(letter.poli_tujuan, "Poli tujuan belum tersedia")
        );
        const doctor = text(letter.nama_dokter, "Dokter belum ditentukan");
        const controlNumber = text(letter.no_surat_kontrol);
        const planDate = formatDate(letter.tgl_rencana_kontrol);
        const issuedDate = formatDate(
            letter.tgl_terbit_kontrol,
            "Tanggal terbit belum tersedia"
        );

        return `
            <article class="control-letter-card bpjs-control-letter-card tone-${tone.card}">
                <details>
                    <summary>
                        <span class="control-letter-date">
                            <i class="bi bi-calendar2-check"></i>
                            <span>
                                <small>Tanggal rencana kontrol</small>
                                <strong>${escapeHtml(planDate)}</strong>
                                <em>${escapeHtml(controlType)}</em>
                            </span>
                        </span>

                        <span class="control-letter-card-main">
                            <span class="control-letter-status ${tone.card}">
                                <i class="bi ${tone.icon}"></i>
                                ${escapeHtml(tone.label)}
                            </span>
                            <strong>${escapeHtml(destinationClinic)}</strong>
                            <small>
                                <i class="bi bi-person-badge"></i>
                                ${escapeHtml(doctor)}
                            </small>
                        </span>

                        <span class="control-letter-expand">
                            <span class="desktop-label">Lihat rincian</span>
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </summary>

                    <div class="control-letter-card-detail">
                        ${isUpcoming(letter.tgl_rencana_kontrol) ? `
                            <div class="control-letter-next-visit">
                                <i class="bi bi-bell"></i>
                                <span>
                                    <strong>Jadwal kontrol BPJS mendatang</strong>
                                    <small>Gunakan surat ini sesuai tanggal rencana kontrol.</small>
                                </span>
                            </div>
                        ` : ""}

                        <div class="bpjs-control-overview">
                            <span>
                                <small>Nomor surat kontrol</small>
                                <strong class="bpjs-control-number">${escapeHtml(controlNumber)}</strong>
                            </span>
                            <span>
                                <small>Poli tujuan</small>
                                <strong>${escapeHtml(destinationClinic)}</strong>
                            </span>
                            <span>
                                <small>Dokter kontrol</small>
                                <strong>${escapeHtml(doctor)}</strong>
                            </span>
                            <span>
                                <small>Tanggal terbit</small>
                                <strong>${escapeHtml(issuedDate)}</strong>
                            </span>
                            <span>
                                <small>SEP asal kontrol</small>
                                <strong class="bpjs-control-number">${escapeHtml(text(letter.no_sep_asal_kontrol))}</strong>
                            </span>
                            <span>
                                <small>Poli asal</small>
                                <strong>${escapeHtml(text(letter.nama_poli_asal, text(letter.poli_asal)))}</strong>
                            </span>
                        </div>
                    </div>
                </details>
            </article>
        `;
    }

    function showMaskedCard(value) {
        const cardValue = maskedCard?.querySelector("strong");
        const normalized = String(value ?? "").trim();

        if (!maskedCard || !cardValue || !normalized) {
            if (maskedCard) {
                maskedCard.hidden = true;
            }
            return;
        }

        cardValue.textContent = normalized;
        maskedCard.hidden = false;
    }

    async function loadBpjsLetters() {
        if (!periodInput || !loadButton || !statusBox || !listBox) {
            return;
        }

        if (config.hasPatient === false) {
            stateMessage(
                "warning",
                "bi-person-x",
                "Data pasien belum tersedia",
                "Surat BPJS dapat dicari setelah data pasien ditemukan.",
                false
            );
            return;
        }

        const mode = selectedSearchMode();
        const period = String(periodInput.value || "").trim();

        if (mode === "period" && !/^\d{4}-\d{2}$/.test(period)) {
            stateMessage(
                "warning",
                "bi-calendar-x",
                "Periode belum dipilih",
                "Pilih bulan pencarian untuk menampilkan surat kontrol BPJS.",
                false
            );
            periodInput.focus();
            return;
        }

        if (activeRequest) {
            activeRequest.abort();
        }

        const request = new AbortController();
        activeRequest = request;
        loadButton.disabled = true;
        loadButton.classList.add("loading");
        listBox.innerHTML = "";
        showMaskedCard("");
        hideSearchSummary();
        loadingState(mode);

        try {
            const url = new URL(config.bpjsUrl, window.location.origin);
            url.searchParams.set("mode", mode);

            if (mode === "period") {
                url.searchParams.set("periode", period);
            }

            const response = await fetch(url, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                credentials: "same-origin",
                signal: request.signal
            });
            const payload = await response.json().catch(() => ({}));
            const data = payload.data || {};
            const letters = Array.isArray(data.surat_kontrol)
                ? data.surat_kontrol
                : [];

            showMaskedCard(data.masked_card_number);

            if (!response.ok) {
                const unavailable = response.status === 422;
                stateMessage(
                    unavailable ? "warning" : "error",
                    unavailable ? "bi-credit-card-2-front" : "bi-cloud-slash",
                    unavailable
                        ? "Nomor kartu BPJS belum tersedia"
                        : "Layanan BPJS belum dapat diakses",
                    payload.message || "Silakan coba kembali beberapa saat lagi.",
                    !unavailable
                );
                return;
            }

            bpjsLoaded = true;

            if (!letters.length) {
                const periodLabel = text(
                    data.periode?.label,
                    "periode yang dipilih"
                );
                stateMessage(
                    "warning",
                    "bi-file-earmark-x",
                    "Surat kontrol tidak ditemukan",
                    mode === "automatic"
                        ? `Tidak ada surat kontrol BPJS pada ${periodLabel}. Gunakan pilihan bulan untuk mencari dokumen yang lebih lama.`
                        : `Tidak ada surat kontrol BPJS pada ${periodLabel}. Coba pilih periode lain.`,
                    false
                );
                return;
            }

            statusBox.className = "control-letter-bpjs-status";
            statusBox.innerHTML = "";
            showSearchSummary(data, letters.length);
            listBox.innerHTML = letters.map(renderBpjsCard).join("");
        } catch (error) {
            if (error?.name === "AbortError") {
                return;
            }

            stateMessage(
                "error",
                "bi-wifi-off",
                "Koneksi terputus",
                "Surat kontrol BPJS belum berhasil dimuat. Periksa koneksi lalu coba lagi.",
                true
            );
        } finally {
            if (activeRequest === request) {
                loadButton.disabled = false;
                loadButton.classList.remove("loading");
                activeRequest = null;
            }
        }
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener("click", () => {
            setTab(tab.dataset.controlTab, true);
        });

        tab.addEventListener("keydown", (event) => {
            if (!["ArrowLeft", "ArrowRight"].includes(event.key)) {
                return;
            }

            event.preventDefault();
            const offset = event.key === "ArrowRight" ? 1 : -1;
            const nextIndex = (index + offset + tabs.length) % tabs.length;
            tabs[nextIndex].focus();
            tabs[nextIndex].click();
        });
    });

    loadButton?.addEventListener("click", loadBpjsLetters);
    searchModeInputs.forEach((input) => {
        input.addEventListener("change", updateSearchMode);
    });
    periodInput?.addEventListener("change", () => {
        bpjsLoaded = false;
    });

    statusBox?.addEventListener("click", (event) => {
        if (event.target.closest("[data-bpjs-retry]")) {
            loadBpjsLetters();
        }
    });
    searchSummary?.addEventListener("click", (event) => {
        if (!event.target.closest("[data-bpjs-edit-search]")) {
            return;
        }

        document.querySelector(".control-letter-search-mode")?.scrollIntoView({
            behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches
                ? "auto"
                : "smooth",
            block: "start"
        });
        searchModeInputs.find((input) => input.checked)?.focus({
            preventScroll: true
        });
    });

    updateSearchMode();
    setTab(config.initialTab === "bpjs" ? "bpjs" : "umum", false);
})();
