# Mobile login investigation and release guide

Status: code fixes implemented and local automated checks passed. Remote production login and a signed mobile release are not yet verified or deployed.

## Confirmed causes and findings

- The Android/mobile configuration used `http://192.168.0.103:8000/api`. The same URL is embedded in both the existing `www` bundle and Android's packaged JavaScript assets. That is the laptop's private LAN address, and requires the laptop backend to be running. It cannot serve users on mobile data or outside that LAN.
- The Android packaged configuration permitted cleartext HTTP. Updating source code does not update an already installed APK.
- `resources/js/services/api.ts` contained unresolved Git conflicts in login and registration. `playwright.config.ts` also contained conflicts. New builds could not reliably be produced from this source.
- Native builds with a missing API URL selected a local demonstration account database. The conflicting code also contained a network-error fallback to that database. Those credentials and accounts are separate from Laravel's users. Production authentication now always uses Laravel; demonstration behavior is restricted to the explicit browser `preview` build mode.
- Session initialization became permanently initialized after a network failure, so a saved valid token could not restore a session after connectivity returned.
- Backend password authentication passed automated tests before changes. This does not establish that a particular installed APK, real account, or production database has been tested.
- The backend's local `.env` uses the Docker PostgreSQL hostname `postgres` and a localhost application URL. The repository contains Vercel deployment configuration, but the connected Vercel account lists no Study Planner project. No production database connection was established.

## Implemented behavior

- Production mobile builds require an absolute public HTTPS API hostname ending in `/api`; empty URLs, HTTP, IP literals, local hosts, and common placeholders fail before bundling. An explicit `mobile-dev` Vite mode may use a development API, but the native shell still forbids cleartext. Use HTTPS for development on real phones.
- Website builds retain same-origin `/api` requests. Capacitor uses packaged web assets with a public API endpoint; it does not load a laptop development server.
- Android cleartext traffic and application backups are disabled. `https://localhost` and `capacitor://localhost` remain legitimate embedded Capacitor origins; they do not point to a laptop backend.
- CORS allows configured origins and Authorization headers. Cross-origin cookies are disabled because this existing API uses bearer tokens. Configure `CORS_ALLOWED_ORIGINS` for any additional legitimate cross-origin clients.
- Access tokens expire after one hour. Separate refresh-only Sanctum tokens expire after 30 days and rotate atomically on each successful renewal. Only the hash is stored in the database. No new database schema is required.
- `POST /api/auth/refresh` accepts `{ "refresh_token": "..." }` and returns `{ "data": { "token": "...", "refresh_token": "...", "user": {...} } }`. It rejects expired, revoked, replayed, or inactive-user refresh tokens. Refresh-only tokens cannot authorize application endpoints.
- The client renews an expired session once, shares in-flight renewal across concurrent requests, and retries the original request. Temporary network/server failures retain the saved session. Failed renewal with 401 clears it and routes to login.
- Logout revokes the current access/refresh pair; sessions on other devices remain valid. Offline logout clears local storage but cannot guarantee server-side revocation until the server is reachable; the orphaned pair expires according to its lifetime.
- Android tokens are encrypted with AES-GCM using a non-exportable Android Keystore key. Legacy tokens migrate out of localStorage. Native secure-storage failure has no plaintext fallback. Passwords are never persisted by this authentication flow.
- Website tokens retain the existing browser localStorage mechanism, now containing the token pair. This preserves website session behavior; browser storage remains accessible to JavaScript and needs the existing application's XSS defenses. It is not an HttpOnly-cookie authentication migration.
- Login/registration/refresh are rate limited. Vercel uses the persistent database cache instead of the ineffective per-request array cache.
- Login errors distinguish credentials, connection failures, timeouts, throttling, expired sessions, missing authentication endpoints, and server failures. The login design is preserved.

## Modified and added files

Paths below are relative to `study-planner`.

