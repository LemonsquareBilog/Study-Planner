# Regression execution

From `study-planner`, source `/workspace/.study-planner-tools/activate.sh` in this prepared cloud environment.

```sh
php artisan test --compact
npm run test:client
npm run test:mobile-config
npm run build:web
npm run test:qa
```

PHPUnit needs a development/testing APP_KEY. In a shell with no root .env, supply an ephemeral random APP_KEY for this command only; do not log its value. Tests use in-memory SQLite. Browser tests use the isolated .env.development database and intercepted API fixtures; run after building assets with no stale public/hot file. The QA runner defaults to `/usr/bin/chromium`; use CHROMIUM_PATH for another installed Chromium. No dependency download or external login is needed.

| Suite | Cases | Result |
| --- | --- | --- |
| Full PHPUnit | 42 tests, 344 assertions | Pass |
| Client recovery | 22 | Pass |
| Mobile configuration | 15 | Pass |
| Chromium QA | 19 | Pass |

New regressions cover 45 task/history rows, three-page traversal without duplicates, tied ordering, own/foreign records, known aggregate scores, empty search, invalid page/sort/date range, ebook update/filter contract, malformed/versioned/legacy sessions, cancellation/404/network errors, Users populated/empty/error/edit, Settings failed-save recovery, on-demand quiz history and retry/retake identity.

Transaction rollback after a simulated answer-write failure also passed. A separate two-process SQLite service experiment passed: simultaneous retries returned the same attempt, with one persisted attempt/answer. Migration up/down/up preserved populated history in PHPUnit; an independent development backup-copy rehearsal also passed. Physical devices, production-engine concurrency and deployed acceptance remain not run.

Dashboard follow-up covers empty and populated summaries, ownership and deleted records, query counts, recommendation thresholds, repeat visits, failed refresh/retry, same-user re-login, account switching and delayed prior-session responses.

The schema compatibility follow-up verified pre-migration and incomplete-index quiz submission behavior, safe rejection of retry tokens when protection is unavailable, and the user-facing retry warning. PostgreSQL 17.11 focused suite: 14 tests and 144 assertions; local synthetic data only.
