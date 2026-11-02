# Mobile/web integration record

Both clients use the same Laravel endpoints. Existing cookie-backed web authentication and native secure token storage are preserved; preview fixtures remain compiled out of real backend builds.

New quiz/history page methods accept the paginated server response and normalize older array responses. Preview arrays are paged locally. Existing array-returning facade methods remain available. Tasks share page/search/sort query types and preview filtering/sorting; deterministic backend ID tie-breaks avoid duplicate/omitted records when primary sort values tie.

Quiz detail supplies an encrypted submission identity. Updated clients submit the same identity and start timestamp on retries; retake reloads detail for a new identity. An existing native binary without this change can still submit but lacks deduplication. Coordinate backend, web and native rollout; older list screens show only the first bounded page. Submission identities are not persisted across page/process loss; returning to quiz detail starts a new logical attempt.

Verified: browser lost-response retry/retake flow, session recovery, native-expiry browser simulation, task/quiz/history contract tests and 15 endpoint-configuration tests. Web and mobile frontend asset builds passed. Mobile assets were emitted to ignored .verification/mobile-build using the audited HTTPS API URL; backend reachability and native binary compilation are not established by this build. Native APK/IPA compilation, physical Android/iOS secure storage, mobile data reachability, offline/background/resume, device switching and real network reconnection remain not run. Browser mobile viewport coverage is not physical-device evidence.
