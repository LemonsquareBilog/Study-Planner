# Study Planner Task, Notification, and Performance Audit

## Executive summary
Implemented database-filtered task expiration, preserved history, UTC deadline conversion, persistent task-linked reminder schedules, cancellation/rescheduling, a protected scheduler endpoint, task notification links, and a stationary Tasks dialog header. Changes are local and require migration before deployment. Production performance and native-device verification remain blocked/unverified; this is not a claim of full completion.

## Architecture
Laravel 13, Vue 3/Pinia/Vue Router, Vite, Capacitor. Checked-in Vercel configuration uses vercel-php, PostgreSQL, database cache and synchronous queues. Deployed configuration and database region are not verified. Existing uncommitted caching/performance work was preserved.

## Verified root causes
- Tasks endpoint previously had no expiration predicate.
- datetime-local inputs displayed UTC text and submitted offset-free timestamps.
- Old reminders deduplicated using title substrings rather than task identity.
- Task modal scrolled its header with the form.
- Study Plan loaded all pending tasks merely to check whether any existed.
No measured evidence establishes Vercel itself as a bottleneck.

## Expiration and history
GET tasks defaults to SQL deadline > now in UTC. view=history selects deadline <= now; view=all supports linked task lookup. Queries remain user-scoped and paginated. History is derived from deadline, not a completion mutation or deletion. Completed and incomplete records retain their status. Rescheduling into the future intentionally reactivates a task. UI has an expired-history selector and refreshes visible tasks every 60 seconds. Existing cache can retain responses up to 30 seconds; exact-boundary visual disappearance is not guaranteed. Dashboard upcoming tasks and Study Plan exclude expired records; Progress retains historical totals. Recommendations do not directly query tasks in the inspected service.

## Notifications
Additive migration adds nullable task foreign key, unique event key, and cancellation timestamp. Creation saves task and schedules atomically. Updates/deletion/status changes lock the task and resynchronize within a transaction. Defaults: creation, 24h, 1h, deadline, and one second after deadline. Past reminder windows are skipped. Completed tasks skip advance reminders. Future cancelled events are retained for audit. Configuration: config/task_reminders.php. The unique key combines task/deadline/trigger; true simultaneous PostgreSQL race testing remains outstanding.

Notification queries and dashboard unread counts exclude cancelled/future events. Notifications link to owner-scoped task lookup including history. SQL time filtering makes persisted in-app events visible independently of browser timers. reminders:tasks marks due pending events sent with a conditional bulk update; repeated runs do not redeliver sent records. Laravel schedule invokes it every minute. Protected GET /api/internal/task-reminders requires a >=32-character CRON_SECRET bearer token, rate limiting, and no-store response. No live scheduler was configured.

## Modal implementation
Tasks create/edit modal uses a non-scrolling header and separate form scroll area, 44px close target, visible keyboard focus, dynamic viewport height and bottom safe-area padding. Unsaved changes require discard confirmation; closing during submission is blocked. Browser checks passed at 1440px and 390px. Other application dialogs have not received a complete consistency audit.

## Localhost versus production / metrics
Production target discovered in .env.mobile. Connectivity script failed with fetch failed under this restricted environment. Authenticated production TTFB, LCP, INP, API/DB latency, query plans, region latency and cache hits remain unmeasured. No before/after production improvement claimed.

Local build: web build passed, approximately 0.97 seconds reported by Vite in one run; mobile build passed, approximately 0.93 seconds. These are build times, not navigation metrics. Registration JS chunk approximately 1,669 kB minified / 851 kB gzip; mixed static/dynamic Capacitor notification import warning remains. These warnings do not establish student navigation causality.

## Database/API optimization
Replaced Study Plan full pending-task load with exists(). Existing tasks(user_id, deadline) index retained; no speculative performance indexes added. Notification identity uniqueness is a correctness constraint. No destructive migration or production database change performed.

