<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class ProgressService
{
    public function summary(User $user): array
    {
        // Keep the response small and use one database round trip regardless
        // of how many tasks, sessions, or attempts the student has recorded.
        $totals = User::query()->whereKey($user->id)
            ->select('id')
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed'),
                'studySessions',
                'studySessions as completed_sessions_count' => fn ($query) => $query->where('status', 'completed'),
                'quizAttempts',
            ])
            ->withSum(['studySessions as completed_minutes' => fn ($query) => $query->where('status', 'completed')], 'duration_minutes')
            ->withAvg('quizAttempts', 'percentage')
            ->firstOrFail();

        $totalTasks = (int) $totals->tasks_count;
        $completedTasks = (int) $totals->completed_tasks_count;

        return [
            'tasks' => [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'completion_rate' => $totalTasks ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            ],
            'study_sessions' => [
                'total' => (int) $totals->study_sessions_count,
                'completed' => (int) $totals->completed_sessions_count,
                'minutes_completed' => (int) $totals->completed_minutes,
            ],
            'quizzes' => [
                'attempts' => (int) $totals->quiz_attempts_count,
                'average_percentage' => $totals->quiz_attempts_count ? round((float) $totals->quiz_attempts_avg_percentage, 2) : 0,
            ],
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
