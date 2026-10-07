<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\SubjectController;
use App\Http\Controllers\Student\TopicController;
use App\Http\Controllers\Student\TaskController;
use App\Http\Controllers\Student\StudyPlanController;
use App\Http\Controllers\Student\QuizController;
use App\Http\Controllers\Student\RecommendationController;
use App\Http\Controllers\Student\ProgressController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\StudyPreferenceController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SystemSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth profile & logout
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
    });

    // Student Endpoints
    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        
        // Study Preferences (Upsert)
        Route::get('/study-preference', [StudyPreferenceController::class, 'show']);
        Route::post('/study-preference', [StudyPreferenceController::class, 'upsert']);

        // Subjects & Nested Topics
        Route::apiResource('/subjects', SubjectController::class);
        Route::apiResource('/subjects.topics', TopicController::class);

        // Tasks
        Route::apiResource('/tasks', TaskController::class);
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

        // Study Plans
        Route::get('/study-plan', [StudyPlanController::class, 'index']);
        Route::post('/study-plan/generate', [StudyPlanController::class, 'generate']);
        Route::patch('/study-plan/{studySession}/status', [StudyPlanController::class, 'updateStatus']);

        // Quizzes
        Route::get('/quizzes', [QuizController::class, 'index']);
        Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit']);
        Route::get('/quiz-attempts', [QuizController::class, 'history']);
        Route::get('/quiz-attempts/{attempt}', [QuizController::class, 'attempt']);

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
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index']);
        Route::get('/subjects/options', [AdminSubjectController::class, 'options']);
        Route::apiResource('/subjects', AdminSubjectController::class)->except(['show']);
        Route::post('/subjects/{subject}/topics', [AdminSubjectController::class, 'storeTopic']);
        Route::patch('/subjects/{subject}/topics/{topic}', [AdminSubjectController::class, 'updateTopic']);
        Route::delete('/subjects/{subject}/topics/{topic}', [AdminSubjectController::class, 'destroyTopic']);
        Route::get('/resource-options', [ResourceController::class, 'options']);
        Route::apiResource('/resources', ResourceController::class);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::get('/system-settings', [SystemSettingController::class, 'index']);
        Route::patch('/system-settings/{setting}', [SystemSettingController::class, 'update']);
    });
});
