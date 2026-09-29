# Personalized Study Planner - Laravel Backend API

A production-ready Laravel backend for a Vue 3 frontend application. This API implements all 32 UI/UX findings from the audit plus additional production-ready features.

## 🚀 Features Implemented

### Core API Features (32 UI/UX Findings Fixed)

| Category | Features |
|----------|----------|
| **Authentication & Onboarding** | Password rules endpoint, user-friendly validation, 2FA, password reset, profile management, onboarding wizard |
| **Study Planning** | Auto-generation with diagnostic reasons, session status lifecycle, calendar view, pagination |
| **Task Management** | Search, bulk actions, restore, explicit status transitions, same-day deadlines, earliest-first sorting |
| **Quiz System** | Draft/save/resume, correct answers in results, previous attempt tracking, in-progress preservation |
| **Notifications** | Lightweight count endpoint, real-time via WebSocket, email + database + broadcast |
| **Recommendations** | Reason field for empty states, view tracking |
| **Admin Features** | Dashboard with metrics, user/resource management, bulk actions, import/export, system settings |
| **API Consistency** | Standardized responses, resource classes, pagination, search on all lists |

### Production-Ready Features

| Feature | Description |
|---------|-------------|
| **API Versioning** | `/api/v1/` prefix with legacy fallback |
| **Rate Limiting** | Tiered limits (strict/standard/generous/admin) with configurable config |
| **Response Caching** | Configurable TTL per endpoint with tags for invalidation |
| **WebSocket Support** | Pusher/Laravel Echo for real-time notifications |
| **OpenAPI/Swagger** | Auto-generated docs at `/docs/swagger` |
| **Comprehensive Logging** | API request/response logging, audit trails, slow query detection |
| **Database Optimization** | Composite indexes, query performance monitoring |
| **File Upload** | Local/S3 support, temporary signed URLs, bulk import (CSV/JSON) |
| **Email Notifications** | 9 templates, queue support, unsubscribe handling |
| **2FA** | TOTP with QR codes, recovery codes |
| **Health Checks** | `/health`, `/health/ready`, `/health/live` for Kubernetes |
| **Request/Response Transformation** | Base API controller, transformers |

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── ApiController.php          # Base controller with response macros
│   │   ├── ApiDocumentationController.php
│   │   ├── Auth/
│   │   ├── Student/                   # Student endpoints
│   │   ├── Admin/                     # Admin endpoints
│   │   └── BroadcastingController.php
│   ├── Middleware/
│   │   ├── EnsureJsonResponse.php     # Force JSON responses
│   │   ├── RateLimitTier.php          # Tiered rate limiting
│   │   ├── ApiCache.php               # Response caching
│   │   └── ApiRequestLogger.php       # Request/response logging
│   ├── Requests/                      # Form requests with validation
│   ├── Resources/                     # API Resources for consistent serialization
│   └── Transformers/                  # Request/Response transformers
├── Services/
│   ├── StudyPlanService.php           # Core scheduling algorithm
│   ├── QuizService.php                # Quiz logic with draft support
│   ├── NotificationService.php        # Notification orchestration
│   ├── FileUploadService.php          # File handling with S3 support
│   └── RecommendationService.php      # Recommendation engine
├── Events/                            # Real-time events
├── Notifications/                     # 9 notification classes
├── Models/                            # Eloquent models with relationships
├── Policies/                          # Authorization policies
└── Providers/                         # Service providers

config/
├── api-cache.php                      # Caching configuration
├── api-docs.php                       # OpenAPI configuration
├── email-notifications.php            # Email templates
├── logging.php                        # Logging configuration
├── notification.php                   # Notification scheduling
├── ratelimit.php                      # Rate limiting tiers
├── websockets.php                     # WebSocket configuration

routes/
├── api.php                            # Versioned API routes
└── channels.php                       # Broadcasting channels

tests/
├── Feature/
│   ├── StudentApiTest.php
│   ├── Admin/
│   │   └── AdminApiTest.php
│   ├── AuthApiTest.php
│   └── RealtimeTest.php
```

## 🔧 Installation

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=study_planner
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Run seeders (optional)
php artisan db:seed

# Install frontend dependencies (if using Laravel Mix/Vite)
npm install
npm run build

# Start development server
php artisan serve
```

## 🌐 API Endpoints

### Public Auth
```
GET    /api/v1/auth/password-rules
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
```

### Authenticated User
```
POST   /api/v1/auth/logout
GET    /api/v1/auth/me
PUT    /api/v1/auth/me
PUT    /api/v1/auth/me/password
POST   /api/v1/auth/me/2fa/enable
POST   /api/v1/auth/me/2fa/disable
POST   /api/v1/auth/me/2fa/verify
GET    /api/v1/auth/me/2fa/qr
```

