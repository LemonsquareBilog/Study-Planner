<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class ProgressService
{
    public function summary(User $user): array
    {
        $tasks = $user->tasks()
            ->selectRaw("COUNT(*) AS total, COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed")
            ->first();
        $sessions = $user->studySessions()
            ->selectRaw("COUNT(*) AS total, COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed, COALESCE(SUM(CASE WHEN status = 'completed' THEN duration_minutes ELSE 0 END), 0) AS minutes_completed")
            ->first();
        $attempts = $user->quizAttempts()
            ->selectRaw('COUNT(*) AS total, AVG(percentage) AS average_percentage')
            ->first();

        $totalTasks = (int) $tasks->total;
        $completedTasks = (int) $tasks->completed;
        $totalAttempts = (int) $attempts->total;

        return [
            'tasks' => [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'completion_rate' => $totalTasks ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            ],
            'study_sessions' => [
                'total' => (int) $sessions->total,
                'completed' => (int) $sessions->completed,
                'minutes_completed' => (int) $sessions->minutes_completed,
            ],
            'quizzes' => [
                'attempts' => $totalAttempts,
                'average_percentage' => $totalAttempts ? round((float) $attempts->average_percentage, 2) : 0,
            ],
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
