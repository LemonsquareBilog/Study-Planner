<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ProgressService;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private ProgressService $progress,
        private RecommendationService $recommendations,
    ) {}

    /**
     * Return the student dashboard data: progress summary + resource recommendations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $progress = $this->progress->summary($user);
        $subjectsCount = $user->subjects()->count();
        $hasPreferences = $user->studyPreference()->exists();

        return response()->json([
            'data' => [
                'progress' => $progress,
                'recommendations' => $this->recommendations->forStudent($user),
                'setup_status' => [
                    'has_preferences' => $hasPreferences,
                    'subjects_count' => $subjectsCount,
                    'tasks_count' => $progress['tasks']['total'],
                    'study_sessions_count' => $progress['study_sessions']['total'],
                    'quiz_attempts_count' => $progress['quizzes']['attempts'],
                    'is_fully_onboarded' => $hasPreferences && $subjectsCount > 0 && $progress['tasks']['total'] > 0,
                ],
                'upcoming_tasks' => $user->tasks()
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->where('deadline', '>=', now())
                    ->orderBy('deadline', 'asc')
                    ->limit(5)
                    ->with(['subject:id,name,color'])
                    ->get(),
                'upcoming_sessions' => $user->studySessions()
                    ->where('scheduled_date', '>=', now()->toDateString())
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_date')
                    ->orderBy('start_time')
                    ->limit(5)
                    ->with(['subject:id,name,color', 'task:id,title'])
                    ->get(),
                'unread_notifications' => $user->notifications()
                    ->whereNull('read_at')
                    ->count(),
            ],
            'message' => 'Dashboard data retrieved successfully.',
        ]);
    }
}
