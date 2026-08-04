const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || '';
const csrf = meta('csrf-token');
const userId = meta('epasien-user-id');
const listUrl = meta('epasien-notifications-url');
const readBaseUrl = meta('epasien-notifications-read-url');
const readAllUrl = meta('epasien-notifications-read-all-url');
const pushConfigUrl = meta('epasien-push-config-url');
const pushSubscriptionUrl = meta('epasien-push-subscription-url');
const notificationSoundUrl = meta('epasien-notification-sound-url') || '/landing/assets/sound/notif.mp3';
const pushOwnerStorageKey = 'epasien.push-owner.v1';
const notificationSoundStorageKey = 'epasien.notification-sound.v1';
const notificationCacheKey = `epasien.notifications.v1.${userId}`;

let notificationSound;
let notificationSoundUnlocked = false;
let unreadCount = 0;
let notificationsLoaded = false;
let notificationsLoading;

const getNotificationSound = (source = notificationSoundUrl) => {
    if (!notificationSound && source) {
        notificationSound = new Audio(source);
        notificationSound.preload = 'auto';
    }

    return notificationSound;
};

const prepareNotificationSound = () => {
    if (notificationSoundUnlocked) return Promise.resolve(true);

    const sound = getNotificationSound();
    if (!sound) return Promise.resolve(false);

    sound.muted = true;
    const playback = sound.play();
    if (!playback) {
        sound.muted = false;
        notificationSoundUnlocked = true;
        return Promise.resolve(true);
    }

    return playback.then(() => {
        sound.pause();
        sound.currentTime = 0;
        sound.muted = false;
        notificationSoundUnlocked = true;
        return true;
    }).catch(() => {
        sound.muted = false;
        return false;
    });
};

const notificationSoundKey = (data = {}) => {
    if (data.promotion_id) return `promotion-${data.promotion_id}`;
    return data.tag || data.url || 'notification';
};

const playNotificationSound = (key, source = notificationSoundUrl) => {
    if (document.visibilityState !== 'visible') return;

    const sound = getNotificationSound(source);
    if (!sound) return;

    const now = Date.now();
    try {
        const previous = JSON.parse(window.localStorage.getItem(notificationSoundStorageKey) || 'null');
        const sameNotification = key && previous?.key === key && now - previous.played_at < 30000;
        const simultaneousNotification = previous?.played_at && now - previous.played_at < 1200;
        if (sameNotification || simultaneousNotification) return;
        window.localStorage.setItem(notificationSoundStorageKey, JSON.stringify({ key, played_at: now }));
    } catch (_) {}

    sound.muted = false;
    sound.pause();
    sound.currentTime = 0;
    sound.play().then(() => {
        notificationSoundUnlocked = true;
    }).catch(() => {});
};

const relativeUrl = (value) => {
    if (!value) return '';
    try {
        const parsed = new URL(value, window.location.origin);
        return `${parsed.pathname}${parsed.search}${parsed.hash}`;
    } catch (_) {
        return value;
    }
};

const normalizedNotificationData = (value) => {
    const data = value || {};
    if (data.kind !== 'promotion') return data;
    return { ...data, image_url: relativeUrl(data.image_url), url: relativeUrl(data.url) };
};

const imageFallback = (image, fallback) => {
    image.addEventListener('error', () => {
        image.replaceWith(fallback());
    }, { once: true });
};

const request = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) },
        ...options,
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
};

const cachedNotifications = () => {
    try {
        const cached = JSON.parse(window.sessionStorage.getItem(notificationCacheKey) || 'null');
        return cached?.payload || null;
    } catch (_) {
        return null;
    }
};

const cacheNotifications = (payload) => {
    try {
        window.sessionStorage.setItem(notificationCacheKey, JSON.stringify({ payload }));
    } catch (_) {}
};

const forgetCachedNotifications = () => {
    try { window.sessionStorage.removeItem(notificationCacheKey); } catch (_) {}
};

const setBadge = (count) => {
    unreadCount = Number(count) || 0;
    const badge = document.querySelector('[data-notification-badge]');
    if (!badge) return;
    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
    badge.hidden = unreadCount < 1;
};

