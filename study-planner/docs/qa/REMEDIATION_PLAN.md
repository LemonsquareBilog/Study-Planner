# Remediation disposition

| Finding | Local change | Evidence |
| --- | --- | --- |
| QA-001 | 20-row history summaries; detail requested separately | QuizPaginationTest and browser review test |
| QA-002 | Ownership subquery, 20-row quiz pages, SQL aggregates, bounded latest-attempt load | QuizPaginationTest |
| QA-003 | Shared TaskQuery with page/search/sort, UI controls, stable ID tie-break and preview paging | TaskPaginationTest, client and browser pagination |
| QA-004 | Versioned guarded decoder; invalid storage removed; exact web marker | Client recovery and browser startup tests |
| QA-005 | Dedicated normalized error classifier | Client recovery tests |
| QA-006 | Book label uses backend ebook in filter/form | AdminResourceContractTest and browser filter |
| QA-007 | UserCard binds computed role CSS without calling it | Populated desktop/mobile render and edit tests |
| QA-008 | Remove unsupported Add User UI and dead handlers | Browser asserts no creation action; no privileged endpoint introduced |
| QA-009 | Import SettingInput, explicit loading/empty/error/retry/save states; retain failed input | Browser Settings scenarios |
| QA-010 | Schema-gated submission identity and additive unique constraint; client retains identity on retries after migration | Backend conflict/retake tests, browser lost-response retry, two-process SQLite experiment |
| BLOCK-001 | Local authentication flows pass; deployed designated student access not established | Deployment owner must verify the real account securely |

Next: rehearse on the production database engine in staging, upgrade deployed web/native clients together, complete device/accessibility/security checks, then request production release approval. No broad table restructuring, cache layer or mass repository cleanup was justified or applied.
