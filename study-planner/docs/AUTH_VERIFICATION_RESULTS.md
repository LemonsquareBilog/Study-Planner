# Executed verification — 2026-10-18

These results cover `study-planner` (Laravel/Vue/Capacitor), not the separate `PROJECT1` application.

| Check actually executed | Final result | Evidence / limits |
|---|---|---|
| Complete PHP unit/feature suite | **23 passed, 147 assertions** | `.verification/phpunit-final.txt`, `.verification/phpunit-final.xml`; isolated in-memory SQLite, breach-service HTTP responses mocked |
| Existing development accounts | **4/4 passed** | Real local API login, bearer-authenticated profile, logout; existing hashes verified before changes; passwords not reset |
| Development persistence | **Passed** | Stop/restart of local PHP server followed by existing-account API login; original SQLite still has five users and no verification fixture; isolated copy has six |
| Real-backend browser authentication and password UI | **10/10 passed** | `.verification/browser-final.txt`; login/reload/logout, invalid credentials, offline restoration, renewal, desktop/mobile rendering, unreachable server without demo fallback, native-session simulation, eye toggles and keyboard interaction, strength feedback and server-side weak-password rejection |
| Preview feature regression suite | **23/23 passed** | `.verification/browser-preview-final.txt`; student/admin navigation, role routing, tasks, subjects, study-plan rendering, quizzes, resources, progress, notifications, responsive navigation and console checks; uses explicit demo preview, not production data |
| Mobile endpoint validation | **15/15 passed** | `npm.cmd run test:mobile-config` |
| Production API checker unit tests | **4/4 passed** | `npm.cmd run test:production-api`; these use mocked HTTP responses |
| Live existing HTTPS deployment | **Passed limited connectivity checks** | `node scripts/check-production-api.mjs https://study-planner-gamma-seven.vercel.app/api`; health, unauthorized profile JSON 401, both native CORS origins, database-backed rejection for a randomly generated nonexistent account; no real production credentials used |
| Website production frontend build | **Passed** | `.verification/web-build.txt` |
| Mobile production frontend build | **Passed** | `.verification/mobile-release-build.txt`; independently hosted HTTPS endpoint embedded |
| Explicit demo preview build | **Passed** | `.verification/preview-build.txt`; built separately from release assets |
| Capacitor Android synchronization | **Passed** | `npx.cmd cap sync android`; final frontend and six native plugins copied/updated |
| Known demo credential / old LAN URL asset scan | **Passed** | No `StudentPass123`, `AdminPassword123` or `192.168.0.103` in web, mobile and Android JavaScript assets; targeted check, not a complete secret-scanning certification |
| PHP dependency security | **No advisories reported after compatible update** | Composer updated CommonMark to 2.10.4, Symfony YAML to PHP-8.3-compatible 7.4.21, and its PHP 8.0 polyfill; Composer's resulting audit reported no advisories |
| npm dependency security | **Critical/high reports fixed; 3 moderate reports remain** | `npm audit fix --ignore-scripts`; remaining chain is Capacitor CLI → xcode → uuid; forced CLI downgrade deferred |
| Android debug APK compilation | **Blocked** | Gradle `assembleDebug --offline` could not find required JDK 21 for `capacitor-local-notifications`; no completed APK claimed |
| Production valid-account login / session renewal | **Not executed** | Requires authorized real production account and deployment of these changes |
| Physical phone Wi-Fi/cellular/laptop-disconnected login | **Not executed** | Requires signed/installable application and phone; frontend simulation is not physical-device evidence |
| iOS build and secure storage | **Not executed / incomplete** | No iOS native project or Keychain implementation in this workspace |
| Production database isolation / backup restore / infrastructure audit | **Not verified** | Seed guards tested; provider access, independent secrets, least privilege, backup restoration and at-rest encryption require infrastructure evidence |

The initial broad browser invocation incorrectly mixed demo-only suites with the real API server; it was interrupted and rerun in the appropriate separate environments. Preview regression initially found two existing defects: accessible labels on select wrappers, and missing Study Plan helpers leaving loading/rendering broken. Both were repaired and the complete preview suite passed. A legacy-bcrypt test initially tried inserting an old hash through the new Argon2id model cast; its fixture now inserts the legacy persisted hash directly, matching the migration scenario. The final backend suite passed.

The PHP CLI in the shell did not load php.ini. Tests used installed PHP 8.3.33 with project-local `.verification/php.ini` enabling installed extensions. Builds used available Node 24.13.1; the project declares Node 22.x, so production CI should use Node 22 and repeat its release build. Frontend builds retain non-fatal existing chunk-size and notification import warnings.

No source was deployed to production during this work. The limited live check describes the existing deployment, not a deployment of the new cookie/password/security changes. Deploy backend and website together, preserve the production key and database, and follow [the implementation and release instructions](AUTH_SECURITY_IMPLEMENTATION.md).
