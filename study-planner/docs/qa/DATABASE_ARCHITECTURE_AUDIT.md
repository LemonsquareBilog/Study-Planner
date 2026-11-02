# Database architecture audit

Inspected repository migrations and the local SQLite schema. Production provider, volumes, plans and backups remain unavailable.

Existing task indexes cover user/status and user/deadline plus subject/deadline and topic/status. Existing quiz-attempt indexes cover user/quiz, user/completed_at and quiz/completed_at. The local history query plan uses `quiz_attempts_user_id_completed_at_index`; the latest-attempt query uses `quiz_attempts_quiz_id_completed_at_index`. See evidence/query-plans.json. These are SQLite observations, not production plans.

The confirmed cost was unbounded history/attempt hydration. Query changes bound returned rows before loading related data. Quiz aggregates execute in SQL; current-page latest IDs are selected using deterministic completed_at/ID ordering. History summaries omit answers/questions; detail access remains ownership-scoped.

One additive schema change supports correctness: nullable submission_id UUID, nullable 64-character submission_hash, unique (user_id, submission_id). Existing attempts remain unchanged with NULL identity. Fingerprints/identities are hidden in API attempt serialization. No normalization changes, cascading-delete policy changes, speculative performance indexes or caching were introduced.

Next: capture production-engine EXPLAIN plans under representative multi-user distributions before considering a covering index. Review cascading-delete retention separately; this task did not change it.