const notificationItem = (item) => {
    const button = document.createElement('button');
    const data = normalizedNotificationData(item.data);
    button.type = 'button';
    button.className = `ep-notification-item${item.read_at ? '' : ' is-unread'}`;
    button.dataset.id = item.id;
    button.dataset.url = data.url || '#';

    const visual = document.createElement('span');
    visual.className = 'ep-notification-item__image';
    if (data.image_url) {
        const img = document.createElement('img');
        img.src = data.image_url;
        img.alt = '';
        img.loading = 'lazy';
        img.decoding = 'async';
        imageFallback(img, () => {
            const icon = document.createElement('i');
            icon.className = 'bi bi-bell-fill';
            return icon;
        });
        visual.append(img);
    } else {
        const icon = document.createElement('i');
        icon.className = 'bi bi-bell-fill';
        visual.append(icon);
    }

    const copy = document.createElement('span');
    copy.className = 'ep-notification-item__copy';
    const title = document.createElement('strong');
    title.textContent = data.title || 'Notifikasi baru';
    const body = document.createElement('p');
    body.textContent = data.body || '';
    const time = document.createElement('small');
    time.textContent = item.time_label || 'baru saja';
    copy.append(title, body, time);
    button.append(visual, copy);

    button.addEventListener('click', async () => {
        if (!item.read_at) {
            try {
                const result = await request(`${readBaseUrl}/${item.id}/read`, { method: 'PATCH', body: '{}' });
                setBadge(result.unread_count || 0);
                button.classList.remove('is-unread');
                forgetCachedNotifications();
            } catch (_) {}
        }
        if (data.url) window.location.href = data.url;
    });
    return button;
};

const renderNotifications = (payload) => {
    const list = document.querySelector('[data-notification-list]');
    if (!list) return;
    list.replaceChildren();
    setBadge(payload.unread_count || 0);
    if (!payload.notifications?.length) {
        const empty = document.createElement('div');
        empty.className = 'ep-notification-empty';
        const icon = document.createElement('i'); icon.className = 'bi bi-bell-slash';
        const strong = document.createElement('strong'); strong.textContent = 'Belum ada notifikasi';
        const small = document.createElement('small'); small.textContent = 'Kabar terbaru akan muncul di sini.';
        empty.append(icon, strong, small);
        list.append(empty);
        return;
    }
    payload.notifications.forEach((item) => list.append(notificationItem(item)));
};

const loadNotifications = async ({ force = false } = {}) => {
    if (!listUrl || (notificationsLoaded && !force)) return;
    if (notificationsLoading) return notificationsLoading;

    notificationsLoading = request(listUrl)
        .then((payload) => {
            notificationsLoaded = true;
            cacheNotifications(payload);
            renderNotifications(payload);
        })
        .catch(() => {
            const list = document.querySelector('[data-notification-list]');
            if (list) list.textContent = 'Notifikasi belum dapat dimuat.';
        })
        .finally(() => { notificationsLoading = null; });

    return notificationsLoading;
};

const showLiveToast = (data) => {
    data = normalizedNotificationData(data);
    document.querySelector('.ep-live-toast')?.remove();
    const toast = document.createElement('div');
    toast.className = 'ep-live-toast';
    if (data.image_url) {
        const image = document.createElement('img'); image.src = data.image_url; image.alt = ''; image.decoding = 'async';
        imageFallback(image, () => {
            const icon = document.createElement('span');
            icon.className = 'ep-live-toast__icon';
            icon.innerHTML = '<i class="bi bi-bell-fill"></i>';
            return icon;
        });
        toast.append(image);
    } else {
        const icon = document.createElement('span'); icon.className = 'ep-live-toast__icon'; icon.innerHTML = '<i class="bi bi-bell-fill"></i>'; toast.append(icon);
    }
    const copy = document.createElement('span');
    const title = document.createElement('strong'); title.textContent = data.title || 'Kabar baru';
    const body = document.createElement('p'); body.textContent = data.body || '';
    copy.append(title, body);
    const close = document.createElement('button'); close.type = 'button'; close.innerHTML = '<i class="bi bi-x-lg"></i>'; close.addEventListener('click', (event) => { event.stopPropagation(); toast.remove(); });
    toast.append(copy, close);
    toast.addEventListener('click', () => { if (data.url) window.location.href = data.url; });
    document.body.append(toast);
    window.setTimeout(() => toast.remove(), 8000);
};

const base64ToUint8 = (value) => {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const raw = atob((value + padding).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)));
};

let pushBusy = false;
const updatePushButtons = (active, message) => {
    document.querySelectorAll('[data-push-toggle]').forEach((button) => {
        button.classList.toggle('is-active', active);
        button.disabled = pushBusy || active;
        const label = button.querySelector('span');
        if (label) label.textContent = active ? 'Notifikasi perangkat aktif' : 'Aktifkan notifikasi perangkat';
        const icon = button.querySelector('i');
        if (icon) icon.className = active ? 'bi bi-bell-check-fill' : 'bi bi-bell';
    });
    if (message) document.querySelectorAll('[data-push-hint]').forEach((hint) => { hint.textContent = message; });
};

