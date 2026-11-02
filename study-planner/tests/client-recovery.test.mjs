import test from 'node:test';
import assert from 'node:assert/strict';
import { decodeSession } from '../resources/js/services/sessionDecoder.ts';
import { classifyHttpError, isBackendUnavailable } from '../resources/js/services/httpError.ts';
import { paginateItems, normalizePage } from '../resources/js/services/pagination.ts';

for (const value of [null, '', '{broken', '{}', '{"token":null}', '{"token":3}', '{"token":"ok","refreshToken":[]}', '{"version":2,"token":"ok"}', '[]', '"token"', 'white space']) {
  test(`session recovery rejects invalid storage ${value}`, () => assert.equal(decodeSession(value), null));
}
test('session decoder preserves legacy and versioned tokens', () => {
  assert.deepEqual(decodeSession('1|legacy-token'), { version: 1, token: '1|legacy-token', refreshToken: null });
  assert.deepEqual(decodeSession('{"token":"cookie-session","refreshToken":null}'), { version: 1, token: 'cookie-session', refreshToken: null });
  assert.deepEqual(decodeSession('{"version":1,"token":"access","refreshToken":"refresh"}'), { version: 1, token: 'access', refreshToken: 'refresh' });
});
for (const [error, category, unavailable] of [
  [{ response: { status: 404 } }, 'not-found', false], [{ response: { status: 401 } }, 'authentication', false],
  [{ response: { status: 403 } }, 'authorization', false], [{ response: { status: 422 } }, 'validation', false],
  [{ response: { status: 503 } }, 'server-unavailable', true], [{ request: {}, code: 'ERR_NETWORK' }, 'network', true],
  [{ code: 'ECONNABORTED' }, 'timeout', true], [{ code: 'ERR_CANCELED' }, 'cancelled', false], [new Error('Programming bug'), 'unknown', false],
]) {
  test(`HTTP errors classify as ${category}`, () => {
    assert.equal(classifyHttpError(error), category); assert.equal(isBackendUnavailable(error), unavailable);
  });
}
test('preview and legacy pagination traverses all 45 records without duplicates', () => {
  const items = Array.from({ length: 45 }, (_, id) => ({ id }));
  const pages = [1, 2, 3].map(page => paginateItems(items, page));
  assert.deepEqual(pages.map(page => page.data.length), [20, 20, 5]);
  assert.equal(new Set(pages.flatMap(page => page.data.map(item => item.id))).size, 45);
  assert.equal(pages[2].total, 45);
  assert.equal(normalizePage(pages[1]), pages[1]);
  assert.deepEqual(normalizePage({ data: items }, 2), pages[1]);
});
