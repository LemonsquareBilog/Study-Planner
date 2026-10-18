# Authentication and security implementation — 2026-10-18

Local authentication is verified. Source changes and mobile assets are ready for review; they have not been deployed to production or installed on a phone.

## Verified causes

- The active `.env` selects **production PostgreSQL**, whereas the preserved SQLite development database contains the seeded accounts. Four recognized seeded account hashes verified successfully and all four passed real API login, bearer profile retrieval, and logout using an isolated copy. The production account records were not inspected, so the existence or password of any particular production account remains unverified.
- `.env.mobile` had an empty API URL. Earlier investigation documented laptop LAN addressing in old packaged assets. Source validation correctly prevents shipping that configuration. Mobile now uses `https://study-planner-gamma-seven.vercel.app/api`; the final asset scan excludes the old LAN address.
- Login and signup displayed Laravel validation arrays directly, explaining the literal bracketed credential error. Both now display readable text.
- The demo module was imported into real builds, exposing sample credentials even when demo authentication was unreachable. It is now conditionally imported only in explicit `preview` builds; known demo passwords are absent from web/mobile/Android assets.
- Signup and password changes enforced inconsistent, outdated password rules. Browser tokens previously persisted in JavaScript-readable storage.

## Changes made in this implementation

| Files | Purpose |
|---|---|
| `app/Http/Controllers/Auth/AuthController.php` | Case-insensitive parameterized email lookup; upgrade legacy password hashes after login; shared password-change policy; revoke sessions after password change; atomic signup/preferences/token creation |
| `app/Http/Requests/Auth/RegisterRequest.php`, `app/Rules/SafePassword.php` | Normalize signup email; require 15–128 characters; reject known breached passwords through padded hash-prefix requests; fail closed on safety-service outage; no composition rules |
| `config/hashing.php` | New Argon2id hashes; verify existing bcrypt hashes during migration; no silent truncation of new long passwords |
| `app/Http/Middleware/BrowserSession.php`, `bootstrap/app.php` | Encrypted HttpOnly, SameSite=Strict browser cookies; production Secure flag; custom-header and origin checks; browser responses contain only session markers; remove generic exception-message logging |
| `app/Http/Middleware/SecurityHeaders.php` | MIME sniffing, framing, referrer, permission, HTTPS transport headers |
| `resources/js/services/api.ts`, `tokenStorage.ts` | Browser cookie transport; remove legacy browser bearer secrets; retain Android secure token storage; exclude demo credentials from real builds |
| `resources/js/views/auth/LoginView.vue`, `RegisterView.vue` | Normalize validation messages; accessible password creation guidance |
| `resources/js/components/PasswordField.vue`, `resources/js/services/passwordStrength.ts` | Independent show/hide controls; zxcvbn-ts estimator; Weak/red, Average/orange, Good/blue, Strong/green meter; personal-input/pattern detection; paste/autofill supported |
| `database/seeders/DatabaseSeeder.php`, `DevelopmentAccountsSeeder.php`, `config/development.php` | Block sample accounts outside local/testing; preserve existing users/passwords; repeatable missing-account fixtures with private environment password |
| `.env.development.example`, `.env.example`, `.env.vercel.example`, `.env.mobile` (private), `.gitignore`, `.dockerignore` | Explicit development database and production settings; exclude private configuration, backups and local data from deployment |
| `resources/js/components/ui/SelectInput.vue` | Forward accessible labels to the actual select control, discovered by regression testing |
| `resources/js/views/student/StudyPlanView.vue` | Restore missing data-loading, grouping, generation, session-action and formatting helpers through the existing store; optional notification initialization cannot block loading; clean up timer subscriptions |
| `package.json`, `package-lock.json`, `composer.lock` | Add estimator; compatible npm security fixes; CommonMark security patch; select Symfony YAML compatible with PHP 8.3 |
| `tests/Feature/AuthenticationTest.php`, `PasswordSecurityTest.php`, `tests/e2e/remote-auth.spec.ts`, `password-signup.spec.ts`, `stack-rendering.spec.ts` | Real authentication, browser-cookie, password, environment and UI checks |
| `www/`, `android/app/src/main/assets/` | Rebuilt mobile assets and Capacitor sync; generated output (some assets are tracked) |

Pre-existing uncommitted authentication work was preserved, including access/refresh token expiry and rotation, native Keystore encryption, CORS, inactive-user checks, login throttling and production URL validation. Its tests were executed rather than assuming it worked. See `REMOTE_LOGIN_REPORT.md` for historical findings; this document supersedes its browser-storage and build-status statements.

## Environment separation and rollback

- The active production `.env` was retained. No database reset or production migration was performed.
- `database/database.sqlite` was backed up with SQLite's backup API into ignored `.verification/rollback-auth/local-database.sqlite`. A separate `database/development.sqlite` copy preserves its five existing users and records; local verification added an explicitly named verification account to the copy only.
- `.env.development` is private, uses `APP_ENV=local`, an absolute development SQLite path, a new development key, and an empty `DB_URL`. It is loaded explicitly with `--env=development`; never copy production credentials into it.
- Pre-change tracked differences and selected source copies are in `.verification/rollback-auth/`. Preserve all pre-existing uncommitted changes when rolling back; do not use `git reset --hard`. Restore only this implementation's reviewed files. Production needs its own provider backup before deployment; no production backup was attempted.
- `DevelopmentAccountsSeeder` creates `developer@student.test` and `developer@admin.test` only when missing. Set a private `DEV_SEED_PASSWORD` of at least 15 characters. Rerunning does not change existing passwords. Original seeded accounts remain usable without applying the new password-creation policy retroactively.
- Seed guards prevent contamination through these seeders, but independent database credentials, network restrictions and deployment permissions must also be configured by the host. Do not expose the development server publicly. Use independently hosted, access-restricted staging for shared remote testing.

