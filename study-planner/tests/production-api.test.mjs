import test from 'node:test';
import assert from 'node:assert/strict';
import { checkProductionApi } from '../scripts/check-production-api.mjs';

function backend({ redirect = false, databaseFailure = false, wrongCors = false } = {}) {
    return async (url, options) => {
        if (redirect) return new Response('', { status: 307 });
        if (url.endsWith('/up')) return new Response('healthy');
        if (url.endsWith('/auth/me')) return Response.json({ message: 'Unauthenticated' }, { status: 401 });
        if (options.method === 'OPTIONS') return new Response(null, { status: 204, headers: {
            'access-control-allow-origin': wrongCors ? 'https://untrusted.invalid' : options.headers.Origin,
            'access-control-allow-methods': 'POST', 'access-control-allow-headers': 'authorization,content-type',
        } });
        if (databaseFailure) return Response.json({ message: 'Server error' }, { status: 500 });
        return Response.json({ errors: { email: ['Invalid credentials'] } }, { status: 422 });
    };
}
const endpoint = 'https://study-planner.vercel.app/api';
test('accepts working API routing, both native origins, and database login rejection', async () => {
    const result = await checkProductionApi(endpoint, backend());
    assert.equal(result.checks.length, 6);
});
test('does not treat a hosting-login redirect as a healthy backend', async () => {
    await assert.rejects(checkProductionApi(endpoint, backend({ redirect: true })), /redirects/);
});
test('detects database-backed authentication failure despite healthy process endpoint', async () => {
    await assert.rejects(checkProductionApi(endpoint, backend({ databaseFailure: true })), /HTTP 500/);
});
test('detects incorrect native CORS configuration', async () => {
    await assert.rejects(checkProductionApi(endpoint, backend({ wrongCors: true })), /CORS/);
});