const pushRegistration = async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return null;
    return (await navigator.serviceWorker.getRegistration('/'))
        || navigator.serviceWorker.register('/sw.js', { scope: '/' });
};

const pushSubscriptionPayload = (subscription) => {
    const json = subscription.toJSON();

    if (!json.endpoint || !json.keys?.p256dh || !json.keys?.auth) {
        throw new Error('invalid-subscription');
    }

    return {
        endpoint: json.endpoint,
        keys: json.keys,
        content_encoding: window.PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
    };
};

const rememberedPushOwner = () => {
    try { return window.sessionStorage.getItem(pushOwnerStorageKey); } catch (_) { return null; }
};

const rememberPushOwner = (ownerId) => {
    try {
        if (ownerId) window.sessionStorage.setItem(pushOwnerStorageKey, ownerId);
        else window.sessionStorage.removeItem(pushOwnerStorageKey);
    } catch (_) {}
};

const persistPushSubscription = async (subscription) => {
    const result = await request(pushSubscriptionUrl, {
        method: 'POST',
        body: JSON.stringify(pushSubscriptionPayload(subscription)),
    });
    rememberPushOwner(userId);
    return result;
};

const ensurePushSubscription = async () => {
    const registration = await pushRegistration();
    if (!registration) throw new Error('unsupported');
    if (Notification.permission !== 'granted') {
        throw new Error(Notification.permission === 'denied' ? 'denied' : 'permission-required');
    }

    let subscription = await registration.pushManager.getSubscription();
    if (!subscription) {
        const config = await request(pushConfigUrl);
        if (!config.enabled || !config.public_key) throw new Error('unconfigured');
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: base64ToUint8(config.public_key),
        });
    }

    if (rememberedPushOwner() !== userId) await persistPushSubscription(subscription);
    updatePushButtons(true, 'Notifikasi dan suara wajib aktif untuk menggunakan E-Pasien.');

    return subscription;
};

let notificationGate;
let notificationGateBlockedElements = [];
let notificationRequirementBusy = false;

const notificationGateElements = () => ({
    title: notificationGate?.querySelector('[data-notification-gate-title]'),
    message: notificationGate?.querySelector('[data-notification-gate-message]'),
    note: notificationGate?.querySelector('[data-notification-gate-note]'),
    button: notificationGate?.querySelector('[data-notification-gate-button]'),
    label: notificationGate?.querySelector('[data-notification-gate-label]'),
});

const createNotificationGate = () => {
    if (notificationGate) return notificationGate;

    notificationGate = document.createElement('div');
    notificationGate.className = 'ep-notification-gate';
    notificationGate.hidden = true;
    notificationGate.setAttribute('role', 'dialog');
    notificationGate.setAttribute('aria-modal', 'true');
    notificationGate.setAttribute('aria-labelledby', 'ep-notification-gate-title');
    notificationGate.innerHTML = `
        <section class="ep-notification-gate__panel">
            <span class="ep-notification-gate__eyebrow">Wajib diaktifkan</span>
            <span class="ep-notification-gate__icon" aria-hidden="true"><i class="bi bi-bell-fill"></i></span>
            <h2 id="ep-notification-gate-title" data-notification-gate-title>Aktifkan notifikasi &amp; suara</h2>
            <p data-notification-gate-message></p>
            <div class="ep-notification-gate__note">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <span data-notification-gate-note></span>
            </div>
            <button type="button" data-notification-gate-button>
                <span class="ep-notification-gate__spinner" aria-hidden="true"></span>
                <i class="bi bi-bell-check-fill" aria-hidden="true"></i>
                <span data-notification-gate-label>Aktifkan Sekarang</span>
            </button>
            <small>Izin notifikasi diperlukan agar informasi penting dari E-Pasien tidak terlewat.</small>
        </section>`;
    document.body.append(notificationGate);

    notificationGate.querySelector('[data-notification-gate-button]')
        ?.addEventListener('click', activateRequiredNotifications);
    notificationGate.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab') return;
        event.preventDefault();
        notificationGate.querySelector('[data-notification-gate-button]')?.focus();
    });

    return notificationGate;
};