| Files | Change |
| --- | --- |
| `resources/js/services/api.ts` | Resolve conflicts; remove automatic demo fallback; bearer session storage, renewal, retries, and logout |
| `resources/js/services/tokenStorage.ts` (new) | Async native secure-storage bridge and legacy token migration |
| `resources/js/services/authError.ts` (new) | User-facing authentication error mapping |
| `resources/js/stores/auth.ts` | Shared initialization; recover after network failure; preserve saved sessions |
| `resources/js/router/index.ts` | Await restoration and recover on internet reconnection |
| `resources/js/views/auth/LoginView.vue` | Use error mapping without redesign |
| `app/Services/AuthSessionService.php` (new) | Atomic expiring access/refresh pair issuance |
| `app/Http/Controllers/Auth/AuthController.php` | Issue pairs, refresh with rotation and row locking, revoke pair on logout |
| `app/Http/Middleware/AccessTokenOnly.php` (new) | Reject refresh-only tokens and inactive users on protected routes |
| `app/Http/Middleware/RoleMiddleware.php` | Require active users for role-protected requests |
| `bootstrap/app.php`, `routes/api.php` | Register middleware, refresh endpoint, and throttling |
| `config/cors.php`, `config/sanctum.php` | Explicit origins, bearer-token CORS, and configurable maximum token age |
| `capacitor.config.ts` | HTTPS embedded origin and disabled cleartext |
| `android/app/src/main/AndroidManifest.xml` | Disable backup and cleartext traffic |
| `android/app/src/main/java/com/studyplanner/app/AuthTokenStoragePlugin.java` (new) | Keystore encryption and durable session storage |
| `android/app/src/main/java/com/studyplanner/app/MainActivity.java` | Register secure-storage plugin |
| `scripts/mobile-config.mjs` (new), `vite.config.js` | Validate production endpoint; support isolated build verification output |
| `.env.mobile` (ignored), `.env.mobile.example`, `.env.mobile-dev.example` (new) | Remove active LAN endpoint; separate production and development configuration |
| `.env.vercel.example`, `vercel.json` | Document/configure persistent cache, origins, and token age |
| `package.json` | Add explicit preview build and mobile-config test command |
| `playwright.config.ts` | Resolve conflicts; allow an explicit backend test URL |
| `tests/Feature/MobileSessionTest.php` (new) | Token expiration, renewal, replay, device isolation, CORS, inactivity, rate limiting |
| `tests/mobile-config.test.mjs` (new) | Production URL validation cases |
| `tests/e2e/remote-auth.spec.ts` (new) | Browser-to-Laravel login, session restoration, logout, reconnection, and renewal |
| `tests/e2e/stack-rendering.spec.ts` | Secure-storage bridge stub for browser simulation of native platform |
| `.gitignore` | Ignore isolated verification database and generated diagnostics |
| `docs/REMOTE_LOGIN_REPORT.md` (new) | Investigation, deployment, build, and verification record |

The local diagnostic website build under `public/build` is generated output. A mobile frontend diagnostic build was generated under ignored `.verification/mobile-test` with a non-release verification URL. It must not be installed or distributed. Existing `www`, Android packaged assets, APKs, and the deployed website have not been updated by these diagnostic builds.

## Production deployment using the existing architecture

