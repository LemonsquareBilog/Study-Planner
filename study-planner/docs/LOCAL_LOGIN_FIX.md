# Localhost login repair — 2026-10-18

Open **http://localhost:8000/login**. The fixed local server is running against the preserved `database/development.sqlite`, not the hosted database.

## Confirmed failure and repair

- Before the repair, localhost login returned JSON HTTP **500**, which the UI correctly translated to “The server is temporarily unavailable.”
- The actual HTTP worker was Herd PHP 8.5.0. Its php.ini tried loading PostgreSQL extensions from nonexistent `C:/php/ext` files. Its available PDO drivers were MySQL and SQLite, while the default project `.env` selected PostgreSQL at the Docker hostname `postgres`. Recent Laravel logs repeatedly contained `could not find driver`.
- An existing `concurrently` development supervisor automatically restarted the broken `php artisan serve` worker after it stopped. The same stack also launched a default-environment queue listener. The identified project stack was stopped and restarted with the corrected backend and Vite configuration; unrelated processes were retained.
- `scripts/serve.ps1` now finds an installed compatible PHP runtime, enables the required extensions in the actual HTTP process, validates the development database before listening, and binds only to loopback. It does not modify global PHP installations or php.ini files.
- `scripts/local-environment.php` and `scripts/local-router.php` explicitly load the private `.env.development`, reject remote database URLs, and isolate the local runtime from cached Docker/production configuration. No database is automatically created, migrated, seeded, or reset.
- Windows `composer dev` / `php artisan dev` now register the corrected server launcher. The development environment uses synchronous jobs, so the unrelated default-environment queue listener is excluded. Docker's Linux startup and its database configuration were retained.
- Vite scans the explicit application entry instead of unrelated repository HTML, and excludes generated/dependency directories from watching. Its API proxy rewrites only the two explicit localhost:5173/127.0.0.1:5173 development origins to the backend origin. The backend's strict browser-origin protection remains unchanged.

## Which credentials to use

| Environment | Correct credential file | Data |
|---|---|---|
| Localhost | `.verification/local-demo-credentials.json` | Original local demo records and original development passwords |
| Live website and installed Android app | `.verification/hosted-demo-credentials.json` | Restored persistent hosted demo records and private hosted passwords |

Both files are private, ignored by Git and excluded from deployment. Passwords were not reset or synchronized between environments. The admin email is **`admin@studyplanner.test`**. Alex remains **`alex.chen@student.test`**; Maria, James and Sarah are also available.

The default `.env` still describes the separate Docker/PostgreSQL setup. The local launcher explicitly uses `.env.development`; do not replace it with cloud database credentials to make passwords match.

## Starting it later

From `study-planner`:

```powershell
npm.cmd run local:check
composer dev
```

The Windows `composer dev` command starts the repaired backend on port 8000 and Vite for the frontend assets. For separate terminals, use `npm.cmd run dev:local` for the backend and `npm.cmd run dev` for Vite. Keep Vite running while its generated `public/hot` file is present. If an existing server already owns port 8000, stop that identified local development session before starting another.

For a fresh checkout, first create the private `.env.development` from `.env.development.example`, configure an existing dedicated development SQLite file and a separate development application key. The launcher intentionally refuses a missing database instead of silently creating an empty replacement.

## Executed checks

- Local runtime preflight passed under installed PHP 8.3 and Herd PHP 8.5 with PDO SQLite enabled and six existing development users present (five original demo accounts plus the earlier verification fixture).
- All five original demo accounts passed real HTTP login, authenticated profile retrieval and logout at `localhost:8000`.
- Invalid credentials now return HTTP **422**, not 500. The local Vite proxy also returned the expected 422 for an invalid-account probe.
- Admin and Alex passed actual browser login, dashboard rendering, reload/session restoration and logout at `localhost:8000`.
- The complete backend suite passed: **26 tests, 163 assertions**.
- The production website frontend build passed after the Vite configuration update. CLI inspection also confirmed the standard Windows development command selects the fixed launcher and excludes the unrelated queue listener.

The production database, hosted passwords and Android endpoint were not modified by this repair. No user or study records were deleted or reseeded.

## Mobile update status

The Android app was already updated in place on 2026-10-18, with a signature-compatible full APK build and no data clear. Admin and Alex login/dashboard loading were subsequently verified on that physical phone after the hosted demo restoration. It uses the deployed HTTPS backend and hosted passwords, independently of this laptop's localhost server. See [device update](ANDROID_DEVICE_UPDATE.md) and [hosted demo verification](HOSTED_DEMO_ACCOUNTS.md).
