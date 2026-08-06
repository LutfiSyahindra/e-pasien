var NOTIFICATION_SOUND_URL = '/landing/assets/sound/notif.mp3';

self.addEventListener('install', function (event) {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

var notificationSoundClient = function (windowClients) {
    var visibleClients = windowClients.filter(function (client) {
        return client.visibilityState === 'visible' || client.focused;
    });

    return Promise.all(visibleClients.map(function (client) {
        return new Promise(function (resolve) {
            var channel = new MessageChannel();
            var timeout = setTimeout(function () { resolve(null); }, 200);

            channel.port1.onmessage = function (event) {
                clearTimeout(timeout);
                resolve(event.data?.ready ? client : null);
            };
            client.postMessage({ type: 'EPASIEN_NOTIFICATION_SOUND_STATUS' }, [channel.port2]);
        });
    })).then(function (readyClients) {
        return readyClients.find(Boolean);
    });
};

self.addEventListener('push', function (event) {
    if (!event.data) return;

    event.waitUntil((async function () {
        var payload = event.data.json();
        var windowClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });
        var foregroundClient = await notificationSoundClient(windowClients);
        var options = {
            body: payload.body || '',
            icon: payload.icon || '/epasien/assets/images/pwa-icon-192.png',
            badge: payload.badge || '/epasien/assets/images/favicon-32x32.png',
            image: payload.image,
            tag: payload.tag || 'epasien-notification',
            renotify: Boolean(payload.renotify),
            vibrate: payload.vibrate || [180, 80, 180],
            actions: payload.actions || [],
            data: payload.data || {},
        };

        if (foregroundClient) {
            options.silent = true;
            delete options.vibrate;
            foregroundClient.postMessage({
                type: 'EPASIEN_PLAY_NOTIFICATION_SOUND',
                key: payload.tag || options.data.url || 'push-notification',
                sound_url: NOTIFICATION_SOUND_URL,
            });
        }

        return self.registration.showNotification(payload.title || 'E-Pasien', options);
    })());
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var data = event.notification.data || {};
    var fallback = data.kind === 'patient_service_message' && data.conversation_id
        ? '/e-pasien/menu/pasien-service?conversation=' + encodeURIComponent(data.conversation_id)
        : '/e-pasien/menu/promo-sehat';
    var target;

    try {
        target = new URL(data.url || fallback, self.location.origin);
        if (target.origin !== self.location.origin) target = new URL(fallback, self.location.origin);
    } catch (_) {
        target = new URL(fallback, self.location.origin);
    }

    event.waitUntil((async function () {
        var windows = await clients.matchAll({ type: 'window', includeUncontrolled: true });
        var targetClient = windows.find(function (client) {
            return client.url === target.href;
        }) || windows.find(function (client) {
            return client.visibilityState === 'visible' || client.focused;
        }) || windows[0];

        if (targetClient) {
            if ('navigate' in targetClient && targetClient.url !== target.href) {
                try {
                    targetClient = await targetClient.navigate(target.href) || targetClient;
                } catch (_) {
                    targetClient = null;
                }
            }

            if (targetClient && 'focus' in targetClient) return targetClient.focus();
        }

        return clients.openWindow ? clients.openWindow(target.href) : undefined;
    })());
});
