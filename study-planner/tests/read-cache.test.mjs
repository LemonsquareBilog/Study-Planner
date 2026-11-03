import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { stripTypeScriptTypes } from 'node:module';

// Execute the same small TypeScript module shipped to the app.
const source = readFileSync(new URL('../resources/js/services/readCache.ts', import.meta.url), 'utf8');
const compiled = stripTypeScriptTypes(source);
const { ReadCache, readCacheKey } = await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`);

test('shares concurrent reads and returns independent data to each view', async () => {
  const cache = new ReadCache();
  let calls = 0;
  const load = async () => { calls++; return { tasks: [{ title: 'Original' }] }; };
  const [first, second] = await Promise.all([cache.get('tasks', load), cache.get('tasks', load)]);
  first.tasks[0].title = 'Edited';
  assert.equal(second.tasks[0].title, 'Original');
  assert.equal((await cache.get('tasks', load)).tasks[0].title, 'Original');
  assert.equal(calls, 1);
});

test('expires data and respects a fixed memory limit', async () => {
  let now = 0;
  let calls = 0;
  const cache = new ReadCache(30, 2, () => now);
  const load = async () => ++calls;
  assert.equal(await cache.get('one', load), 1);
  now = 30;
  assert.equal(await cache.get('one', load), 2);
  await cache.get('two', load);
  await cache.get('three', load);
  assert.equal(await cache.get('one', load), 5);
});

test('invalidation cannot be undone by a late response from the old session', async () => {
  const cache = new ReadCache();
  let release;
  const old = cache.get('dashboard', () => new Promise(resolve => { release = resolve; }));
  await Promise.resolve();
  cache.clear();
  await cache.get('dashboard', async () => ({ user: 'new' }));
  release({ user: 'old' });
  await old;
  assert.deepEqual(await cache.get('dashboard', async () => assert.fail('New response should be cached')), { user: 'new' });
});

test('failed reads are retried rather than cached', async () => {
  const cache = new ReadCache();
  await assert.rejects(cache.get('dashboard', async () => { throw new Error('Offline'); }), /Offline/);
  assert.equal(await cache.get('dashboard', async () => 'Recovered'), 'Recovered');
});

test('filter order does not duplicate requests, but pages and filters stay separate', () => {
  assert.equal(readCacheKey('/tasks', { page: 1, status: 'pending' }), readCacheKey('/tasks', { status: 'pending', page: 1, unused: undefined }));
  assert.notEqual(readCacheKey('/tasks', { page: 1 }), readCacheKey('/tasks', { page: 2 }));
  assert.notEqual(readCacheKey('/tasks', { status: 'pending' }), readCacheKey('/tasks', { status: 'completed' }));
});
