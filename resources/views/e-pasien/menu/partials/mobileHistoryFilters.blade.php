<script>
    document.addEventListener("DOMContentLoaded", () => {
        const mobileViewport = window.matchMedia("(max-width: 767px)");

        document.querySelectorAll("[data-mobile-filter-toggle]").forEach((toggle) => {
            const panelId = toggle.getAttribute("aria-controls");
            const panel = panelId ? document.getElementById(panelId) : null;

            if (!panel) {
                return;
            }

            toggle.setAttribute("data-mobile-filter-ready", "");

            let mobileExpanded = Boolean(
                panel.querySelector(".laboratory-filter-error, .prescription-filter-error")
            );

            const syncPanel = () => {
                const isExpanded = !mobileViewport.matches || mobileExpanded;

                panel.hidden = !isExpanded;
                toggle.setAttribute("aria-expanded", String(isExpanded));
            };

            toggle.addEventListener("click", () => {
                mobileExpanded = !mobileExpanded;
                syncPanel();
            });

            if (typeof mobileViewport.addEventListener === "function") {
                mobileViewport.addEventListener("change", syncPanel);
            } else {
                mobileViewport.addListener(syncPanel);
            }

            syncPanel();
        });
    });
</script>
