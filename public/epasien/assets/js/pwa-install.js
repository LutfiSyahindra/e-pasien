(function () {
    'use strict';

    var deferredInstallPrompt = null;
    var toastDismissedKey = 'epasien.pwa-install-toast-dismissed-until.v1';
    var toastDismissedFor = 7 * 24 * 60 * 60 * 1000;
    var installedEvidenceKey = 'epasien.pwa-installed.v1';
    var deviceUuidKey = 'epasien.device-uuid.v1';
    var trackingInterval = 5 * 60 * 1000;

    var isInstalled = function () {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    };

    var isIos = function () {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent)
            || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
    };

    var rememberInstalled = function () {
        try {
            window.localStorage.setItem(installedEvidenceKey, String(Date.now()));
        } catch (_) {}
    };

    var hasInstallEvidence = function () {
        if (isInstalled()) return true;

        try {
            return Boolean(window.localStorage.getItem(installedEvidenceKey));
        } catch (_) {
            return false;
        }
    };

    var createUuid = function () {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (character) {
            var random = Math.floor(Math.random() * 16);
            var value = character === 'x' ? random : (random & 0x3) | 0x8;
            return value.toString(16);
        });
    };

    var deviceUuid = function () {
        try {
            var existing = window.localStorage.getItem(deviceUuidKey);
            if (existing) return existing;

            var generated = createUuid();
            window.localStorage.setItem(deviceUuidKey, generated);
            return generated;
        } catch (_) {
            return createUuid();
        }
    };

    var reportUsage = function (force) {
        var endpointMeta = document.querySelector('meta[name="epasien-access-tracking-url"]');
        var userMeta = document.querySelector('meta[name="epasien-user-id"]');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var endpoint = endpointMeta ? endpointMeta.content : '';
        var userId = userMeta ? userMeta.content : '';
        var csrfToken = csrfMeta ? csrfMeta.content : '';
        if (!endpoint || !userId || !csrfToken || typeof window.fetch !== 'function') return;

        var mode = isInstalled() ? 'pwa' : 'web';
        var reportKey = 'epasien.access-reported.' + userId + '.' + mode + '.v1';

        if (!force) {
            try {
                var lastReportedAt = Number(window.localStorage.getItem(reportKey) || 0);
                if (lastReportedAt > Date.now() - trackingInterval) return;
            } catch (_) {}
        }

        window.fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                device_uuid: deviceUuid(),
                mode: mode,
                installed: hasInstallEvidence()
            })
        }).then(function (response) {
            if (!response.ok) return;

            try {
                window.localStorage.setItem(reportKey, String(Date.now()));
            } catch (_) {}
        }).catch(function () {});
    };

    var installButtons = function () {
        return Array.prototype.slice.call(document.querySelectorAll('[data-pwa-install]'));
    };

    var updateStatus = function (message) {
        document.querySelectorAll('[data-pwa-status]').forEach(function (status) {
            status.textContent = message;
        });

        var toastMessage = document.querySelector('[data-pwa-install-toast] .ep-pwa-toast__copy > span');
        if (toastMessage) toastMessage.textContent = message;
    };

    var updateButtons = function (state) {
        installButtons().forEach(function (button) {
            var label = button.querySelector('[data-pwa-install-label]');
            var text = 'Install Aplikasi';

            if (state === 'installed') text = 'Aplikasi Terpasang';
            if (state === 'installing') text = 'Menyiapkan Instalasi...';
            if (label) label.textContent = text;
            else if (!button.closest('[data-pwa-install-toast]')) button.textContent = text;

            button.disabled = state === 'installed' || state === 'installing';
            button.classList.toggle('is-installed', state === 'installed');
            button.classList.toggle('is-loading', state === 'installing');
        });
    };

    var focusInstallHelp = function () {
        var section = document.querySelector('[data-pwa-section]');
        if (!section) return;

        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.setTimeout(function () {
            var help = section.querySelector('[data-pwa-manual-help]');
            if (help) {
                help.classList.add('is-highlighted');
                help.focus({ preventScroll: true });
                window.setTimeout(function () { help.classList.remove('is-highlighted'); }, 2200);
            }
        }, 650);
    };

    var showManualInstructions = function () {
        if (isIos()) {
            updateStatus('Di iPhone/iPad, ketuk Bagikan lalu pilih Tambahkan ke Layar Utama.');
        } else {
            updateStatus('Buka menu browser lalu pilih Instal aplikasi atau Tambahkan ke layar utama.');
        }

        focusInstallHelp();
    };

    var installApp = async function () {
        if (isInstalled()) {
            updateButtons('installed');
            updateStatus('E-Pasien sudah terpasang di perangkat ini.');
            return;
        }

        if (!deferredInstallPrompt) {
            showManualInstructions();
            return;
        }

        updateButtons('installing');

        try {
            deferredInstallPrompt.prompt();
            var choice = await deferredInstallPrompt.userChoice;
            deferredInstallPrompt = null;

            if (choice.outcome === 'accepted') {
                updateStatus('Instalasi dimulai. Ikon E-Pasien akan muncul di perangkat Anda.');
                dismissToast(false);
                return;
            }

            updateButtons('ready');
            updateStatus('Instalasi belum dilakukan. Anda dapat mencobanya kembali kapan saja.');
        } catch (_) {
            deferredInstallPrompt = null;
            updateButtons('ready');
            showManualInstructions();
        }
    };

    var dismissalIsActive = function () {
        try {
            return Number(window.localStorage.getItem(toastDismissedKey) || 0) > Date.now();
        } catch (_) {
            return false;
        }
    };

    var rememberToastDismissal = function () {
        try {
            window.localStorage.setItem(toastDismissedKey, String(Date.now() + toastDismissedFor));
        } catch (_) {}
    };

    var dismissToast = function (remember) {
        var toast = document.querySelector('[data-pwa-install-toast]');
        if (!toast) return;

        if (remember !== false) rememberToastDismissal();
        document.body.classList.remove('pwa-toast-visible');
        toast.classList.remove('is-visible');
        window.setTimeout(function () { toast.remove(); }, 280);
    };

    var createToast = function () {
        var toastEnabled = document.querySelector('[data-pwa-section]')
            || document.querySelector('meta[name="epasien-pwa-install-toast"][content="1"]');
        if (!toastEnabled || isInstalled() || dismissalIsActive() || document.querySelector('[data-pwa-install-toast]')) return;

        var toast = document.createElement('aside');
        toast.className = 'ep-pwa-toast';
        toast.setAttribute('data-pwa-install-toast', '');
        toast.setAttribute('role', 'region');
        toast.setAttribute('aria-label', 'Saran instalasi aplikasi E-Pasien');
        toast.setAttribute('aria-live', 'polite');
        toast.innerHTML = ''
            + '<button type="button" class="ep-pwa-toast__close" data-pwa-toast-close aria-label="Tutup saran instalasi">'
            + '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>'
            + '<span class="ep-pwa-toast__icon" aria-hidden="true">'
            + '<img src="/epasien/assets/images/pwa-icon-192.png" alt="" width="52" height="52"></span>'
            + '<span class="ep-pwa-toast__copy"><small>AKSES LEBIH MUDAH</small>'
            + '<strong>Install aplikasi E-Pasien</strong>'
            + '<span>Buka layanan rumah sakit lebih cepat langsung dari layar utama.</span></span>'
            + '<span class="ep-pwa-toast__actions">'
            + '<button type="button" class="ep-pwa-toast__install" data-pwa-install>'
            + '<span data-pwa-install-label>Install Aplikasi</span></button>'
            + '<button type="button" class="ep-pwa-toast__later" data-pwa-toast-close>Nanti</button></span>';

        document.body.appendChild(toast);
        document.body.classList.add('pwa-toast-visible');
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () { toast.classList.add('is-visible'); });
        });
    };

    var initialize = function () {
        if ('serviceWorker' in window.navigator) {
            window.navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
        }

        if (isInstalled()) {
            rememberInstalled();
            updateButtons('installed');
            updateStatus('E-Pasien sudah terpasang di perangkat ini.');
        } else if (deferredInstallPrompt) {
            updateButtons('ready');
            updateStatus('E-Pasien siap di-install di perangkat Anda.');
        } else if (isIos()) {
            updateStatus('Ketuk Install Aplikasi untuk melihat cara menambahkannya ke Layar Utama.');
        } else {
            updateStatus('Install langsung dari browser, tanpa perlu membuka Play Store.');
        }

        document.addEventListener('click', function (event) {
            var installButton = event.target.closest('[data-pwa-install]');
            var closeButton = event.target.closest('[data-pwa-toast-close]');

            if (installButton) {
                event.preventDefault();
                installApp();
            }

            if (closeButton) {
                event.preventDefault();
                dismissToast(true);
            }
        });

        var toastDelay = window.matchMedia('(max-width: 575px)').matches ? 3500 : 1200;
        window.setTimeout(createToast, toastDelay);
        reportUsage(false);
    };

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredInstallPrompt = event;
        updateButtons('ready');
        updateStatus('E-Pasien siap di-install di perangkat Anda.');
        if (document.readyState !== 'loading') createToast();
    });

    window.addEventListener('appinstalled', function () {
        deferredInstallPrompt = null;
        rememberInstalled();
        updateButtons('installed');
        updateStatus('E-Pasien berhasil dipasang di perangkat ini.');
        dismissToast(false);
        reportUsage(true);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
