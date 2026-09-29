# Personalized Study Planner and Learning Recommendation System
## Complete Laravel Backend/Data Foundation & Implementation Blueprint

> Based strictly on the supplied IPT2 proposal. The proposal specifies a web application using Vue.js, Laravel, MySQL, Vercel, Git/GitHub, Figma and Postman. It includes user management, subjects/tasks, automated study plans, in-app/email reminders, self-assessment quizzes, rule-based resource recommendations, progress reporting, and admin resource/system management. It explicitly excludes LMS/SIS integration, native mobile apps, ML adaptive learning, live collaboration/tutoring, offline sync, and multi-institution deployment.

## 1. Final architecture

```text
Vue 3 + TypeScript + Tailwind
          |
       HTTPS/REST
          |
Laravel API
 | Auth / RBAC
 | Controllers
 | Form Requests
 | Policies
 | Services
 | Eloquent ORM
          |
        MySQL
```

Business flow:

```text
Request
  -> Form Request
  -> Policy/Role
  -> Controller
  -> Service
  -> Eloquent
  -> MySQL
```

Services:
- `StudyPlanService`
- `RecommendationService`
- `QuizService`
- `ProgressService`
- `NotificationService`

## 2. Domain schema

```text
users
 ├── study_preferences
 ├── subjects
 │    ├── topics
 │    │    ├── quizzes
 │    │    │    └── questions
 │    │    └── resources (many-to-many)
 │    ├── tasks
 │    └── study_sessions
 ├── quiz_attempts
 │    └── quiz_answers
 ├── notifications
 └── recommendation_views

resources
 ├── resource_subject
 └── resource_topic

system_settings
```

### Tables

| Table | Purpose |
|---|---|
| users | authentication, profile and role |
| study_preferences | available study time and session settings |
| subjects | student's academic subjects |
| topics | subject-specific weak-area/quiz units |
| tasks | assignments, exams, projects and deadlines |
| study_sessions | generated/planned study blocks |
| quizzes | topic assessments |
| questions | multiple-choice quiz questions |
| quiz_attempts | historical quiz scores |
| quiz_answers | per-question historical performance |
| resources | admin-managed learning library |
| resource_subject | resource-to-subject mapping |
| resource_topic | resource-to-topic mapping |
| notifications | in-app/email reminder state |
| recommendation_views | recommendation interaction history |
| system_settings | configurable thresholds/reminder settings |

## 3. Key constraints

- `users.email` unique.
- `subjects(user_id,name)` unique.
- `topics(subject_id,name)` unique.
- `questions(quiz_id,question_order)` unique.
- `quiz_answers(quiz_attempt_id,question_id)` unique.
- Resource pivot pairs are primary keys.
- Study-session exact time boundaries are unique per student/day.
- Cross-record ownership is enforced by Laravel services/policies because SQL foreign keys alone cannot verify that a topic belongs to the student's subject.
- Student-facing quiz payloads must never expose `correct_answer`.

## 4. Migrations

The companion implementation directory contains executable migration files under:

`database/migrations/`

Migration order:
1. users
2. study_preferences
3. subjects
4. topics
5. tasks
6. study_sessions
7. quizzes
8. questions
9. quiz_attempts
10. quiz_answers
11. resources
12. resource_subject
13. resource_topic
14. notifications
15. recommendation_views
16. system_settings

## 5. Eloquent models

Models are provided under:

`app/Models/`

Relationships:
- User hasOne StudyPreference.
- User hasMany Subjects, Tasks, StudySessions, QuizAttempts, Notifications, RecommendationViews.
- Subject belongsTo User and hasMany Topics, Tasks, StudySessions; belongsToMany Resources.
- Topic belongsTo Subject and hasMany Tasks, StudySessions, Quizzes; belongsToMany Resources.
- Task belongsTo User, Subject and optional Topic; hasMany StudySessions.
- StudySession belongsTo User, Subject, optional Topic and optional Task.
- Quiz belongsTo Topic and hasMany Questions/Attempts.
- Question belongsTo Quiz and hasMany QuizAnswers.
- QuizAttempt belongsTo User/Quiz and hasMany QuizAnswers.
- Resource belongsToMany Subjects/Topics and hasMany RecommendationViews.
- Notification belongsTo User.
- RecommendationView belongsTo User/Resource/Topic.

## 6. Study plan algorithm

Inputs:
- pending/in-progress tasks
- task deadlines
- task priority
- estimated duration
- student maximum daily minutes
- preferred start/end time
- session duration
- break duration

Algorithm:
1. Validate date range and study preferences.
2. Load unfinished tasks in the planning period.
3. Order urgent tasks first, then deadline.
4. Calculate remaining minutes after existing study sessions.
5. Iterate available days before the deadline.
6. Calculate remaining daily capacity.
7. Find the next non-conflicting slot.
8. Create a study session.
9. Repeat until task time is allocated or planning window ends.
10. Use a transaction so a failed generation does not leave a partial plan.

Important: overlap detection must be performed in service code. A unique constraint can stop identical intervals but cannot by itself detect all interval overlaps.

## 7. Recommendation engine

The proposal explicitly requires rule-based recommendations rather than machine learning.