## Files changed in this work
- app/Http/Controllers/Student/{TaskController,NotificationController,DashboardController}.php
- app/Services/{TaskNotificationService,StudyPlanService}.php
- app/Models/Notification.php
- app/Console/Commands/DeliverTaskNotifications.php
- config/task_reminders.php; routes/api.php; routes/console.php
- database/migrations/2026_11_03_004810_add_task_identity_to_notifications.php
- resources/js/services/taskDeadline.ts
- resources/js/views/student/{TasksView,NotificationsView}.vue
- tests/Feature/{TaskExpirationTest,TaskNotificationTest}.php
- tests/task-deadline.test.mjs; tests/e2e/task-modal.spec.ts
- generated build assets; this report. Boost setup also generated local guidelines/configuration.

## Tests and actual results
- Backend expiration, notification and existing student feature suite: 9 passed, 87 assertions.
- Timezone and read-cache Node tests: 7 passed.
- Focused student navigation Playwright suite: 4 passed (5.2 seconds total), mocked API data. Covers cache reuse, task loading, quiz loading and account-switch isolation.
- Task modal desktop/mobile viewport Playwright: 2 passed (4.1 seconds total), mocked API data.
- Full navigation run exposed 3 failures: admin subjects, admin resources, subject-detail progressive loading. Unresolved; not hidden by the focused rerun.
- Web/mobile bundles built successfully. No native Android/iOS device execution or production end-to-end verification.
- PHP formatter applied to changed PHP files without broadly rewriting other dirty files.

## Security
Student ownership checks retained; tests cover another student reading history and notifications and attempting to mark another student's notification read. Scheduler fails closed without configured secret. No secrets printed or production writes performed. Existing Composer advisory still needs package-specific audit. Notification scheduling is transactional on task API mutations; external writers must call the same scheduling service.

## Deployment and rollback
1. Review local changes and back up the target database. Confirm separate development/production databases.
2. Test the additive migration in staging, then run the normal production migration process before serving code that queries the new columns.
3. Run php artisan reminders:tasks --backfill once to schedule existing future tasks; it uses bounded chunks and row locks.
4. Configure a strong server-only CRON_SECRET. Use a trusted external minute scheduler calling the protected endpoint, or Vercel Pro/Enterprise cron with schedule * * * * *. Do not add minute cron to Hobby: it only permits daily runs. On a persistent Laravel host, invoke schedule:run every minute instead.
5. Build web/mobile, verify account isolation, real deadline transitions and notification links, then deploy through the established pipeline. No deployment was performed here.
6. Rollback: stop scheduler; deploy previous application version before dropping the added columns. Preserve/export notification event metadata first. Migration down removes only the added FK/unique constraint/columns; dropping metadata loses scheduling history, so prefer leaving the additive columns during an application rollback. Re-enable the prior reminder schedule only after checking duplication risk.

Vercel plan limits checked against https://vercel.com/docs/cron-jobs/usage-and-pricing on 2026-11-03.

## Remaining work
Production metrics/logs/query plans and scheduler activation; real native-device/web synchronization; three browser failures; all-dialog accessibility audit/focus trapping; exact-deadline cache invalidation; notification live polling; DST fall-back ambiguity UI; legacy offset-free deadline interpretation; full API concurrency testing; broader rollback verification. No production acceptance targets have been demonstrated.

## Local task creation repair
The local development.sqlite still had the notification migration pending; task creation rolled back when scheduling queried missing notification columns. Backed up database/development.sqlite to a timestamped development-before-notification-fix file and applied only 2026_11_03_004810_add_task_identity_to_notifications successfully. Production was untouched. Added a visible generic save-error alert preserving form input rather than silently swallowing server failures. Task creation/study-plan and notification tests passed: 3 tests, 32 assertions. Web build passed. Local migration is now applied, superseding earlier pending-local-migration statements; production deployment still requires its own migration.

## Upcoming deadline notes
Notifications now includes the next five unfinished task deadlines in ascending order, independent of unread-notification filtering. Each note shows title, local due date/time, timezone, remaining time and a task link. The backend uses a bounded user-scoped query excluding completed, cancelled, deleted and expired tasks. No migration needed for this addition. Upcoming-deadline and notification tests passed: 3 tests, 20 assertions; web build passed.
