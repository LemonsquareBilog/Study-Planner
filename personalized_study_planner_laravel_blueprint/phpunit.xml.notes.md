# Testing notes

Feature tests should cover:
1. registration/login/logout
2. inactive-account rejection
3. student cannot access another student's subject/task
4. topic must belong to task subject
5. study-plan generation respects daily/session limits
6. duplicate/overlapping study sessions are rejected by service logic
7. quiz submission calculates server-side score
8. correct answers are never returned to student quiz payloads
9. weak-topic recommendations use configured threshold
10. non-admin cannot manage resources
