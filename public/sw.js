self.addEventListener('push', function (event) {
    if (!event.data) return;
    var payload = event.data.json();
    event.waitUntil(self.registration.showNotification(payload.title || 'E-Pasien', {
        body: payload.body || '',
        icon: payload.icon || '/epasien/assets/images/logo-icon.png',
        badge: payload.badge || '/epasien/assets/images/favicon-32x32.png',
        image: payload.image,
        tag: payload.tag || 'epasien-notification',
        renotify: Boolean(payload.renotify),
        vibrate: payload.vibrate || [180, 80, 180],
        actions: payload.actions || [],
        data: payload.data || {},
    }));
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
