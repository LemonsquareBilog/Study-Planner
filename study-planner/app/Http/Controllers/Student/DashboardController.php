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

        return response()->json([
            'data' => [
                'progress' => $this->progress->summary($user),
                'recommendations' => $this->recommendations->forStudent($user),
                'setup_status' => [
                    'has_preferences' => (bool)$user->studyPreference,
                    'subjects_count' => $user->subjects()->count(),
                    'tasks_count' => $user->tasks()->count(),
                    'study_sessions_count' => $user->studySessions()->count(),
                    'quiz_attempts_count' => $user->quizAttempts()->count(),
                    'is_fully_onboarded' => (bool)($user->studyPreference && $user->subjects()->exists() && $user->tasks()->exists()),
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
