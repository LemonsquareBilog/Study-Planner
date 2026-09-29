<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth profile & logout
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'update']);
        Route::put('/me/password', [AuthController::class, 'updatePassword']);
        Route::post('/me/2fa/enable', [AuthController::class, 'enable2FA']);
        Route::post('/me/2fa/disable', [AuthController::class, 'disable2FA']);
        Route::post('/me/2fa/verify', [AuthController::class, 'verify2FA']);
        Route::get('/me/2fa/qr', [AuthController::class, 'get2FAQR']);
    });

    // Student Endpoints
    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        
        // Study Preferences (Upsert)
        Route::get('/study-preference', [StudyPreferenceController::class, 'show']);
        Route::post('/study-preference', [StudyPreferenceController::class, 'upsert']);
        Route::get('/study-preference/onboarding', [StudyPreferenceController::class, 'onboardingStatus']);
        Route::post('/study-preference/defaults', [StudyPreferenceController::class, 'createDefaults']);

        // Subjects & Nested Topics
        Route::apiResource('/subjects', SubjectController::class);
        Route::post('/subjects/{subject}/restore', [SubjectController::class, 'restore']);
        Route::apiResource('/subjects.topics', TopicController::class);
        Route::post('/subjects/{subject}/topics/{topic}/restore', [TopicController::class, 'restore']);

        // Tasks
        Route::apiResource('/tasks', TaskController::class);
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
        Route::post('/tasks/{task}/restore', [TaskController::class, 'restore']);
        Route::put('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

        // Study Plans
        Route::get('/study-plan', [StudyPlanController::class, 'index']);
        Route::post('/study-plan/generate', [StudyPlanController::class, 'generate']);
        Route::put('/study-plan/{studySession}/status', [StudyPlanController::class, 'updateStatus']);
        Route::get('/study-plan/calendar', [StudyPlanController::class, 'calendar']);

        // Quizzes
        Route::get('/quizzes', [QuizController::class, 'index']);
        Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
        Route::post('/quizzes/{quiz}/start', [QuizController::class, 'start']);
        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit']);
        Route::post('/quiz-attempts/{attempt}/draft', [QuizController::class, 'saveDraft']);
        Route::get('/quiz-attempts/{attempt}/resume', [QuizController::class, 'resume']);

        // Resource Recommendations & View Tracking
        Route::get('/recommendations', [RecommendationController::class, 'index']);
        Route::post('/recommendations/{resource}/view', [RecommendationController::class, 'recordView']);

        // Detailed Progress & Performance Analytics
        Route::get('/progress', [ProgressController::class, 'index']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/count', [NotificationController::class, 'count']);
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);
    });

    // Admin Endpoints
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::apiResource('/resources', ResourceController::class);
        Route::post('/resources/{resource}/restore', [ResourceController::class, 'restore']);
        Route::post('/resources/bulk', [ResourceController::class, 'bulkAction']);
        Route::post('/resources/import', [ResourceController::class, 'import']);
        
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::post('/users/bulk', [UserController::class, 'bulkAction']);
        Route::post('/users/export', [UserController::class, 'export']);
        
        Route::get('/system-settings', [SystemSettingController::class, 'index']);
        Route::patch('/system-settings/{setting}', [SystemSettingController::class, 'update']);
        Route::post('/system-settings/bulk', [SystemSettingController::class, 'bulkAction']);
        
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard']);
        Route::get('/analytics', [DashboardController::class, 'analytics']);
        Route::get('/logs', [DashboardController::class, 'logs']);
    });
});

// Broadcasting Authorization Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/broadcasting/auth', [BroadcastingController::class, 'authorize'])->name('broadcasting.auth');
});

// Legacy routes redirect notice
Route::fallback(function () {
    return response()->json([
        'error' => [
            'code' => 'ROUTE_NOT_FOUND',
            'message' => 'API endpoint not found. Please use /api/v1/ prefix.',
            'details' => [],
        ],
    ], 404);
});