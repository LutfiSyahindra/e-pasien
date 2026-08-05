<div id="ep-pwa-splash" class="ep-pwa-splash" role="status" aria-label="Membuka E-Pasien">
    <div class="ep-pwa-splash__content">
        <img src="{{ asset("landing/assets/imagesArsy/epasien.png") }}" alt="E-Pasien">
        <span class="ep-pwa-splash__loader" aria-hidden="true"></span>
    </div>
</div>

<script>
    (function () {
        var splash = document.getElementById("ep-pwa-splash");
        var standalone = window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
        var storageKey = "epasien:pwa-splash-shown:v1";
        var alreadyShown = false;

        try {
            alreadyShown = window.sessionStorage.getItem(storageKey) === "1";
        } catch (error) {
            alreadyShown = false;
        }

        if (!standalone || alreadyShown) {
            splash.hidden = true;
            return;
        }

        document.documentElement.classList.add("ep-pwa-splash-active");

        try {
            window.sessionStorage.setItem(storageKey, "1");
        } catch (error) {
            // The splash still works when browser storage is unavailable.
        }

        var startedAt = Date.now();
        var hidden = false;

        function hideSplash() {
            if (hidden) {
                return;
            }

            var remaining = Math.max(0, 650 - (Date.now() - startedAt));

            window.setTimeout(function () {
                if (hidden) {
                    return;
                }

                hidden = true;
                splash.classList.add("is-hiding");
                document.documentElement.classList.remove("ep-pwa-splash-active");

                window.setTimeout(function () {
                    splash.hidden = true;
                }, 220);
            }, remaining);
        }

        window.addEventListener("load", hideSplash, { once: true });
        window.setTimeout(hideSplash, 3000);
    })();
</script>
