const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || '';
const csrf = meta('csrf-token');
const userId = meta('epasien-user-id');
const listUrl = meta('epasien-notifications-url');
const readBaseUrl = meta('epasien-notifications-read-url');
const readAllUrl = meta('epasien-notifications-read-all-url');
const pushConfigUrl = meta('epasien-push-config-url');
const pushSubscriptionUrl = meta('epasien-push-subscription-url');

const request = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) },
        ...options,
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
};

const setBadge = (count) => {
    const badge = document.querySelector('[data-notification-badge]');
    if (!badge) return;
    badge.textContent = count > 99 ? '99+' : String(count);
    badge.hidden = count < 1;
};

const notificationItem = (item) => {
    const button = document.createElement('button');
    const data = item.data || {};
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

const loadNotifications = async () => {
    if (!listUrl) return;
    try { renderNotifications(await request(listUrl)); } catch (_) {
        const list = document.querySelector('[data-notification-list]');
        if (list) list.textContent = 'Notifikasi belum dapat dimuat.';
    }
};

const showLiveToast = (data) => {
    document.querySelector('.ep-live-toast')?.remove();
    const toast = document.createElement('div');
    toast.className = 'ep-live-toast';
    if (data.image_url) {
        const image = document.createElement('img'); image.src = data.image_url; image.alt = ''; toast.append(image);
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
        button.disabled = pushBusy;
        const label = button.querySelector('span');
        if (label) label.textContent = active ? 'Notifikasi perangkat aktif' : 'Aktifkan notifikasi perangkat';
        const icon = button.querySelector('i');
        if (icon) icon.className = active ? 'bi bi-bell-check-fill' : 'bi bi-bell';
    });
    if (message) document.querySelectorAll('[data-push-hint]').forEach((hint) => { hint.textContent = message; });
};

const pushRegistration = async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return null;
    return navigator.serviceWorker.register('/sw.js', { scope: '/' });
};

const syncPushState = async () => {
    const registration = await pushRegistration();
    if (!registration) {
        updatePushButtons(false, 'Browser ini belum mendukung push notification.');
        return;
    }
    const subscription = await registration.pushManager.getSubscription();
    updatePushButtons(Boolean(subscription), subscription ? 'Kabar terbaru akan masuk ke perangkat ini.' : undefined);
};

const togglePush = async () => {
    if (pushBusy) return;
    pushBusy = true; updatePushButtons(false);
    try {
        const registration = await pushRegistration();
        if (!registration) throw new Error('unsupported');
        const current = await registration.pushManager.getSubscription();
        if (current) {
            await request(pushSubscriptionUrl, { method: 'DELETE', body: JSON.stringify({ endpoint: current.endpoint }) });
            await current.unsubscribe();
            updatePushButtons(false, 'Notifikasi perangkat telah dinonaktifkan.');
            return;
        }
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') throw new Error('denied');
        const config = await request(pushConfigUrl);
        if (!config.enabled || !config.public_key) throw new Error('unconfigured');
        const subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: base64ToUint8(config.public_key) });
        const json = subscription.toJSON();
        await request(pushSubscriptionUrl, {
            method: 'POST',
            body: JSON.stringify({ endpoint: json.endpoint, keys: json.keys, content_encoding: window.PushManager.supportedContentEncodings?.[0] || 'aes128gcm' }),
        });
        updatePushButtons(true, 'Berhasil! Kabar terbaru akan masuk ke perangkat ini.');
    } catch (error) {
        const messages = { denied: 'Izin notifikasi ditolak. Aktifkan melalui pengaturan browser.', unsupported: 'Browser ini belum mendukung push notification.', unconfigured: 'Push notification belum dikonfigurasi pada server.' };
        updatePushButtons(false, messages[error.message] || 'Notifikasi perangkat belum dapat diaktifkan.');
    } finally {
        pushBusy = false;
        let active = false;
        try {
            const registration = await pushRegistration();
            active = Boolean(registration && await registration.pushManager.getSubscription());
        } catch (_) {}
        updatePushButtons(active);
    }
};

if (userId) {
    document.addEventListener('DOMContentLoaded', () => {
        loadNotifications();
        syncPushState().catch(() => {});
        document.querySelectorAll('[data-push-toggle]').forEach((button) => button.addEventListener('click', togglePush));
        document.querySelector('[data-notification-read-all]')?.addEventListener('click', async () => {
            try { await request(readAllUrl, { method: 'PATCH', body: '{}' }); await loadNotifications(); } catch (_) {}
        });

        if (window.Echo) {
            window.Echo.private(`App.Models.User.${userId}`).notification((notification) => {
                showLiveToast(notification);
                loadNotifications();
            });
        }
    });
}
