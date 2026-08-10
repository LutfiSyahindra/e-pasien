import 'bootstrap-icons/font/bootstrap-icons.css';
import './bootstrap';

const startAlpine = () => {
    if (!document.querySelector('[x-data]')) return;

    import('alpinejs').then(({ default: Alpine }) => {
        window.Alpine = Alpine;
        Alpine.start();
    });
};

const startNotificationCenter = () => import('./notification-center').catch(() => {});
const scheduleNotificationCenter = () => {
    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(startNotificationCenter, { timeout: 1200 });
        return;
    }

    window.setTimeout(startNotificationCenter, 0);
};

startAlpine();

if (document.querySelector('.ep-premium-navbar')) {
    import('./navbar-mobile').catch(() => {});
}

if (document.querySelector('meta[name="epasien-patient-service-navbar-url"]')) {
    import('./patient-service-navbar').catch(() => {});
}

if (document.querySelector('meta[name="epasien-user-id"]')) {
    if (document.readyState === 'complete') scheduleNotificationCenter();
    else window.addEventListener('load', scheduleNotificationCenter, { once: true });
}
