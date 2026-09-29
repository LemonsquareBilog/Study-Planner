# Complete Implementation Roadmap

## 1. Create project

```bash
composer create-project laravel/laravel study-planner
cd study-planner
composer require laravel/sanctum
npm install
npm install vue pinia vue-router axios
npm install -D @vitejs/plugin-vue typescript vue-tsc tailwindcss
```

Copy the provided `app`, `database`, and `routes` files into the Laravel project.

## 2. Configure database

`.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=study_planner
DB_USERNAME=study_planner
DB_PASSWORD=change-this
```

Use a dedicated database account with only required privileges.

## 3. Register Role Middleware

Laravel 12 applications register middleware aliases in `bootstrap/app.php`.

Add:

```php
->withMiddleware(function ($middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
})
```

## 4. Sanctum

Protect private API routes with `auth:sanctum`. For a first-party SPA, configure Sanctum's stateful SPA authentication according to the chosen deployment topology rather than exposing long-lived tokens unnecessarily.

## 5. Validation and authorization

The database foreign keys provide structural integrity. Laravel services/policies must additionally verify ownership:
- student's subject belongs to student
- topic belongs to selected subject
- task belongs to student
- session references belong to same student
- recommendation resources are active
- instructor aggregate access requires the consent rule defined by the proposal

## 6. Frontend

Recommended structure:

```text
resources/js/
├── app.ts
├── router/
├── stores/
├── services/
│   └── api.ts
├── layouts/
│   ├── StudentLayout.vue
│   └── AdminLayout.vue
├── views/
│   ├── auth/
│   ├── student/
│   └── admin/
└── components/
```

Core views:
- Login
- Registration
- Student Dashboard
- Subjects
- Tasks
- Study Plan
- Quiz
- Recommendations
- Progress
- Notifications
- Admin Resource Library
- Admin Settings

## 7. API response policy

Use consistent JSON:
```json
{
  "data": {},
  "message": "Success"
}
```

Validation errors should use Laravel's standard 422 response. Authorization errors should use 403. Missing records should use 404.

## 8. Study planner rules

The current service:
- prioritizes urgent/high/medium/low tasks
- orders by deadline
- considers estimated minutes
- considers maximum daily minutes
- uses preferred study hours
- uses session duration
- adds breaks
- avoids occupied time slots
- does not schedule after the task deadline

For a production version, add an explicit overlap query before every insert as a second line of defense.

## 9. Recommendation rules

The proposal requires rule-based recommendations. Do not replace this with an ML model.

Recommended classification:
- `< weak_topic_threshold` = Weak
- `weak_topic_threshold .. review_topic_threshold` = Needs Review
- above review threshold = Strong

Resources should be mapped to subjects/topics in the admin library.

## 10. Progress metrics

Student dashboard should calculate:
- task completion rate
- completed study-session minutes
- study-session completion rate
- quiz attempt count
- average quiz percentage
- topic-level performance trends

## 11. Notifications

Use Laravel notifications/jobs:
- create in-app notification immediately
- queue email delivery
- use scheduled jobs/commands to generate upcoming deadline/session reminders
- record sent/failed state

## 12. Testing

Run:
```bash
php artisan test
npm run build
```

Add feature tests for authorization, ownership, validation, quiz scoring, planning, recommendations, and admin-only actions.

## 13. Production checklist

- APP_DEBUG=false
- strong APP_KEY
- HTTPS
- production DB credentials
- queue worker
- scheduler
- mail provider configured
- database backups
- log monitoring
- rate limiting
- CORS restricted to frontend origin
- secure cookies where SPA authentication is used
- remove demo accounts
- replace demo resource URLs
