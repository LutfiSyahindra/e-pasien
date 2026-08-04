(function () {
    "use strict";

    var loaderScript = document.currentScript;
    var sweetAlertPromise;
    var input = document.getElementById("promo-image");
    var preview = document.querySelector("[data-image-preview]");
    var placeholder = document.querySelector("[data-upload-placeholder]");

    function loadSweetAlert() {
        if (window.Swal) return Promise.resolve(window.Swal);
        if (sweetAlertPromise) return sweetAlertPromise;

        sweetAlertPromise = new Promise(function (resolve, reject) {
            var premiumCssUrl = loaderScript && loaderScript.dataset.premiumCss;
            if (premiumCssUrl && !document.querySelector('link[data-promo-swal-css]')) {
                var stylesheet = document.createElement("link");
                stylesheet.rel = "stylesheet";
                stylesheet.href = premiumCssUrl;
                stylesheet.dataset.promoSwalCss = "true";
                document.head.appendChild(stylesheet);
            }

            var script = document.createElement("script");
            var settled = false;
            var timeout = window.setTimeout(function () {
                if (settled) return;
                settled = true;
                reject(new Error("SweetAlert load timeout"));
            }, 4000);

            script.src = "https://cdn.jsdelivr.net/npm/sweetalert2@11";
            script.async = true;
            script.dataset.promoSwal = "true";
            script.onload = function () {
                if (settled) return;
                settled = true;
                window.clearTimeout(timeout);
                window.Swal ? resolve(window.Swal) : reject(new Error("SweetAlert unavailable"));
            };
            script.onerror = function () {
                if (settled) return;
                settled = true;
                window.clearTimeout(timeout);
                reject(new Error("SweetAlert failed to load"));
            };
            document.head.appendChild(script);
        });

        return sweetAlertPromise;
    }

    function fallbackDeleteConfirmation(form) {
        if (window.confirm("Hapus konten ini secara permanen?")) form.submit();
    }

    function showDeleteConfirmation(form) {
        loadSweetAlert().then(function (Swal) {
            Swal.fire({
                title: "Hapus konten?",
                text: "Konten dan gambar akan dihapus permanen.",
                icon: "warning",
                iconHtml: '<i class="bi bi-exclamation-triangle"></i>',
                buttonsStyling: false,
                focusConfirm: false,
                heightAuto: false,
                reverseButtons: true,
                width: "min(360px, calc(100vw - 32px))",
                showCancelButton: true,
                confirmButtonText: "Ya, hapus",
                cancelButtonText: "Batal",
                customClass: {
                    container: "ep-swal-container",
                    popup: "ep-swal-popup",
                    icon: "ep-swal-icon",
                    title: "ep-swal-title",
                    htmlContainer: "ep-swal-text",
                    actions: "ep-swal-actions",
                    confirmButton: "ep-swal-button ep-swal-confirm",
                    cancelButton: "ep-swal-button ep-swal-cancel"
                }
            }).then(function (result) { if (result.isConfirmed) form.submit(); });
        }).catch(function () {
            fallbackDeleteConfirmation(form);
        });
    }

    if (input && preview) {
        input.addEventListener("change", function () {
            var file = input.files && input.files[0];
            if (!file) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            if (placeholder) placeholder.hidden = true;
        });
    }

    ["title", "caption"].forEach(function (name) {
        var field = document.querySelector('[name="' + name + '"]');
        var counter = document.querySelector('[data-count-for="' + name + '"]');
        if (!field || !counter) return;
        var update = function () { counter.textContent = field.value.length; };
        field.addEventListener("input", update);
        update();
    });

    document.querySelectorAll("[data-promo-delete]").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();
            showDeleteConfirmation(form);
        });
    });
})();