1. Identify the actual existing Study Planner hosting project and its stable public HTTPS domain. Reuse that project and database where possible. The other projects visible in the connected account are not this application. No paid service was provisioned.
2. Confirm the production PostgreSQL database contains the existing users and application data. A fresh empty database will not recognize existing accounts. If the only database is the laptop's Docker database, back it up and migrate the whole database, preserving IDs, password hashes, relationships, and migration history. Use provider-supported backup/restore tooling; do not reseed or reset passwords. Keep database credentials server-side and restrict database network access to the application/provider as supported by the host.
3. The existing Vercel repository configuration uses root directory `study-planner`, Node 22, the existing community PHP runtime, and `npm run build:web`. Verify that the PHP runtime and required PostgreSQL extensions support the locked Composer packages before release. Never deploy `php artisan serve`, Vite, a tunnel, or a publicly exposed database as production infrastructure.
4. Configure the server environment below. Preserve the existing `APP_KEY`; rotating it can invalidate encrypted application data and cookies. Configure a provider-supported PostgreSQL TLS connection; prefer certificate-verifying TLS where supported and configure the provider's CA settings if required.
5. Back up the target database, then run `php artisan migrate --force` from a trusted environment configured against that exact target. This creates the existing token/cache tables if absent. Do not run destructive migration commands or demo seeders. No new migration was added by this change.
6. Deploy the reviewed backend and website to the intended project. Ensure the production domain allows normal mobile users to reach the API; preview deployment protection requiring a hosting-account login is not an application login.
7. Verify `/up`, valid login, invalid login, refresh, logout, and native CORS against the real production database. `/up` alone does not prove database authentication works. Do not put real passwords or tokens in screenshots or build logs.
8. Configure an existing managed scheduler to prune expired Sanctum tokens (`php artisan sanctum:prune-expired --hours=24`) regularly. Preserve the application's existing reminders/scheduled tasks; a serverless web deployment alone does not execute a laptop scheduler.

| Server variable | Required value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Existing persistent application key, stored as a hosting secret |
| `APP_URL` | Stable public HTTPS website URL |
| `DB_CONNECTION` | `pgsql` for existing production architecture |
| `DB_URL` | Secret provider connection URL for the persistent database |
| `DB_SSLMODE` | Provider-supported TLS mode, at least `require`; prefer verified TLS |
| `CACHE_STORE` | `database` so rate limits survive serverless requests |
| `SESSION_DRIVER` | Existing Vercel value `cookie` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CORS_ALLOWED_ORIGINS` | `https://localhost,capacitor://localhost`, plus actual additional cross-origin clients if used |
| `SANCTUM_EXPIRATION` | `43200` minutes maximum token age; access token's own expiry is one hour |
| `QUEUE_CONNECTION`, `LOG_CHANNEL` | Existing Vercel values `sync`, `stderr` |

`DB_HOST=postgres`, laptop addresses, localhost backend URLs, and laptop database files cannot serve this serverless production application. Build-time `VITE_*` values are public; never put database passwords, tokens, or API secrets in them.

## Rebuild the installed Android application

Deploy the backend first, then set the actual endpoint in ignored `.env.mobile`:

```dotenv
VITE_API_BASE_URL=https://ACTUAL_DEPLOYED_HOST/api
```

Replace the placeholder with the verified domain. From `study-planner`:

```powershell
npm.cmd run test:mobile-config
npm.cmd run mobile:build
npm.cmd run mobile:open
```

`mobile:build` builds the packaged frontend and runs Capacitor Android sync. In Android Studio, increment the release versionCode and generate a signed APK/AAB with the existing application ID and signing key. A website deployment cannot update native plugins or the installed APK. Install/distribute the signed update; preserve application data rather than uninstalling the app. Production users do not need USB/ADB or the laptop after this update and hosted-backend deployment.

The project contains only the Android native platform. iOS is not release-ready: an iOS native project and a registered Keychain implementation of `AuthTokenStorage` are required, followed by macOS/Xcode signing and physical-device tests. JavaScript never falls back to localStorage on unsupported native platforms.

## Verification results

