# Preserved professor-demo accounts — 2026-10-18

The five original demo accounts and their saved learning records have been restored to the persistent PostgreSQL database used by the existing live website and Android application:

`https://study-planner-gamma-seven.vercel.app`

The admin address is **`admin@studyplanner.test`**, not `admin@studentplanner.test`. The latter address was absent from both the preserved source and the hosted database; no duplicate alias account was created.

| Account | Role | Subjects | Tasks | Study sessions | Quiz attempts |
|---|---|---:|---:|---:|---:|
| `admin@studyplanner.test` | Administrator | — | — | — | — |
| `alex.chen@student.test` | Student | 4 | 22 | 43 | 71 |
| `maria.santos@student.test` | Student | 6 | 31 | 44 | 49 |
| `james.wilson@student.test` | Student | 6 | 35 | 42 | 58 |
| `sarah.kim@student.test` | Student | 5 | 30 | 35 | 46 |

All related preferences, topics, quizzes, questions, answers, resource links, notifications and recommendation views were retained. The restored dataset contains 84 topics, 84 quizzes, 336 questions, 896 quiz answers, 15 resources, 52 notifications and 21 recommendation views. Source IDs were remapped to avoid collisions with existing users and records.

## Signing in

At the owner's request, the hosted admin and Alex passwords now match their localhost demo passwords. The other three hosted accounts retain their separate randomly generated passwords. Local accounts are unchanged. Password values remain in the private credentials file rather than this document.

The private hosted credentials are in `.verification/hosted-demo-credentials.json` on this computer. The file is ignored by Git and excluded from Vercel/Docker deployment. Open it locally and use the value next to the exact account email. Do not put it into frontend environment variables, source control, screenshots, or public presentation materials. The demo administrator is a real administrator in this existing live app and can administer real production users; share its access only with trusted people.

Web and mobile use the same hosted credentials. The already-installed phone update points to this HTTPS backend; no new APK is needed for this database restoration. The hosted database persists independently of the laptop.

## Demonstrating the saved records

Alex is a useful starting account: its saved profile includes four subjects, 22 tasks, 43 sessions and 71 quiz attempts. Other students have different subjects/workloads and assessment histories.

The original dates were preserved: sessions span **2026-09-08 through 2026-10-23** and task deadlines span **2026-09-15 through 2026-10-29**. Select that range on Study Plan when showing the full schedule. Completed/history filters may be needed to see older work. No dates, scores or statuses were changed just to make the current dashboard look busy.

Do not run demo seeders or `migrate:fresh` against this live database. Routine web/phone restarts and code deployments do not recreate or reset these accounts. Any edits made during a presentation are real persistent edits to these demo accounts.

## Preservation, implementation and verification

- Before the import the hosted database contained two users, and none of the selected demo accounts. Afterward it contains seven users. The original two users' emails and password hashes matched the pre-import snapshot exactly.
- `DemoAccountRestoreService` performs an explicit additive import in a transaction. It rejects an existing selected account rather than overwriting credentials or data, remaps all foreign keys and validates per-account record counts before commit. Source sessions, authentication tokens, caches and global settings are not imported.
- `scripts/restore-hosted-demo.php` is CLI-only and defaults to a dry run. Applying requires the exact HTTPS application URL and explicit production-demo flag. Normal production seeding remains blocked. A matching successful receipt makes reruns return without resetting or duplicating the accounts.
- Logical snapshots of every affected target table and the ID-mapping receipt are saved privately under `.verification/hosted-demo-before-*.json` and `.verification/hosted-demo-restore-receipt.json`. Keep them private: snapshots contain password hashes. Rollback must be deliberately reviewed against the receipt's inserted IDs; do not replace the live database or restore the old snapshot over new real-user activity.
- The first slow row-by-row import was interrupted before commit. A read-only inspection confirmed the original two-user state before the tested batch import was applied successfully.
- All **26 backend tests / 163 assertions** passed, including import dry-run behavior, relationship remapping, existing-user/password preservation, duplicate-account rejection and complete rollback on an invalid relationship.
- All **five hosted accounts** passed live HTTPS API login, profile verification, native Android CORS, their role-appropriate application endpoints (34 protected endpoint checks), and logout. Results: `.verification/hosted-demo-api-results.json`; no credentials or tokens were printed.
- The restored admin and Alex passed real browser UI login, dashboard loading and logout on the existing live website.
- Both also passed login and dashboard loading in the installed application on the physical Android phone. Android UI controls were used after the WebView debugger timed out; passwords were supplied through stdin rather than host process arguments or logs. This verifies the connected phone's current network, not a separate cellular-network or laptop-disconnected test.

This change restores selected demonstration records to the existing live database, as requested. It does not create a separate demo tenancy or deploy all previously prepared backend/web security changes. The existing data-ownership policies still apply; administrators retain their normal privileged access.
