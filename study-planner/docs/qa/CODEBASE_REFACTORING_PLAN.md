# Incremental refactoring record

The existing Laravel and Vue structure is retained. No dependency upgrades or whole-repository moves were needed.

Implemented boundaries behind the existing services/api.ts compatibility facade:

- services/sessionDecoder.ts: guarded versioned session decoding and legacy compatibility.
- services/httpError.ts: HTTP error categories and the existing unavailable helper export.
- services/pagination.ts: old-array/preview-to-page compatibility.
- Shared TaskQuery/QuizQuery types; quiz page methods preserve array-returning facade methods for remaining consumers.
- StudyPlan now queries a related quiz by topic on demand, avoiding an implicit first-page lookup after pagination.

UserCard computed binding and canonical ebook values are targeted fixes. SettingInput now models draft input and explicit save while the parent owns actual request/error/success state. Unsupported user creation handlers were removed instead of adding a privileged endpoint outside the supported contract.

Further controller/query/action extraction should follow measured complexity and independently passing feature tests. Keep centralized refresh coordination and session-version protections; do not move them incidentally. Generated bundles, blueprint duplicates and caches were not deleted because deployment dependencies have not been established.