const showNotificationGate = ({ state = 'default', title, message, note, label, busy = false }) => {
    const gate = createNotificationGate();
    const elements = notificationGateElements();
    gate.dataset.state = state;
    elements.title.textContent = title;
    elements.message.textContent = message;
    elements.note.textContent = note;
    elements.label.textContent = label;
    elements.button.disabled = busy;
    elements.button.classList.toggle('is-loading', busy);

    if (gate.hidden) {
        gate.hidden = false;
        document.body.classList.add('ep-notification-required');
        notificationGateBlockedElements = [...document.body.children].filter((element) => {
            if (element === gate || element.inert) return false;
            element.inert = true;
            return true;
        });
    }

    window.setTimeout(() => elements.button.focus(), 0);
};

const hideNotificationGate = () => {
    if (!notificationGate || notificationGate.hidden) return;
    notificationGate.hidden = true;
    document.body.classList.remove('ep-notification-required');
    notificationGateBlockedElements.forEach((element) => { element.inert = false; });
    notificationGateBlockedElements = [];
};

const showNotificationPermissionRequired = () => {
    const denied = Notification.permission === 'denied';
    showNotificationGate({
        state: denied ? 'denied' : 'default',
        title: denied ? 'Izin notifikasi masih diblokir' : 'Aktifkan notifikasi & suara',
        message: denied
            ? 'E-Pasien belum dapat dilanjutkan karena izin notifikasi ditolak atau diblokir.'
            : 'Satu kali klik akan mengaktifkan ringtone, meminta izin browser, dan mendaftarkan perangkat ini.',
        note: denied
            ? 'Buka ikon gembok/info pada browser, ubah Notifikasi menjadi Izinkan, lalu tekan Periksa Kembali.'
            : 'Saat browser bertanya, pilih Izinkan agar Anda dapat melanjutkan ke E-Pasien.',
        label: denied ? 'Periksa Kembali' : 'Aktifkan Sekarang',
    });
};

const showNotificationSetupError = (error) => {
    const messages = {
        unsupported: {
            title: 'Browser belum mendukung notifikasi',
            message: 'Gunakan browser yang mendukung Web Push. Pada iPhone/iPad, tambahkan E-Pasien ke Layar Utama terlebih dahulu.',
            note: 'Setelah menggunakan browser yang didukung, buka kembali halaman ini dan tekan Coba Lagi.',
        },
        unconfigured: {
            title: 'Push notification belum siap',
            message: 'Konfigurasi push notification pada server belum tersedia.',
            note: 'Hubungi administrator E-Pasien, kemudian tekan Coba Lagi setelah konfigurasi selesai.',
        },
    };
    const content = messages[error?.message] || {
        title: 'Notifikasi belum dapat diaktifkan',
        message: 'Terjadi kendala saat mendaftarkan perangkat ini ke layanan notifikasi.',
        note: 'Periksa koneksi internet Anda, lalu tekan Coba Lagi.',
    };

    showNotificationGate({
        state: 'error',
        ...content,
        label: 'Coba Lagi',
    });
};

const activateRequiredNotifications = async () => {
    if (notificationRequirementBusy) return;

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        showNotificationSetupError(new Error('unsupported'));
        return;
    }

    notificationRequirementBusy = true;
    pushBusy = true;
    updatePushButtons(false);
    showNotificationGate({
        state: 'loading',
        title: 'Mengaktifkan notifikasi',
        message: 'Selesaikan permintaan izin dari browser untuk melanjutkan.',
        note: 'Pilih Izinkan pada dialog browser yang muncul.',
        label: 'Sedang Mengaktifkan...',
        busy: true,
    });

    try {
        const soundPreparation = prepareNotificationSound();
        const permissionRequest = Notification.permission === 'default'
            ? Notification.requestPermission()
            : Promise.resolve(Notification.permission);
        const [, permission] = await Promise.all([soundPreparation, permissionRequest]);

        if (permission !== 'granted') {
            updatePushButtons(false, 'Izin notifikasi wajib diberikan untuk menggunakan E-Pasien.');
            showNotificationPermissionRequired();
            return;
        }

        showNotificationGate({
            state: 'loading',
            title: 'Mendaftarkan perangkat',
            message: 'Izin berhasil diberikan. Perangkat sedang dihubungkan ke layanan push notification.',
            note: 'Mohon tunggu sebentar, proses ini berjalan otomatis.',
            label: 'Sedang Mendaftarkan...',
            busy: true,
        });
        await ensurePushSubscription();
        hideNotificationGate();
    } catch (error) {
        showNotificationSetupError(error);
    } finally {
        notificationRequirementBusy = false;
        pushBusy = false;
        const button = notificationGateElements().button;
        if (button && !notificationGate.hidden) {
            button.disabled = false;
            button.classList.remove('is-loading');
        }
    }
};

