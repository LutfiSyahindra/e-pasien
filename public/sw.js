var NOTIFICATION_SOUND_URL = '/landing/assets/sound/notif.mp3';

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
            icon: payload.icon || '/epasien/assets/images/logo-icon.png',
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
    var target = event.notification.data && event.notification.data.url ? event.notification.data.url : '/e-pasien/menu/promo-sehat';
    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windows) {
        for (var i = 0; i < windows.length; i++) {
            if ('focus' in windows[i]) { windows[i].navigate(target); return windows[i].focus(); }
        }
        return clients.openWindow ? clients.openWindow(target) : undefined;
    }));
});
