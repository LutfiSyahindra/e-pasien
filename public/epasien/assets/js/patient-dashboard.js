(function () {
    "use strict";

    const panel = document.querySelector("[data-doctor-queue-panel]");

    if (!panel || !window.fetch) {
        return;
    }

    const list = panel.querySelector("[data-doctor-queue-list]");
    const live = panel.querySelector("[data-doctor-queue-live]");
    const liveLabel = panel.querySelector("[data-doctor-queue-live-label]");
    const refreshed = panel.querySelector("[data-doctor-queue-refreshed]");
    const endpoint = panel.dataset.endpoint;
    const refreshInterval = Math.max(Number(panel.dataset.refreshInterval) || 10000, 10000);
    let loading = false;
    let lastQueueSignature = null;

    if (!list || !endpoint) {
        return;
    }

    function element(tag, className, text) {
        const node = document.createElement(tag);

        if (className) {
            node.className = className;
        }

        if (text !== undefined && text !== null) {
            node.textContent = String(text);
        }

        return node;
    }

    function icon(name) {
        const node = element("i", "bi " + name);
        node.setAttribute("aria-hidden", "true");

        return node;
    }

    function queueCard(queue) {
        const card = element(
            "article",
            "patient-dashboard-queue-card" + (queue.is_patient_queue ? " is-patient" : "")
        );
        const number = element("div", "patient-dashboard-queue-number");
        const body = element("div", "patient-dashboard-queue-card__body");
        const doctor = element("div", "patient-dashboard-queue-doctor");
        const avatar = element("span", "patient-dashboard-queue-avatar");
        const identity = element("span", "patient-dashboard-queue-doctor__identity");
        const clinic = element("span", "patient-dashboard-queue-clinic");

        number.append(
            element("small", "", queue.number_label || "Sedang dipanggil"),
            element("strong", "", queue.current_number || "-")
        );

        avatar.append(element("span", "", queue.doctor_initials || "DR"));

        if (queue.doctor_photo_url) {
            const photo = element("img");
            photo.src = queue.doctor_photo_url;
            photo.alt = "Foto " + (queue.doctor_name || "dokter");
            photo.loading = "lazy";
            photo.decoding = "async";
            photo.addEventListener("error", function () {
                photo.remove();
            });
            avatar.append(photo);
        }

        clinic.append(icon("bi-hospital"), document.createTextNode(queue.clinic_name || "Poliklinik"));
        identity.append(clinic, element("h3", "", queue.doctor_name || "Dokter belum tercatat"));
        doctor.append(avatar, identity);
        body.append(doctor);

        if (queue.is_patient_queue) {
            const yours = element("span", "patient-dashboard-queue-yours");
            yours.append(
                icon("bi-person-check-fill"),
                document.createTextNode("Antrean Anda " + (queue.patient_number || "-"))
            );
            body.append(yours, element("p", "", queue.patient_message || "Pantau panggilan petugas."));
        } else {
            body.append(element("p", "", queue.queue_message || "Panggilan antrean sedang berlangsung."));
        }

        if (queue.serviced_at_label) {
            const time = element("time");
            if (queue.serviced_at) {
                time.dateTime = queue.serviced_at;
            }
            time.append(
                icon("bi-clock-history"),
                document.createTextNode("Pembaruan pelayanan " + queue.serviced_at_label)
            );
            body.append(time);
        }

        card.append(number, body);

        return card;
    }

    function emptyState() {
        const state = element("div", "patient-dashboard-state patient-dashboard-queue-empty");
        state.setAttribute("role", "status");
        const symbol = element("span");
        const copy = element("div");

        symbol.append(icon("bi-hourglass-split"));
        copy.append(
            element("h3", "", "Belum ada antrean poli berjalan"),
            element("p", "", "Nomor antrean akan muncul saat petugas poli mulai memanggil pasien.")
        );
        state.append(symbol, copy);

        return state;
    }

    function render(queues) {
        const fragment = document.createDocumentFragment();

        if (queues.length) {
            queues.forEach(function (queue) {
                fragment.append(queueCard(queue));
            });
        } else {
            fragment.append(emptyState());
        }

        list.replaceChildren(fragment);
    }

    async function refreshQueues() {
        if (loading || document.hidden) {
            return;
        }

        loading = true;
        list.setAttribute("aria-busy", "true");

        try {
            const response = await fetch(endpoint, {
                cache: "no-store",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) {
                throw new Error("Queue refresh failed with status " + response.status);
            }

            const payload = await response.json();
            const queues = Array.isArray(payload.data) ? payload.data : [];
            const queueSignature = JSON.stringify(queues);

            if (queueSignature !== lastQueueSignature) {
                render(queues);
                lastQueueSignature = queueSignature;
            }
            live?.classList.remove("is-delayed");

            if (liveLabel) {
                liveLabel.textContent = "Live";
            }

            if (refreshed) {
                refreshed.textContent = queues.length
                    ? "Diperbarui " + (payload.meta?.refreshed_at_label || "baru saja")
                    : "Menunggu panggilan";
            }
        } catch (error) {
            live?.classList.add("is-delayed");

            if (liveLabel) {
                liveLabel.textContent = "Tertunda";
            }

            if (refreshed) {
                refreshed.textContent = "Pembaruan tertunda";
            }
        } finally {
            loading = false;
            list.setAttribute("aria-busy", "false");
        }
    }

    window.setInterval(refreshQueues, refreshInterval);
    document.addEventListener("visibilitychange", function () {
        if (!document.hidden) {
            refreshQueues();
        }
    });
})();