## Running locally

From `study-planner`, use PHP with mbstring, PDO SQLite/PostgreSQL, OpenSSL, cURL and the normal Laravel extensions enabled. The available shell PHP had no loaded php.ini; the project-local `.verification/php.ini` enables installed extensions for this workstation. Use your normal configured PHP in other environments.

```powershell
php -c .verification/php.ini artisan migrate --env=development
php -c .verification/php.ini artisan serve --env=development --host=127.0.0.1 --port=8000
# A second terminal: build assets once, or run Vite for live edits.
npm.cmd run build:web
# optional: npm.cmd run dev
```

Open `http://127.0.0.1:8000`. Keep the development database file; restarts do not migrate, seed or reset it automatically. For a fresh checkout, copy `.env.development.example` to `.env.development`, use an absolute DB path, create the SQLite file, then run `key:generate --env=development` and `migrate --env=development`.

To add missing private fixtures after setting `DEV_SEED_PASSWORD` in `.env.development`:

```powershell
php -c .verification/php.ini artisan db:seed --env=development --class=DevelopmentAccountsSeeder
```

Do not run `migrate:fresh` against existing data. To recreate samples, create a **new** explicitly named development database and point only the development configuration at it. Keep the original as rollback data.

## Remote/mobile release

1. Review backend and frontend changes together; deploy them together. HttpOnly browser sessions require the new middleware. Existing browser bearer sessions will require one fresh sign-in after this migration. Keep the existing production `APP_KEY` stable; it encrypts browser cookies.
2. Confirm the production runtime supports Argon2id, mbstring and PostgreSQL; configure `APP_ENV=production`, `APP_DEBUG=false`, `HASH_DRIVER=argon2id`, the stable HTTPS `APP_URL`, `CACHE_STORE=database`, and independent production `DB_URL` with verified database TLS where supported. Keep development fixtures and passwords absent. A persistent database cache is essential for throttling.
3. Retain the configured native origins `https://localhost,capacitor://localhost`; those are WebView origins, not backend addresses. Same-origin web requests use `/api`. Cross-origin browser-cookie hosting is intentionally unsupported by this implementation.
4. Run `node scripts/check-production-api.mjs https://study-planner-gamma-seven.vercel.app/api`; then verify a real authorized production account, refresh and logout. Do not substitute development credentials or seed production to make login pass.
5. `npm.cmd run mobile:build` rebuilds the frontend and synchronizes Android. The private `.env.mobile` now contains the verified public HTTPS endpoint. For local devices use a trusted HTTPS development/staging endpoint in a `mobile-dev` build; physical-device localhost and emulator host mappings are not remote hosting.
6. Install/configure **JDK 21** for Gradle, then run `./gradlew.bat assembleDebug` from `android` for a debug APK. The installed Android Studio JBR did not satisfy the notification plugin's JDK 21 toolchain requirement. Do not lower plugin Java requirements to hide this failure.
7. For distribution, increment the version code and sign using the existing application's release key in Android Studio/CI. No signing key was accessed and no APK was installed. Test Wi-Fi, cellular, laptop disconnected, invalid credentials, expiration and logout on a physical phone before distributing.

## Security and operational limits

- Password safety checks use the Pwned Passwords range API only at password creation/change. No plaintext password or full hash is transmitted. An outage temporarily prevents creating/changing passwords; login is unaffected. Unit/integration tests mock this dependency; a live service outage test was not performed.
- Browser storage holds only a non-secret marker. Native access/refresh tokens still use the pre-existing Android Keystore plugin. iOS has no native Keychain implementation in this workspace and has not been built or validated.
- Compatible npm fixes removed the reported critical/high advisories. Three moderate reports remain in the Capacitor CLI → xcode → uuid dependency chain. Resolving them with npm's proposed forced CLI downgrade needs separate compatibility review; no forced downgrade was applied.
- CommonMark was updated to 2.10.4. Existing Symfony YAML 8.1.8 required PHP >=8.4.1 despite the application's PHP 8.3 target; Composer selected supported 7.4.21 within the dependency's allowed range. The resulting Composer operation reported no security advisories.
- The estimator dictionaries add approximately 1.67 MB uncompressed to the lazy signup chunk. Login does not download that chunk. Full TypeScript cleanup, penetration testing, MFA, provider database access/backups/restore, production monitoring and iOS validation are not completed by these targeted fixes.
- Existing database sample credentials are development-only legacy fixtures. Do not publish or use them for live accounts. No production user was deleted, reset or deliberately created.

Password length, lack of arbitrary composition rules, blocklist checking and password-manager support follow [NIST SP 800-63B-4](https://pages.nist.gov/800-63-4/sp800-63b.html). This is a targeted implementation and verification record, not a certification that the entire application is secure.

## Verification evidence

See the adjacent verification-results document for final executed counts and outstanding checks. Raw logs are under ignored `.verification/`; never publish those directories or database backups.
