<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Resource;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Admin dashboard with system metrics.
     */
    public function adminDashboard(Request $request): JsonResponse
    {
        $totalUsers = User::count();
        $totalStudents = User::where('role', 'student')->count();
        $totalAdmins = User::where('role', 'admin')->count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();

        $totalResources = Resource::count();
        $activeResources = Resource::where('is_active', true)->count();

        $totalSubjects = Subject::count();
        $totalTasks = Task::count();
        $completedTasks = Task::where('status', 'completed')->count();
        $pendingTasks = Task::whereIn('status', ['pending', 'in_progress'])->count();

        $totalQuizAttempts = QuizAttempt::count();
        $avgQuizScore = QuizAttempt::avg('percentage') ?? 0;

        $recentRegistrations = User::where('role', 'student')
            ->latest('created_at')
            ->limit(10)
            ->get(['id', 'name', 'email', 'created_at']);

        return response()->json([
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'students' => $totalStudents,
                    'admins' => $totalAdmins,
                    'active' => $activeUsers,
                    'inactive' => $inactiveUsers,
                ],
                'resources' => [
                    'total' => $totalResources,
                    'active' => $activeResources,
                ],
                'subjects' => [
                    'total' => $totalSubjects,
                ],
                'tasks' => [
                    'total' => $totalTasks,
                    'completed' => $completedTasks,
                    'pending' => $pendingTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                ],
                'quizzes' => [
                    'total_attempts' => $totalQuizAttempts,
                    'average_score' => round($avgQuizScore, 2),
                ],
                'recent_registrations' => $recentRegistrations,
            ],
            'message' => 'Admin dashboard data retrieved successfully.',
        ]);
    }
}