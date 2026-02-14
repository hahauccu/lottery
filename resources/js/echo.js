import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
const useTLS = scheme === 'https';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? (useTLS ? 443 : 80)),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: useTLS,
    // pusher-js transport 名稱應為 "ws"；TLS 由 forceTLS 決定是否升級為 wss。
    enabledTransports: ['ws'],
});