Recommended classification:
- below `weak_topic_threshold`: Weak
- up to `review_topic_threshold`: Needs Review
- above it: Strong

The engine:
1. Reads historical quiz attempts.
2. Groups scores by topic.
3. Calculates average topic performance.
4. Identifies weak topics.
5. Finds active resources mapped to those topics.
6. Returns resources to the student.

`weak_topic_threshold` is stored in `system_settings`.

## 8. Quiz security

Never trust a client-provided score.

The client sends selected answers only. `QuizService`:
1. Loads authoritative questions from MySQL.
2. Compares answers to hidden `correct_answer`.
3. Calculates score server-side.
4. Stores `quiz_attempt`.
5. Stores each `quiz_answer`.
6. Calculates percentage.
7. Returns the resulting attempt.

The `Question` model hides `correct_answer` by default.

## 9. Progress reporting

The progress service calculates:
- total tasks
- completed tasks
- completion percentage
- total study sessions
- completed study sessions
- completed study minutes
- quiz attempts
- average quiz percentage

The frontend can transform these into cards/charts without duplicating business calculations.

## 10. Notifications

Use:
- `notifications` table for in-app state
- Laravel notification classes for delivery
- queued email jobs
- scheduler/commands for deadline and study-session reminders

Notification types:
- task_reminder
- deadline_reminder
- study_session_reminder
- recommendation
- system

## 11. Authorization

Roles:
- Admin: resource library, user roles/system settings.
- Student: own subjects, tasks, plans, quizzes, recommendations and progress.
- Instructor: optional read-only aggregated/anonymized class-level data, subject to student consent.

The supplied proposal does not define the exact instructor-consent data model, so a later requirements decision is required before implementing that module.

## 12. API routes

```text
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout

GET    /api/student/dashboard

GET    /api/student/tasks
POST   /api/student/tasks
PUT    /api/student/tasks/{task}
DELETE /api/student/tasks/{task}
POST   /api/student/tasks/{task}/complete

GET    /api/student/study-plan
POST   /api/student/study-plan/generate

GET    /api/student/quizzes/{quiz}
POST   /api/student/quizzes/{quiz}/submit

GET    /api/student/notifications
POST   /api/student/notifications/{notification}/read

GET    /api/admin/resources
POST   /api/admin/resources
PUT    /api/admin/resources/{resource}
DELETE /api/admin/resources/{resource}
```

## 13. Frontend structure

```text
resources/js/
├── app.ts
├── router/
├── stores/
├── services/api.ts
├── layouts/
├── views/
│   ├── auth/
│   ├── student/
│   └── admin/
└── components/
```

Student views:
- Dashboard
- Subjects
- Tasks
- Study Plan
- Quiz
- Recommendations
- Progress
- Notifications

Admin views:
- Dashboard
- Resource Library
- User Management
- System Settings

## 14. Accessibility and UX

The UI should:
- use semantic HTML
- provide keyboard navigation
- provide visible focus indicators
- associate labels with inputs
- provide validation messages
- avoid color-only status indicators
- maintain sufficient contrast
- remain usable at mobile browser widths
- expose loading/error/empty states
- preserve clear confirmation for destructive operations

## 15. Performance

- paginate task/resource/notification lists
- eager-load only required relationships
- use database indexes for dashboard/date/status queries
- avoid N+1 queries
- queue email
- cache stable system settings where appropriate
- lazy-load heavy frontend views
- avoid returning hidden/private fields

## 16. Security

Production requirements:
- `APP_DEBUG=false`
- HTTPS
- strong secret keys
- secure environment variables
- least-privilege DB account
- authentication rate limiting
- strict CORS
- secure cookie configuration for SPA auth
- server-side validation
- authorization policies
- transaction boundaries
- sanitized/escaped output
- no secrets in source control
- no demo credentials
- regular backups

## 17. Development commands

```bash
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
npm run build
```

## 18. Seed data

The seeder creates:
- development admin
- development student
- study preferences
- three sample subjects
- five sample topics
- sample learning resources
- recommendation/reminder settings

Demo credentials:
- `admin@studyplanner.test`
- `student@studyplanner.test`

These are for local development only and must be removed/changed before production.

## 19. Files included in the implementation package

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/AuthController.php
│   │   ├── Student/
│   │   └── Admin/ResourceController.php
│   ├── Middleware/RoleMiddleware.php
│   └── Requests/
├── Models/
├── Policies/
└── Services/

database/
├── migrations/
└── seeders/DatabaseSeeder.php

routes/
└── api.php

IMPLEMENTATION_GUIDE.md
README.md
```

## 20. Remaining implementation work

The database/domain layer and core service/API blueprint are defined. The next code layer should implement:
1. complete Subject/Topic controllers and Form Requests
2. study-preference endpoints
3. admin user/role management
4. system-settings endpoints
5. instructor consent + anonymized aggregation after requirements clarification
6. notification classes, jobs and scheduler
7. complete automated tests
8. Vue frontend
9. deployment configuration

The supplied proposal does not contain enough detail to invent the exact instructor consent workflow, detailed UI wireframes, email provider, or hosting topology. Those should be decided during implementation rather than silently added to the requirements.
