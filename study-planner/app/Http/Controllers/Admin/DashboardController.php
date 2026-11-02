<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Return high-level administrative platform metrics and health indicators (UX-026).
     */
    public function index(Request $request): JsonResponse
    {
        $students = User::where('role', 'student')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_active THEN 1 ELSE 0 END) as active')
            ->first();
        $resourcesCount = Resource::count();
        $subjectsCount = Subject::count();
        $quizzesCount = Quiz::count();
        $attempts = QuizAttempt::query()
            ->selectRaw('COUNT(*) as total, AVG(percentage) as average_score')
            ->first();

        $recentUsers = User::latest()
            ->limit(5)
            ->get(['id', 'name', 'email', 'role', 'course', 'year_level', 'is_active', 'created_at']);

        $recentAttempts = QuizAttempt::with(['user:id,name,email', 'quiz:id,title'])
            ->latest('completed_at')
            ->limit(5)
            ->get(['id', 'user_id', 'quiz_id', 'score', 'total_questions', 'percentage', 'completed_at']);

        return response()->json([
            'data' => [
                'metrics' => [
                    'total_students' => (int) $students->total,
                    'active_students' => (int) $students->active,
                    'total_resources' => $resourcesCount,
                    'total_subjects' => $subjectsCount,
                    'total_quizzes' => $quizzesCount,
                    'total_quiz_attempts' => (int) $attempts->total,
                    'average_quiz_score' => round($attempts->average_score ?? 0, 1),
                ],
                'recent_users' => $recentUsers,
                'recent_attempts' => $recentAttempts,
            ],
            'message' => 'Admin dashboard metrics retrieved successfully.',
        ]);
    }
}
