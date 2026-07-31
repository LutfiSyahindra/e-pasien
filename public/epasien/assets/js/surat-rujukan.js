(function () {
    "use strict";

    const config = window.suratRujukanConfig || {};
    const mainTabs = Array.from(
        document.querySelectorAll("[data-referral-tab]")
    );
    const mainPanels = {
        masuk: document.getElementById("incomingReferralPanel"),
        keluar: document.getElementById("outgoingReferralPanel")
    };
    const outgoingTabs = Array.from(
        document.querySelectorAll("[data-outgoing-tab]")
    );
    const outgoingPanels = {
        umum: document.getElementById("generalOutgoingPanel"),
        bpjs: document.getElementById("bpjsOutgoingPanel")
    };
    const incomingStatus = document.getElementById("incomingReferralStatus");
    const incomingList = document.getElementById("incomingReferralList");
    const incomingMaskedCard = document.getElementById("incomingMaskedCard");
    const outgoingForm = document.getElementById("bpjsOutgoingForm");
    const outgoingStart = document.getElementById("bpjsOutgoingStart");
    const outgoingEnd = document.getElementById("bpjsOutgoingEnd");
    const outgoingButton = document.getElementById("loadBpjsOutgoing");
    const outgoingStatus = document.getElementById("bpjsOutgoingStatus");
    const outgoingList = document.getElementById("bpjsOutgoingList");
    const outgoingMaskedCard = document.getElementById("outgoingMaskedCard");
    let incomingLoaded = false;
    let outgoingLoaded = false;
    let incomingRequest = null;
    let outgoingRequest = null;

    if (!mainTabs.length || !mainPanels.masuk || !mainPanels.keluar) {
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

    function dateLabel(date) {
        return text(date?.date_label, "Tanggal belum tersedia");
    }

    function updateUrl(values) {
        if (!window.history || !window.URL) {
            return;
        }

        const url = new URL(window.location.href);
        Object.entries(values).forEach(([key, value]) => {
            if (value) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
        });
        url.searchParams.delete("umum_page");
        window.history.replaceState({ referralLetter: true }, "", url);
    }

    function setMainTab(name, shouldUpdateUrl) {
        const selected = name === "keluar" ? "keluar" : "masuk";

        mainTabs.forEach((tab) => {
            const active = tab.dataset.referralTab === selected;
            tab.classList.toggle("active", active);
            tab.setAttribute("aria-selected", active ? "true" : "false");
            tab.tabIndex = active ? 0 : -1;
        });

        Object.entries(mainPanels).forEach(([panelName, panel]) => {
            panel.hidden = panelName !== selected;
        });

        if (shouldUpdateUrl) {
            updateUrl({
                tab: selected,
                jenis: selected === "keluar"
                    ? selectedOutgoingType()
                    : null
            });
        }

        if (selected === "masuk" && !incomingLoaded) {
            loadIncomingReferrals();
        }

        if (
            selected === "keluar"
            && selectedOutgoingType() === "bpjs"
            && !outgoingLoaded
        ) {
            loadOutgoingReferrals();
        }
    }

    function selectedOutgoingType() {
        return outgoingTabs.find((tab) => tab.classList.contains("active"))
            ?.dataset.outgoingTab === "bpjs"
            ? "bpjs"
            : "umum";
    }

    function setOutgoingTab(name, shouldUpdateUrl) {
        const selected = name === "bpjs" ? "bpjs" : "umum";

        outgoingTabs.forEach((tab) => {
            const active = tab.dataset.outgoingTab === selected;
            tab.classList.toggle("active", active);
            tab.setAttribute("aria-selected", active ? "true" : "false");
            tab.tabIndex = active ? 0 : -1;
        });

        Object.entries(outgoingPanels).forEach(([panelName, panel]) => {
            if (panel) {
                panel.hidden = panelName !== selected;
            }
        });

        if (shouldUpdateUrl) {
            updateUrl({ tab: "keluar", jenis: selected });
        }

        if (
            selected === "bpjs"
            && !outgoingLoaded
            && !mainPanels.keluar.hidden
        ) {
            loadOutgoingReferrals();
        }
    }

    function loadingState(container, title, message) {
        if (!container) {
            return;
        }

        container.className = "referral-state";
        container.innerHTML = `
            <div class="referral-loading" role="status">
                <span class="referral-loading-mark" aria-hidden="true"></span>
                <strong>${escapeHtml(title)}</strong>
                <p>${escapeHtml(message)}</p>
            </div>
        `;
    }

    function stateMessage(
        container,
        type,
        icon,
        title,
        message,
        retryTarget
    ) {
        if (!container) {
            return;
        }

        container.className = `referral-state is-${type}`;
        container.innerHTML = `
            <span class="referral-state-icon">
                <i class="bi ${escapeHtml(icon)}"></i>
            </span>
            <strong>${escapeHtml(title)}</strong>
            <p>${escapeHtml(message)}</p>
            ${retryTarget ? `
                <button type="button"
                    class="referral-state-action"
                    data-referral-retry="${escapeHtml(retryTarget)}">
                    <i class="bi bi-arrow-clockwise"></i>
                    Coba lagi
                </button>
            ` : ""}
        `;
    }

    function clearState(container) {
        if (!container) {
            return;
        }

        container.className = "referral-state";
        container.innerHTML = "";
    }

    function showMaskedCard(container, value) {
        const number = container?.querySelector("strong");
        const normalized = String(value ?? "").trim();

        if (!container || !number || !normalized) {
            if (container) {
                container.hidden = true;
            }
            return;
        }

        number.textContent = normalized;
        container.hidden = false;
    }

    function setSourceState(sourceName, state, count) {
        const card = document.querySelector(
            `[data-source-card="${sourceName}"]`
        );
        const status = card?.querySelector("[data-source-status]");

        if (!card || !status) {
            return;
        }

        card.classList.remove(
            "is-loading",
            "is-success",
            "is-empty",
            "is-error"
        );
        card.classList.add(`is-${state}`);

        if (state === "loading") {
            status.textContent = "Memeriksa";
        } else if (state === "success") {
            status.textContent = `${Number(count || 0)} ditemukan`;
        } else if (state === "error") {
            status.textContent = "Terkendala";
        } else {
            status.textContent = "Tidak ada";
        }
    }

    function updateSourceStates(sources) {
        ["pcare", "rumah_sakit"].forEach((name) => {
            const source = sources?.[name] || {};
            setSourceState(name, source.state || "error", source.count || 0);
        });
    }

    function renderIncomingCard(referral) {
        const destination = text(
            referral.poli_tujuan?.nama,
            "Poli tujuan belum tersedia"
        );
        const source = text(referral.source, "bpjs");
        const sourceLabel = text(referral.source_label, "BPJS");
        const provider = text(
            referral.perujuk?.nama,
            "Fasilitas perujuk belum tersedia"
        );

        return `
            <article class="referral-card tone-bpjs">
                <details>
                    <summary>
                        <span class="referral-date-block">
                            <i class="bi bi-calendar2-check"></i>
                            <span>
                                <small>Tanggal kunjungan</small>
                                <strong>${escapeHtml(dateLabel(referral.tanggal))}</strong>
                            </span>
                        </span>
                        <span class="referral-card-main">
                            <span class="referral-badge ${escapeHtml(source)}">
                                <i class="bi ${source === "pcare"
                                    ? "bi-heart-pulse"
                                    : "bi-hospital"}"></i>
                                ${escapeHtml(sourceLabel)}
                            </span>
                            <strong>${escapeHtml(destination)}</strong>
                            <small>
                                <i class="bi bi-building"></i>
                                ${escapeHtml(provider)}
                            </small>
                        </span>
                        <span class="referral-expand">
                            <span>Lihat rincian</span>
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </summary>
                    <div class="referral-card-details">
                        <div class="referral-number-box">
                            <span>
                                <small>Nomor rujukan</small>
                                <strong>${escapeHtml(text(referral.no_rujukan))}</strong>
                            </span>
                            <span>
                                <small>Jenis pelayanan</small>
                                <strong>${escapeHtml(text(referral.pelayanan?.nama))}</strong>
                            </span>
                        </div>
                        <div class="referral-detail-grid">
                            <span>
                                <small>Diagnosis</small>
                                <strong>${escapeHtml(text(referral.diagnosa?.nama))}</strong>
                            </span>
                            <span>
                                <small>Kode diagnosis</small>
                                <strong>${escapeHtml(text(referral.diagnosa?.kode))}</strong>
                            </span>
                            <span>
                                <small>Keluhan</small>
                                <strong>${escapeHtml(text(referral.keluhan))}</strong>
                            </span>
                            <span>
                                <small>Fasilitas perujuk</small>
                                <strong>${escapeHtml(provider)}</strong>
                            </span>
                            <span>
                                <small>Peserta</small>
                                <strong>${escapeHtml(text(referral.peserta?.nama))}</strong>
                            </span>
                            <span>
                                <small>Status peserta</small>
                                <strong>${escapeHtml(text(referral.peserta?.status))}</strong>
                            </span>
                        </div>
                    </div>
                </details>
            </article>
        `;
    }

    function renderOutgoingCard(referral) {
        const provider = text(
            referral.provider_tujuan?.nama,
            "Fasilitas tujuan belum tersedia"
        );

        return `
            <article class="referral-card tone-bpjs">
                <details>
                    <summary>
                        <span class="referral-date-block">
                            <i class="bi bi-calendar2-week"></i>
                            <span>
                                <small>Tanggal rujukan</small>
                                <strong>${escapeHtml(dateLabel(referral.tanggal))}</strong>
                            </span>
                        </span>
                        <span class="referral-card-main">
                            <span class="referral-badge bpjs">
                                <i class="bi bi-shield-plus"></i>
                                ${escapeHtml(text(
                                    referral.jenis_pelayanan_label,
                                    "BPJS"
                                ))}
                            </span>
                            <strong>${escapeHtml(provider)}</strong>
                            <small>
                                <i class="bi bi-person"></i>
                                ${escapeHtml(text(referral.nama, "Nama peserta"))}
                            </small>
                        </span>
                        <span class="referral-expand">
                            <span>Lihat rincian</span>
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </summary>
                    <div class="referral-card-details">
                        <div class="referral-number-box">
                            <span>
                                <small>Nomor rujukan</small>
                                <strong>${escapeHtml(text(referral.no_rujukan))}</strong>
                            </span>
                            <span>
                                <small>Nomor SEP</small>
                                <strong>${escapeHtml(text(referral.no_sep))}</strong>
                            </span>
                        </div>
                        <div class="referral-detail-grid">
                            <span>
                                <small>Fasilitas tujuan</small>
                                <strong>${escapeHtml(provider)}</strong>
                            </span>
                            <span>
                                <small>Kode fasilitas</small>
                                <strong>${escapeHtml(text(
                                    referral.provider_tujuan?.kode
                                ))}</strong>
                            </span>
                            <span>
                                <small>Jenis pelayanan</small>
                                <strong>${escapeHtml(text(
                                    referral.jenis_pelayanan_label
                                ))}</strong>
                            </span>
                        </div>
                    </div>
                </details>
            </article>
        `;
    }

    async function loadIncomingReferrals() {
        if (!incomingStatus || !incomingList) {
            return;
        }

        if (config.hasPatient === false) {
            stateMessage(
                incomingStatus,
                "warning",
                "bi-person-x",
                "Data pasien belum tersedia",
                "Rujukan BPJS dapat dicari setelah akun terhubung dengan data pasien.",
                null
            );
            return;
        }

        if (incomingRequest) {
            incomingRequest.abort();
        }

        const request = new AbortController();
        incomingRequest = request;
        incomingList.innerHTML = "";
        showMaskedCard(incomingMaskedCard, "");
        setSourceState("pcare", "loading", 0);
        setSourceState("rumah_sakit", "loading", 0);
        loadingState(
            incomingStatus,
            "Memeriksa rujukan BPJS...",
            "PCare dan rumah sakit sedang diperiksa dalam satu pencarian."
        );

        try {
            const response = await fetch(config.incomingUrl, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                credentials: "same-origin",
                signal: request.signal
            });
            const payload = await response.json().catch(() => ({}));
            const data = payload.data || {};
            const referrals = Array.isArray(data.rujukan)
                ? data.rujukan
                : [];

            updateSourceStates(data.sources);
            showMaskedCard(incomingMaskedCard, data.masked_card_number);

            if (!response.ok) {
                const cardUnavailable = response.status === 422;
                stateMessage(
                    incomingStatus,
                    cardUnavailable ? "warning" : "error",
                    cardUnavailable
                        ? "bi-credit-card-2-front"
                        : "bi-cloud-slash",
                    cardUnavailable
                        ? "Nomor kartu BPJS belum tersedia"
                        : "Layanan BPJS belum dapat diakses",
                    payload.message || "Silakan coba kembali beberapa saat lagi.",
                    cardUnavailable ? null : "incoming"
                );
                return;
            }

            incomingLoaded = true;

            if (!referrals.length) {
                stateMessage(
                    incomingStatus,
                    "empty",
                    "bi-file-earmark-x",
                    "Rujukan BPJS tidak ditemukan",
                    payload.message
                        || "Tidak ada rujukan aktif dari PCare maupun rumah sakit.",
                    data.partial ? "incoming" : null
                );
                return;
            }

            if (data.partial) {
                stateMessage(
                    incomingStatus,
                    "warning",
                    "bi-exclamation-triangle",
                    "Sebagian sumber belum tersedia",
                    data.meta_data?.message
                        || "Rujukan yang berhasil ditemukan tetap ditampilkan.",
                    "incoming"
                );
            } else {
                clearState(incomingStatus);
            }

            incomingList.innerHTML = referrals
                .map(renderIncomingCard)
                .join("");
        } catch (error) {
            if (error?.name === "AbortError") {
                return;
            }

            setSourceState("pcare", "error", 0);
            setSourceState("rumah_sakit", "error", 0);
            stateMessage(
                incomingStatus,
                "error",
                "bi-wifi-off",
                "Koneksi terputus",
                "Rujukan BPJS belum berhasil dimuat. Periksa koneksi lalu coba lagi.",
                "incoming"
            );
        } finally {
            if (incomingRequest === request) {
                incomingRequest = null;
            }
        }
    }

    async function loadOutgoingReferrals(event) {
        event?.preventDefault();

        if (
            !outgoingStart
            || !outgoingEnd
            || !outgoingButton
            || !outgoingStatus
            || !outgoingList
        ) {
            return;
        }

        if (config.hasPatient === false) {
            stateMessage(
                outgoingStatus,
                "warning",
                "bi-person-x",
                "Data pasien belum tersedia",
                "Rujukan keluar BPJS dapat dicari setelah data pasien ditemukan.",
                null
            );
            return;
        }

        const startDate = String(outgoingStart.value || "").trim();
        const endDate = String(outgoingEnd.value || "").trim();

        if (!startDate || !endDate) {
            stateMessage(
                outgoingStatus,
                "warning",
                "bi-calendar-x",
                "Tanggal belum lengkap",
                "Pilih tanggal mulai dan tanggal akhir pencarian.",
                null
            );
            (!startDate ? outgoingStart : outgoingEnd).focus();
            return;
        }

        if (startDate > endDate) {
            stateMessage(
                outgoingStatus,
                "warning",
                "bi-calendar-x",
                "Rentang tanggal tidak valid",
                "Tanggal mulai harus sebelum atau sama dengan tanggal akhir.",
                null
            );
            outgoingStart.focus();
            return;
        }

        if (outgoingRequest) {
            outgoingRequest.abort();
        }

        const request = new AbortController();
        outgoingRequest = request;
        outgoingButton.disabled = true;
        outgoingList.innerHTML = "";
        showMaskedCard(outgoingMaskedCard, "");
        loadingState(
            outgoingStatus,
            "Menghubungkan ke VClaim...",
            "Daftar rujukan keluar RS sedang diperiksa untuk periode pilihan Anda."
        );

        try {
            const url = new URL(config.outgoingUrl, window.location.origin);
            url.searchParams.set("tanggal_mulai", startDate);
            url.searchParams.set("tanggal_akhir", endDate);

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
            const referrals = Array.isArray(data.rujukan)
                ? data.rujukan
                : [];

            showMaskedCard(outgoingMaskedCard, data.masked_card_number);

            if (!response.ok) {
                const cardUnavailable = response.status === 422;
                stateMessage(
                    outgoingStatus,
                    cardUnavailable ? "warning" : "error",
                    cardUnavailable
                        ? "bi-credit-card-2-front"
                        : "bi-cloud-slash",
                    cardUnavailable
                        ? "Nomor kartu BPJS belum tersedia"
                        : "Layanan VClaim belum dapat diakses",
                    payload.message || "Silakan coba kembali beberapa saat lagi.",
                    cardUnavailable ? null : "outgoing"
                );
                return;
            }

            outgoingLoaded = true;

            if (!referrals.length) {
                stateMessage(
                    outgoingStatus,
                    "empty",
                    "bi-file-earmark-x",
                    "Rujukan keluar tidak ditemukan",
                    payload.message
                        || "Coba gunakan rentang tanggal yang berbeda.",
                    null
                );
                return;
            }

            clearState(outgoingStatus);
            outgoingList.innerHTML = referrals
                .map(renderOutgoingCard)
                .join("");
        } catch (error) {
            if (error?.name === "AbortError") {
                return;
            }

            stateMessage(
                outgoingStatus,
                "error",
                "bi-wifi-off",
                "Koneksi terputus",
                "Rujukan keluar BPJS belum berhasil dimuat. Silakan coba lagi.",
                "outgoing"
            );
        } finally {
            if (outgoingRequest === request) {
                outgoingButton.disabled = false;
                outgoingRequest = null;
            }
        }
    }

    function wireArrowNavigation(tabs, attributeName) {
        tabs.forEach((tab, index) => {
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
    }

    mainTabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            setMainTab(tab.dataset.referralTab, true);
        });
    });

    outgoingTabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            setOutgoingTab(tab.dataset.outgoingTab, true);
        });
    });

    outgoingForm?.addEventListener("submit", loadOutgoingReferrals);
    [outgoingStart, outgoingEnd].forEach((input) => {
        input?.addEventListener("change", () => {
            outgoingLoaded = false;
        });
    });

    incomingStatus?.addEventListener("click", (event) => {
        if (event.target.closest('[data-referral-retry="incoming"]')) {
            incomingLoaded = false;
            loadIncomingReferrals();
        }
    });

    outgoingStatus?.addEventListener("click", (event) => {
        if (event.target.closest('[data-referral-retry="outgoing"]')) {
            outgoingLoaded = false;
            loadOutgoingReferrals();
        }
    });

    wireArrowNavigation(mainTabs);
    wireArrowNavigation(outgoingTabs);
    setOutgoingTab(
        config.initialOutgoingType === "bpjs" ? "bpjs" : "umum",
        false
    );
    setMainTab(config.initialTab === "keluar" ? "keluar" : "masuk", false);
})();