| Scenario/check | Result |
| --- | --- |
| Full available Laravel unit/feature suite | Passed: 16 tests, 113 assertions, isolated SQLite |
| Production mobile endpoint validation | Passed: 15 tests |
| Real browser login with valid/invalid credentials | Passed against isolated Laravel API/database |
| Browser close-equivalent reload/session restoration | Passed; actual OS app close/reopen remains pending |
| Logout and login again | Passed with server-side revocation |
| Expired access token, refresh, and replay rejection | Passed backend tests; client renewal/retry passed browser test |
| Two independent device sessions | Passed backend token isolation; two physical devices pending |
| Temporary disconnect/reconnect | Passed simulated failed API request followed by reconnection |
| Native CORS and unknown-origin rejection | Passed server preflight tests |
| Browser login rendering at 1440px and 393px | Passed; existing design retained |
| Browser suite | Passed: 9 selected Chromium checks, including simulated native unauthorized-session routing |
| Website production frontend build | Passed |
| Mobile production frontend compilation | Passed into isolated diagnostic directory with a verification hostname; no production endpoint/live login validation implied |
| Default mobile build without production URL | Correctly fails before bundling |
| Android native secure storage/APK | Not verified: compiler encountered filesystem access errors; no signed release produced |
| Physical Wi-Fi/mobile-data login | Pending deployment and physical-device tests |
| Laptop off, another location, another physical device | Pending deployment and physical-device tests |
| Existing deployed website/account database | No deployment/database access established; unchanged by local tests |
| iOS | No native project/Keychain bridge present; not verified |

Run backend tests with `php artisan test --compact` and URL tests with `npm.cmd run test:mobile-config`. In this environment PHP needed the installed configuration explicitly: `C:/Users/Acer/php/php.exe -c C:/Users/Acer/php/php.ini artisan test --compact`. The bare PATH PHP had no loaded ini/extensions. Laravel Boost installation was attempted as instructed by AGENTS.md but blocked by package-registry network restrictions; existing dependencies were used without disabling TLS.

The browser suite was run against a throwaway SQLite database containing a test-only account, with the built website assets. The final run used `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8124` and an isolated diagnostic PHP router that ignores the development hot marker. An existing development process recreated `public/hot` during verification, which caused page-load timeouts until test serving was isolated from that marker. Its original contents were restored afterward. On a production deployment the marker must be absent; the existing Vercel packaging excludes it.

## Remaining blockers

The actual stable backend URL, access to the intended hosting project and persistent database, any required account-data migration, a signed Android rebuild, native storage testing, and physical-network testing are still required. There is no verified deployable production APK in this result. Production readiness cannot be claimed until those checks pass. No existing real user/database was modified by automated verification.

## Follow-up: preparing the phone update

The existing ADB helper successfully detected a connected phone. Read-only inspection reports `com.studyplanner.app` versionCode 1/versionName 1.0, debuggable, last updated October 11 at 22:13:57, before this investigation's fixes. No APK update has been installed. The standard ADB executable encountered a local filesystem access error; the helper accesses the already-running ADB server without changing its configuration.

New `scripts/check-production-api.mjs` verifies a supplied public HTTPS API URL, `/up`, JSON 401 on the protected profile endpoint, both native CORS origins, and database-backed rejection of one randomly generated nonexistent account. It does not use real credentials, issue tokens, or verify a valid production account. Four new tests in `tests/production-api.test.mjs` passed, including rejection of hosting-login redirects, bad CORS, and database errors despite a healthy `/up`. `package.json` adds `mobile:check-backend` and `test:production-api` commands.

New `scripts/prepare-mobile-update.ps1` runs those checks before rebuilding and syncing Android assets. It stops without rebuilding when backend validation fails. Once the real public API endpoint is confirmed, run from `study-planner`:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/prepare-mobile-update.ps1 -ApiBaseUrl https://ACTUAL_DEPLOYED_HOST/api
```

Replace the hostname with the actual deployed API. The helper temporarily sets the build environment and restores it afterward; to make future ordinary builds use the same endpoint, also put the verified URL in `.env.mobile`. Then create an APK with the signing key matching the installed app. Do not uninstall the existing app or substitute an unrelated signing key. The helper does not sign or install an APK. Hosting/database access is still missing; the visible Vercel projects are unrelated, and Docker database inspection was blocked by this environment's permissions.

Implementation references: [Laravel Sanctum](https://laravel.com/framework/docs/13.x/sanctum), [Capacitor configuration](https://capacitorjs.com/docs/config), [Android Keystore](https://developer.android.com/privacy-and-security/keystore).
