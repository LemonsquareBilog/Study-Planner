import test from 'node:test';
import assert from 'node:assert/strict';
import { validateMobileApiUrl } from '../scripts/mobile-config.mjs';

test('accepts a public HTTPS API endpoint', () => {
    assert.equal(validateMobileApiUrl('https://study-planner.vercel.app/api'), 'https://study-planner.vercel.app/api');
});
for (const url of ['', '/api', 'http://study-planner.vercel.app/api', 'https://localhost/api',
    'https://127.0.0.1/api', 'https://192.168.0.103/api', 'https://10.0.0.1/api',
    'https://[::1]/api', 'https://backend.local/api', 'https://YOUR_PROJECT.vercel.app/api',
    'https://user:password@study-planner.vercel.app/api', 'https://study-planner.vercel.app',
    'https://study-planner.vercel.app/api?token=secret']) {
    test(`rejects invalid production API URL: ${url}`, () => assert.throws(() => validateMobileApiUrl(url)));
}
test('allows LAN HTTP only in explicitly selected development mode', () => {
    assert.equal(validateMobileApiUrl('http://192.168.0.103:8000/api', true), 'http://192.168.0.103:8000/api');
});
