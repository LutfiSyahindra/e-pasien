let realtimePromise;

window.initializeEpasienRealtime = () => {
    if (!import.meta.env.VITE_REVERB_APP_KEY) return Promise.resolve(null);
    if (window.Echo) return Promise.resolve(window.Echo);
    if (realtimePromise) return realtimePromise;

    realtimePromise = Promise.all([
        import('laravel-echo'),
        import('pusher-js'),
    ]).then(([echoModule, pusherModule]) => {
        const Echo = echoModule.default;
        window.Pusher = pusherModule.default;
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
            wsPort: import.meta.env.VITE_REVERB_PORT || 80,
            wssPort: import.meta.env.VITE_REVERB_PORT || 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        return window.Echo;
    }).catch(() => null);

    return realtimePromise;
};
