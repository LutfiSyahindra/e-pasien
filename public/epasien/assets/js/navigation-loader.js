(function () {
    "use strict";

    var navigationTimer = null;

    function loader() {
        return document.getElementById("ep-navigation-loader");
    }

    function showLoader() {
        var element = loader();

        if (!element) {
            return;
        }

        window.clearTimeout(navigationTimer);
        element.hidden = false;
        element.setAttribute("aria-hidden", "false");
        document.documentElement.classList.add("ep-navigation-pending");

        window.requestAnimationFrame(function () {
            element.classList.add("is-visible");
        });
    }

    function hideLoader() {
        var element = loader();

        window.clearTimeout(navigationTimer);
        document.documentElement.classList.remove("ep-navigation-pending");

        if (!element) {
            return;
        }

        element.classList.remove("is-visible");
        element.setAttribute("aria-hidden", "true");
        element.hidden = true;
    }

    function navigationUrl(anchor) {
        if (
            !anchor ||
            anchor.hasAttribute("download") ||
            (anchor.target && anchor.target.toLowerCase() !== "_self")
        ) {
            return null;
        }

        var href = anchor.getAttribute("href");

        if (!href || href.charAt(0) === "#") {
            return null;
        }

        var url;

        try {
            url = new URL(anchor.href, window.location.href);
        } catch (error) {
            return null;
        }

        if (
            url.origin !== window.location.origin ||
            (url.protocol !== "http:" && url.protocol !== "https:")
        ) {
            return null;
        }

        if (url.href === window.location.href) {
            return null;
        }

        return url;
    }

    document.addEventListener("click", function (event) {
        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        if (!(event.target instanceof Element)) {
            return;
        }

        var anchor = event.target.closest("a[href]");
        var url = navigationUrl(anchor);

        if (!url) {
            return;
        }

        event.preventDefault();
        showLoader();

        navigationTimer = window.setTimeout(function () {
            window.location.assign(url.href);
        }, 40);
    }, true);

    document.addEventListener("submit", function (event) {
        if (!event.defaultPrevented) {
            showLoader();
        }
    });

    window.addEventListener("pageshow", hideLoader);
    window.addEventListener("load", hideLoader);

    window.EPasienNavigationLoader = {
        show: showLoader,
        hide: hideLoader
    };
})();
