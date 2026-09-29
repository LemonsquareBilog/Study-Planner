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
        $studentsCount = User::where('role', 'student')->count();
        $activeStudentsCount = User::where('role', 'student')->where('is_active', true)->count();
        $resourcesCount = Resource::count();
        $subjectsCount = Subject::count();
        $quizzesCount = Quiz::count();
        $quizAttemptsCount = QuizAttempt::count();
        $avgScore = round(QuizAttempt::avg('percentage') ?? 0, 1);

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
                    'total_students' => $studentsCount,
                    'active_students' => $activeStudentsCount,
                    'total_resources' => $resourcesCount,
                    'total_subjects' => $subjectsCount,
                    'total_quizzes' => $quizzesCount,
                    'total_quiz_attempts' => $quizAttemptsCount,
                    'average_quiz_score' => $avgScore,
                ],
                'recent_users' => $recentUsers,
                'recent_attempts' => $recentAttempts,
            ],
            'message' => 'Admin dashboard metrics retrieved successfully.',
        ]);
    }
}
