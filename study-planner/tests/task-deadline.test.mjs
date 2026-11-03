import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { stripTypeScriptTypes } from 'node:module';

const source = readFileSync(new URL('../resources/js/services/taskDeadline.ts', import.meta.url), 'utf8');
const { localDeadline, deadlineInstant } = await import(`data:text/javascript;base64,${Buffer.from(stripTypeScriptTypes(source)).toString('base64')}`);

test('Manila local deadline preserves its instant through editing', () => {
  process.env.TZ = 'Asia/Manila';
  assert.equal(deadlineInstant('2026-11-03T11:00'), '2026-11-03T03:00:00.000Z');
  assert.equal(localDeadline('2026-11-03T03:00:00Z'), '2026-11-03T11:00');
});

test('daylight saving offsets follow the deadline date', () => {
  process.env.TZ = 'America/New_York';
  assert.equal(deadlineInstant('2026-07-03T11:00'), '2026-07-03T15:00:00.000Z');
  assert.equal(deadlineInstant('2026-12-03T11:00'), '2026-12-03T16:00:00.000Z');
  assert.throws(() => deadlineInstant('2026-03-08T02:30'));
  assert.throws(() => deadlineInstant('invalid'));
});
