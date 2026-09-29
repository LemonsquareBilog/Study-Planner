<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class ProgressService
{
    public function summary(User $user): array
    {
        $tasks = $user->tasks()->get();
        $sessions = $user->studySessions()->get();
        $attempts = $user->quizAttempts()->get();

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();

        return [
            'tasks' => [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'completion_rate' => $totalTasks ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            ],
            'study_sessions' => [
                'total' => $sessions->count(),
                'completed' => $sessions->where('status', 'completed')->count(),
                'minutes_completed' => $sessions->where('status', 'completed')->sum('duration_minutes'),
            ],
            'quizzes' => [
                'attempts' => $attempts->count(),
                'average_percentage' => $attempts->count() ? round($attempts->avg('percentage'), 2) : 0,
            ],
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
