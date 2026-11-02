# Submission-identity migration and rollback

Migration: `database/migrations/2026_11_03_000001_add_submission_identity_to_quiz_attempts.php`.

Adds two nullable columns and a unique (user_id, submission_id) index. Existing rows need no backfill; old clients may omit identities. When both columns and their unique index are present, new clients receive an authenticated identity from quiz detail, retain it through retry and request a fresh one for retakes. During a transitional release without that schema, quiz submissions remain available through the legacy path but retries are not deduplicated; quiz detail explicitly warns users to check history before retrying. A previously issued identity is rejected if the schema becomes incomplete.

## Local evidence

Backed up development SQLite with its native backup API and opened a restore copy; integrity_check returned ok. Rehearsed migrate / rollback --step=1 / migrate on that copy, checked foreign_key_check and counts of users, subjects, quiz_attempts and quiz_answers. Applied the migration to local development afterward. A separate in-memory populated-history test preserves the attempt and score across down/up. Two simultaneous service retries against disposable file-backed SQLite persisted one attempt.

A later isolated PostgreSQL 17.11 rehearsal ran 11 focused tests (122 assertions), including dashboard aggregates and quiz ownership/idempotency. The submission migration passed up/down/up; two simultaneous retries returned the same attempt and left one attempt and one answer. This was a disposable local Unix-socket database with synthetic data, not the production database or a production-size staging copy. PostgreSQL version, data distribution, permissions, backup and migration lock duration on the deployed system still require verification. The local test runner and logs are retained under `/workspace/.study-planner-tools/pg-review`.

Private local backup: `.verification/development-before-submission.sqlite`. It is an ignored setup artifact; do not commit databases or credentials.

## Staging and production procedure

1. Identify the production engine/version and verify the current migration state. Securely take a backup and demonstrate restoration on that engine.
2. Rehearse on populated staging data; record counts, foreign keys, score totals and current quiz/history responses. Confirm nullable unique semantics and DDL locking expectations.
3. Run `php artisan migrate --force` in staging; repeat integrity, regression and simultaneous retry checks. Do not assume SQLite lock behavior generalizes.
4. On the authorized release, apply the additive migration after backup and rehearsal to activate retry protection. The backend supports the legacy schema while the migration is pending; ship updated web/native clients together. Preserve APP_KEY so issued identities can be decoded.
5. Monitor conflicts, errors and response sizes. Old clients retain array data fields but only receive the first bounded page until upgraded.

Application rollback: restore the prior application release first. Nullable extra columns may safely remain while investigating. Schema rollback removes identity/fingerprint information and its unique constraint, losing deduplication history; do it only after dependent code is stopped, a new restorable backup exists and the operator accepts this loss. Restore a database backup only under a coordinated recovery plan to avoid losing subsequent writes.
