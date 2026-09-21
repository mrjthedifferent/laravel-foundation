import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbConfig = typeof window !== 'undefined' && window.__REVERB__;
const key = reverbConfig?.key ?? import.meta.env.VITE_REVERB_APP_KEY;
const host = reverbConfig?.host ?? import.meta.env.VITE_REVERB_HOST;
const port = reverbConfig?.port ?? import.meta.env.VITE_REVERB_PORT ?? 443;
const scheme = reverbConfig?.scheme ?? import.meta.env.VITE_REVERB_SCHEME ?? 'https';

const hasValidKey = key && String(key).trim();
if (hasValidKey && host) {
    const forceTLS = scheme === 'https';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port ?? 80,
        wssPort: port ?? 443,
        forceTLS,
        enabledTransports: forceTLS ? ['wss'] : ['ws'],
    });
}
