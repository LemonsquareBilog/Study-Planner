# Production release gates

Local implementation is validated; production release is not approved by these results.

- [x] Local backend, client, browser and web build checks completed.
- [x] Additive SQLite migration rehearsed up/down/up with integrity checks and verified backup copy.
- [x] Disposable local PostgreSQL 17.11 migration up/down/up and simultaneous retry tests passed; production-sized staging verification remains open.
- [x] Synthetic history baseline compared with the modified implementation.
- [ ] Match deployed commit and confirm administrator and designated student sign-in using secure credential entry.
- [ ] Verify database provider/version, applied migrations and actual indexes.
- [ ] Take and restore-test a production-engine staging backup; verify populated row counts, ownership and foreign keys after migration.
- [ ] Exercise concurrent retries, transaction rollback and unique constraints on that engine.
- [ ] Deploy web/mobile contract changes together; older clients see the bounded first page and legacy submissions lack deduplication.
- [ ] Verify reachability of api.pwnedpasswords.com for signup/password changes and API registry destinations for maintenance.
- [ ] Test physical Android/iOS on mobile data: cold launch, expiry, backgrounding, reconnect and secure storage.
- [ ] Complete supported browser, keyboard/focus, contrast, modal and responsive accessibility testing.
- [ ] Measure full HTTP/UI performance against agreed budgets; exercise monitoring and rollback.
- [ ] Obtain explicit production deployment approval.

No production migration, data mutation or deployment was performed.