### Student Endpoints (require `role:student`)
```
GET    /api/v1/student/dashboard
GET    /api/v1/student/study-preference
POST   /api/v1/student/study-preference
GET    /api/v1/student/study-preference/onboarding
POST   /api/v1/student/study-preference/defaults

GET    /api/v1/student/subjects
POST   /api/v1/student/subjects
GET    /api/v1/student/subjects/{subject}
PUT    /api/v1/student/subjects/{subject}
DELETE /api/v1/student/subjects/{subject}
POST   /api/v1/student/subjects/{subject}/restore

GET    /api/v1/student/subjects/{subject}/topics
POST   /api/v1/student/subjects/{subject}/topics
GET    /api/v1/student/subjects/{subject}/topics/{topic}
PUT    /api/v1/student/subjects/{subject}/topics/{topic}
DELETE /api/v1/student/subjects/{subject}/topics/{topic}
POST   /api/v1/student/subjects/{subject}/topics/{topic}/restore

GET    /api/v1/student/tasks
POST   /api/v1/student/tasks
GET    /api/v1/student/tasks/{task}
PUT    /api/v1/student/tasks/{task}
DELETE /api/v1/student/tasks/{task}
POST   /api/v1/student/tasks/{task}/restore
POST   /api/v1/student/tasks/{task}/complete
PUT    /api/v1/student/tasks/{task}/status
POST   /api/v1/student/tasks/bulk

GET    /api/v1/student/study-plan
POST   /api/v1/student/study-plan/generate
GET    /api/v1/student/study-plan/calendar
PUT    /api/v1/student/study-plan/{studySession}/status

GET    /api/v1/student/quizzes
GET    /api/v1/student/quizzes/{quiz}
POST   /api/v1/student/quizzes/{quiz}/start
POST   /api/v1/student/quizzes/{quiz}/submit
POST   /api/v1/student/quiz-attempts/{attempt}/draft
GET    /api/v1/student/quiz-attempts/{attempt}/resume
GET    /api/v1/student/quiz-attempts

GET    /api/v1/student/recommendations
POST   /api/v1/student/recommendations/{resource}/view

GET    /api/v1/student/progress
GET    /api/v1/student/progress/export

GET    /api/v1/student/notifications
GET    /api/v1/student/notifications/count
POST   /api/v1/student/notifications/read-all
POST   /api/v1/student/notifications/{notification}/read
POST   /api/v1/student/notifications/bulk
```

### Admin Endpoints (require `role:admin`)
```
GET    /api/v1/admin/dashboard
GET    /api/v1/admin/analytics
GET    /api/v1/admin/logs

GET    /api/v1/admin/resources
POST   /api/v1/admin/resources
GET    /api/v1/admin/resources/{resource}
PUT    /api/v1/admin/resources/{resource}
DELETE /api/v1/admin/resources/{resource}
POST   /api/v1/admin/resources/{resource}/restore
POST   /api/v1/admin/resources/bulk
POST   /api/v1/admin/resources/import
POST   /api/v1/admin/resources/{resource}/upload-file
DELETE /api/v1/admin/resources/{resource}/file
GET    /api/v1/admin/resources/{resource}/upload-url

GET    /api/v1/admin/users
GET    /api/v1/admin/users/{user}
PATCH  /api/v1/admin/users/{user}
POST   /api/v1/admin/users/bulk
POST   /api/v1/admin/users/export

GET    /api/v1/admin/system-settings
PATCH  /api/v1/admin/system-settings/{setting}
POST   /api/v1/admin/system-settings/bulk
```

### Health & Documentation
```
GET    /health
GET    /health/ready
GET    /health/live

GET    /docs/openapi
GET    /docs/swagger
GET    /docs/redoc
```

### Broadcasting
```
POST   /api/v1/broadcasting/auth
```

## 📊 Response Format

All endpoints return consistent JSON responses:

### Success
```json
{
  "data": {},
  "message": "Operation successful"
}
```

### Paginated
```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5,
    "from": 1,
    "to": 20,
    "has_more_pages": true
  },
  "message": "Success"
}
```

### Error
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "field": ["Error message"]
    }
  }
}
```

## ⚙️ Configuration

### Environment Variables
```env
# App
APP_URL=http://localhost:8000
APP_VERSION=1.0.0

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=study_planner
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# API Cache
API_CACHE_ENABLED=true
API_CACHE_STORE=redis

# Rate Limiting
RATE_LIMIT_GLOBAL_ENABLED=true

# Logging
LOG_CHANNEL=stack
API_LOG_REQUESTS=true
API_LOG_RESPONSES=true
SLOW_REQUEST_THRESHOLD=1000
API_LOG_STORE_IN_DATABASE=true

# WebSocket
WEBSOCKETS_ENABLED=true
WEBSOCKETS_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

# Email
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@studyplanner.example.com
MAIL_FROM_NAME="Study Planner"

# Notifications
NOTIFICATION_QUEUE=notifications
EMAIL_NOTIFICATIONS_ENABLED=true

# API Docs
API_DOCS_ENABLED=true
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=StudentApiTest
php artisan test --filter=AdminApiTest
php artisan test --filter=AuthApiTest
php artisan test --filter=RealtimeTest

# With coverage
php artisan test --coverage
```

## 🚀 Deployment

### Docker
```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev zip unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

EXPOSE 9000
CMD ["php-fpm"]
```

### Kubernetes Health Checks
```yaml
livenessProbe:
  httpGet:
    path: /health/live
    port: 8000
  initialDelaySeconds: 30
  periodSeconds: 10

readinessProbe:
  httpGet:
    path: /health/ready
    port: 8000
  initialDelaySeconds: 10
  periodSeconds: 5
```

## 📝 Documentation

- **Navigation IA**: `NAVIGATION_IA.md` - Complete navigation hierarchy for frontend
- **Accessibility Foundation**: `ACCESSIBILITY_FOUNDATION.md` - WCAG 2.1 AA compliance guide
- **API Docs**: Available at `/docs/swagger` when `API_DOCS_ENABLED=true`

## 🔐 Security

- All API endpoints return JSON (no HTML error pages)
- Rate limiting on all endpoints
- Password requirements enforced (8 chars, uppercase, lowercase, number, symbol)
- 2FA with TOTP and recovery codes
- Soft deletes with restore capability
- Audit logging for sensitive actions
- CORS configured for frontend domain
- Sanctum token authentication

## 📈 Monitoring

- **API Logs**: `storage/logs/api.log`
- **Audit Logs**: `storage/logs/audit.log`
- **Slow Queries**: `storage/logs/slow-queries.log`
- **API Request Logs**: Database table `api_request_logs`
- **Health Checks**: `/health`, `/health/ready`, `/health/live`

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.