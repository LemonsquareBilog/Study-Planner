# Controlled quiz-history benchmark

3 November 2026, local Debian 13, PHP 8.4.24, SQLite 3.46.1. Baseline: QuizController from commit d9b2085913e6b503b8077d625d807334d4913cdb; updated: this working tree. Same disposable file-backed SQLite fixture, one student, one quiz/question, 1,000 completed attempts and one answer each. Twenty measured controller calls per variant after one warmup each. Baseline ran first, updated second. Response serialization included; HTTP transport, browser rendering, authentication and external network excluded. DB query logging enabled equally. p95 uses nearest-rank sample 19/20. Incremental peak is maximum reset peak minus starting allocated usage, not total process RSS.

| Metric | Baseline | Updated |
| --- | ---: | ---: |
| Rows per response | 1,000 | 20 |
| Serialized bytes | 1,337,059 | 20,045 |
| Queries | 6 | 5 |
| Median controller time | 472.09 ms | 7.87 ms |
| p95 controller time | 573.58 ms | 10.25 ms |
| Max incremental peak | 11,122,896 B | 160,384 B |

Raw observations: evidence/history-benchmark.json. Expected semantics differ deliberately: updated history returns a summary page; answer detail requires a separate owned-attempt request. The benchmark demonstrates bounded hydration/serialization, not equal full-history transfer or a production latency guarantee. Production p95, database-only p95, UI loading, Lighthouse, mobile memory and cross-device transitions remain unmeasured; no agreed production budget exists.

Reproduce on a disposable database: apply migrations; create the fixture above; instantiate baseline/current controllers against the same user-resolved Request for page 1; warm each variant; time history() through getContent() with hrtime(true), reset peak usage each iteration, and log queries. Never seed/delete production history for a benchmark. The actual experiment scripts and raw logs remain in ignored .verification in the prepared workspace.

The web build still reports large-chunk/mixed-import warnings. No broad bundle rewrite or speculative caching was attempted without application/device measurements. Existing indexes handled the local history/latest plans; production-engine profiling is a release follow-up.

## Dashboard follow-up

A second local SQLite experiment compared the complete student dashboard controller against the previous implementation using the same fixture: one student, 1,000 completed quiz attempts, no tasks or study sessions, and no matching resource recommendations. Each variant received one warmup and 20 measured controller calls; the baseline ran first. Responses matched except for the generated timestamp. Timings include controller work and response serialization but exclude HTTP transport, authentication, remote database connections and browser rendering. Values are not a production estimate.

| Metric | Before | After |
| --- | ---: | ---: |
| Database queries | 13 | 10 |
| Median controller time | 24.9 ms | 2.09 ms |
| p95 controller time | 31.63 ms | 2.51 ms |
| Maximum incremental peak memory | 2,314,064 B | 22,640 B |

Raw results are in [dashboard-benchmark.json](evidence/dashboard-benchmark.json). Progress totals now use SQL aggregation; duplicate onboarding counts and recommendation lookups were consolidated. A populated admin dashboard test also recorded 11 queries before and at most 9 after. The student dashboard shows its previous data while refreshing within the same authenticated session; logout, re-login and account changes clear it.

Database execution plans and request timings on the actual deployment remain necessary to identify the cause and degree of live latency. Browser tests simulate network responses, and mobile assets were built without compiling a native application.
