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

        $hasPreferences = (bool) $user->studyPreference;
        $subjectsCount = $user->subjects()->count();
        $tasksCount = $user->tasks()->count();
        $hasStudyPlan = $user->studySessions()->exists();

        $steps = [
            'study_preferences' => $hasPreferences,
            'subjects' => $subjectsCount > 0,
            'tasks' => $tasksCount > 0,
            'study_plan' => $hasStudyPlan,
        ];

        $completedSteps = collect($steps)->filter()->count();
        $totalSteps = count($steps);

        $nextAction = null;
        if (!$hasPreferences) {
            $nextAction = 'create_study_preferences';
        } elseif ($subjectsCount === 0) {
            $nextAction = 'create_first_subject';
        } elseif ($tasksCount === 0) {
            $nextAction = 'create_first_task';
        } elseif (!$hasStudyPlan) {
            $nextAction = 'generate_study_plan';
        }

        return response()->json([
            'data' => [
                'progress' => $this->progress->summary($user),
                'recommendations' => $this->recommendations->forStudent($user),
                'upcoming_tasks' => $user->tasks()
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->where('deadline', '>=', now())
                    ->orderBy('deadline')
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
                'onboarding' => [
                    'steps' => $steps,
                    'completed_steps' => $completedSteps,
                    'total_steps' => $totalSteps,
                    'completion_percentage' => round(($completedSteps / $totalSteps) * 100),
                    'next_action' => $nextAction,
                    'is_complete' => $completedSteps === $totalSteps,
                    'counts' => [
                        'subjects' => $subjectsCount,
                        'tasks' => $tasksCount,
                        'study_sessions' => $user->studySessions()->count(),
                    ],
                ],
            ],
            'message' => 'Dashboard data retrieved successfully.',
        ]);
    }
}
