# Security implementation assessment

Local changes retain role middleware, ownership checks, server scoring, transactions, token refresh coordination and session-version protections. No production credentials or accounts were changed.

- Session JSON is guarded and checked for token/refresh-token types and supported version; invalid sessions are removed. Web storage accepts the exact cookie-session marker rather than a substring. Native secure storage still fails closed without browser fallback.
- Normal 404s are not network outages. Cancellations, timeouts, authentication, authorization, validation and service errors have separate categories.
- Quiz summaries are user-scoped. Answer details remain on the owned-attempt endpoint. Available quizzes exclude inactive and foreign-owned records.
- The server encrypts/authenticates submission identities bound to user and quiz. A unique user/identity constraint and transaction/user-row lock prevent duplicate inserts. Conflicting reuse receives 409; forged identifiers receive 422. Submission identities and fingerprints are hidden in attempt responses.
- Legacy submissions without an identity remain accepted for rollout compatibility and do not gain retry deduplication. Upgrade web/native clients before considering mandatory identities.
- Unsupported privileged account creation was removed from the UI; no creation endpoint was added. Existing admin edit limits remain authoritative.

Verified locally: auth suite, wrong-role denial, cross-user task/history/quiz isolation, answer hiding before grading, forged identity rejection, retry conflicts, native expiry and invalid web-session recovery. Production headers/cookies, refresh replay under real infrastructure, CORS, dependency advisory audit, upload validation, penetration testing and physical secure storage remain unverified. No blanket security claim is made.
