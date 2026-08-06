(function () {
    "use strict";

    var trigger = document.querySelector("[data-promo-image-open]");
    var viewer = document.querySelector("[data-promo-image-viewer]");
    var closeButton = viewer && viewer.querySelector("[data-promo-image-close]");

    if (!trigger || !viewer || !closeButton) return;

    function openViewer() {
        document.documentElement.classList.add("promo-image-viewer-open");

        if (typeof viewer.showModal === "function") {
            viewer.showModal();
        } else {
            viewer.setAttribute("open", "");
        }

        closeButton.focus();
    }

    function closeViewer() {
        if (typeof viewer.close === "function") {
            viewer.close();
        } else {
            viewer.removeAttribute("open");
            document.documentElement.classList.remove("promo-image-viewer-open");
            trigger.focus();
        }
    }

    trigger.addEventListener("click", openViewer);
    closeButton.addEventListener("click", closeViewer);

    viewer.addEventListener("click", function (event) {
        if (event.target === viewer || event.target.classList.contains("promo-image-viewer__stage")) {
            closeViewer();
        }
    });

    viewer.addEventListener("close", function () {
        document.documentElement.classList.remove("promo-image-viewer-open");
        trigger.focus();
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && viewer.hasAttribute("open") && typeof viewer.close !== "function") {
            closeViewer();
        }
    });
})();