const enforceRequiredNotifications = async () => {
    if (notificationRequirementBusy) return;

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        showNotificationSetupError(new Error('unsupported'));
        updatePushButtons(false, 'Browser ini belum mendukung push notification.');
        return;
    }

    if (Notification.permission !== 'granted') {
        updatePushButtons(false, 'Izin notifikasi wajib diberikan untuk menggunakan E-Pasien.');
        showNotificationPermissionRequired();
        return;
    }

    if (rememberedPushOwner() === userId) {
        updatePushButtons(true, 'Notifikasi dan suara wajib aktif untuk menggunakan E-Pasien.');
        hideNotificationGate();
        return;
    }

    notificationRequirementBusy = true;
    try {
        const registration = await pushRegistration();
        if (!registration) throw new Error('unsupported');

        const subscription = await registration.pushManager.getSubscription();
        if (subscription) {
            if (rememberedPushOwner() !== userId) await persistPushSubscription(subscription);
            updatePushButtons(true, 'Notifikasi dan suara wajib aktif untuk menggunakan E-Pasien.');
            hideNotificationGate();
            return;
        }

        showNotificationGate({
            state: 'loading',
            title: 'Menyiapkan notifikasi',
            message: 'Izin sudah aktif. E-Pasien sedang mendaftarkan perangkat Anda.',
            note: 'Proses ini hanya diperlukan ketika subscription belum tersedia.',
            label: 'Mohon Tunggu...',
            busy: true,
        });
        await ensurePushSubscription();
        hideNotificationGate();
    } catch (error) {
        showNotificationSetupError(error);
    } finally {
        notificationRequirementBusy = false;
        const button = notificationGateElements().button;
        if (button && !notificationGate.hidden) {
            button.disabled = false;
            button.classList.remove('is-loading');
        }
    }
};

const togglePush = () => activateRequiredNotifications();

const recheckRequiredNotifications = () => {
    const gateVisible = notificationGate && !notificationGate.hidden;
    const notificationUnavailable = !('Notification' in window) || Notification.permission !== 'granted';
    if (gateVisible || notificationUnavailable) enforceRequiredNotifications();
};

const subscribeToRealtime = async () => {
    const echo = window.Echo || await window.initializeEpasienRealtime?.();
    if (!echo) return;

    echo.private(`App.Models.User.${userId}`).notification((notification) => {
        showLiveToast(notification);
        playNotificationSound(notificationSoundKey(notification));
        forgetCachedNotifications();
        notificationsLoaded = false;
        setBadge(unreadCount + 1);

        if (document.querySelector('.ep-notification-menu')?.classList.contains('show')) {
            loadNotifications({ force: true });
        }
    });
};

const initializeNotificationCenter = () => {
    const cached = cachedNotifications();
    if (cached) setBadge(cached.unread_count || 0);

    document.addEventListener('pointerdown', prepareNotificationSound, { once: true, capture: true });
    document.addEventListener('keydown', prepareNotificationSound, { once: true, capture: true });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data?.type === 'EPASIEN_NOTIFICATION_SOUND_STATUS') {
                event.ports[0]?.postMessage({
                    ready: notificationSoundUnlocked && document.visibilityState === 'visible',
                });
                return;
            }

            if (event.data?.type === 'EPASIEN_PLAY_NOTIFICATION_SOUND') {
                playNotificationSound(event.data.key || 'push-notification', event.data.sound_url);
            }
        });
    }

    document.querySelector('.ep-notification-nav')?.addEventListener('shown.bs.dropdown', () => {
        const cached = cachedNotifications();
        if (cached && !notificationsLoaded) renderNotifications(cached);
        loadNotifications();
    });
    enforceRequiredNotifications();
    subscribeToRealtime();
    document.querySelectorAll('[data-push-toggle]').forEach((button) => button.addEventListener('click', togglePush));
    document.querySelector('[data-notification-read-all]')?.addEventListener('click', async () => {
        try {
            await request(readAllUrl, { method: 'PATCH', body: '{}' });
            forgetCachedNotifications();
            notificationsLoaded = false;
            await loadNotifications({ force: true });
        } catch (_) {}
    });

    window.addEventListener('focus', recheckRequiredNotifications);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') recheckRequiredNotifications();
    });
};

if (userId) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeNotificationCenter, { once: true });
    } else {
        initializeNotificationCenter();
    }
}
