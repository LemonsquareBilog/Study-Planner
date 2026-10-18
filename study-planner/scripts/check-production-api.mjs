import { randomUUID } from 'node:crypto';
import { validateMobileApiUrl } from './mobile-config.mjs';

export async function checkProductionApi(value, fetchRequest = fetch) {
    const base = validateMobileApiUrl(value);
    const request = async (url, options = {}) => {
        const response = await fetchRequest(url, {
            ...options, redirect: 'manual', signal: AbortSignal.timeout(15000),
        });
        if (response.status >= 300 && response.status < 400) {
            throw new Error('The API redirects instead of serving the application. Check the public domain and deployment protection.');
        }
        return response;
    };
    const health = await request(base.slice(0, -4) + '/up');
    if (health.status !== 200) throw new Error(`Backend health check returned HTTP ${health.status}.`);

    const unauthenticated = await request(base + '/auth/me', { headers: { Accept: 'application/json' } });
    if (unauthenticated.status !== 401 || !unauthenticated.headers.get('content-type')?.includes('application/json')) {
        throw new Error('The profile endpoint did not return the expected JSON 401. Check API routing and deployment protection.');
    }

    for (const origin of ['https://localhost', 'capacitor://localhost']) {
        const preflight = await request(base + '/auth/login', {
            method: 'OPTIONS', headers: {
                Origin: origin, 'Access-Control-Request-Method': 'POST',
                'Access-Control-Request-Headers': 'authorization,content-type',
            },
        });
        const allowedHeaders = preflight.headers.get('access-control-allow-headers')?.toLowerCase() || '';
        const allowedMethods = preflight.headers.get('access-control-allow-methods')?.toLowerCase() || '';
        if (preflight.status !== 204 || preflight.headers.get('access-control-allow-origin') !== origin ||
            !(allowedHeaders === '*' || (allowedHeaders.includes('authorization') && allowedHeaders.includes('content-type'))) ||
            !(allowedMethods === '*' || allowedMethods.includes('post'))) {
            throw new Error(`Native CORS preflight failed for ${origin}.`);
        }
    }

    // One deliberately nonexistent account tests the database-backed login route.
    // No real user credentials are required, persisted, or printed.
    const invalidLogin = await request(base + '/auth/login', {
        method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: `${randomUUID()}@connectivity.invalid`, password: randomUUID() }),
    });
    if (invalidLogin.status === 429) throw new Error('Authentication is rate limited. Wait a minute before retrying.');
    if (invalidLogin.status !== 422 || !invalidLogin.headers.get('content-type')?.includes('application/json')) {
        throw new Error(`Database-backed login check returned HTTP ${invalidLogin.status}; expected a JSON credential rejection.`);
    }
    const rejected = await invalidLogin.json();
    if (!rejected.errors?.email || rejected.data?.token) throw new Error('The login endpoint did not return the expected credential rejection.');
    return { base, checks: ['HTTPS configuration', 'backend health', 'protected API routing', 'Android CORS', 'iOS CORS', 'database-backed credential rejection'] };
}

if (import.meta.url === (await import('node:url')).pathToFileURL(process.argv[1] || '').href) {
    try {
        const result = await checkProductionApi(process.argv[2] || process.env.VITE_API_BASE_URL);
        console.log(`Production connectivity checks passed for ${result.base}.`);
        console.log('Still required: valid-account login, refresh/logout, signed APK, and physical-device testing.');
    } catch (error) {
        console.error(`Production connectivity check failed: ${error.message}`);
        process.exitCode = 1;
    }
}
