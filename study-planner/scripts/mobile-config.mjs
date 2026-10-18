import { isIP } from 'node:net';

export function validateMobileApiUrl(value, development = false) {
    if (!value) throw new Error('VITE_API_BASE_URL must point to your deployed backend, including /api.');
    let url;
    try { url = new URL(value); } catch { throw new Error('VITE_API_BASE_URL must be an absolute URL.'); }
    const host = url.hostname.toLowerCase();
    if (url.username || url.password || url.search || url.hash || !url.pathname.endsWith('/api')) {
        throw new Error('API URL must end in /api and contain no credentials, query, or fragment.');
    }
    if (development && ['http:', 'https:'].includes(url.protocol)) return url.href.replace(/\/$/, '');
    if (url.protocol !== 'https:' || isIP(host.replace(/^\[|\]$/g, '')) || !host.includes('.') ||
        /(^|\.)(localhost|local|internal|test|example|invalid)$/.test(host) || host.endsWith('.example.com') ||
        /your[_-]|your-domain|your-project/i.test(host)) {
        throw new Error('Production mobile builds require a public HTTPS backend hostname. Local addresses and placeholders are forbidden.');
    }
    return url.href.replace(/\/$/, '');
}
