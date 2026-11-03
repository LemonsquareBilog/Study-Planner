<?php

namespace App\Services;

use App\Models\StudyPreference;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudyPlanService
{
    public function generate(User $user, Carbon $from, Carbon $to): array
    {
        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages(['date_range' => 'The start date must not be after the end date.']);
        }

        // Auto-provision default preferences if not yet configured (UX-002)
        $preferences = $user->studyPreference ?: $user->studyPreference()->create([
            'max_daily_minutes' => 120,
            'session_duration_minutes' => 50,
            'break_duration_minutes' => 10,
            'preferred_start_time' => '18:00',
            'preferred_end_time' => '22:00',
        ]);

        $hasPendingTasks = $user->tasks()->where('deadline', '>', now())->whereIn('status', ['pending', 'in_progress'])->exists();
        if (! $hasPendingTasks) {
            return [
                'created_sessions_count' => 0,
                'created_sessions' => [],
                'reason' => 'no_pending_tasks',
                'message' => 'You do not have any pending tasks. Create tasks first to generate a personalized study plan.',
            ];
        }

        $tasks = $user->tasks()->where('deadline', '>', now())
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereBetween('deadline', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('deadline')
            ->get();

        if ($tasks->isEmpty()) {
            return [
                'created_sessions_count' => 0,
                'created_sessions' => [],
                'reason' => 'no_tasks_in_range',
                'message' => "No pending tasks have deadlines between {$from->format('M d')} and {$to->format('M d')}. Try broadening your date range or add tasks due within this period.",
            ];
        }

        $createdSessions = [];

        DB::transaction(function () use ($user, $preferences, $tasks, $from, $to, &$createdSessions) {
            foreach ($tasks as $task) {
                $remaining = $task->estimated_minutes - $task->studySessions()
                    ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
                    ->sum('duration_minutes');

                if ($remaining <= 0) {
                    continue;
                }

                $date = $from->copy()->startOfDay();
                $availableFrom = max(
                    $date->timestamp,
                    Carbon::today()->timestamp,
                    $task->start_date?->copy()->startOfDay()->timestamp ?? 0,
                    $task->created_at->copy()->startOfDay()->timestamp,
                );
                $date = Carbon::createFromTimestamp($availableFrom, $date->timezone)->startOfDay();
                while ($remaining > 0 && $date->lte($to) && $date->lt($task->deadline)) {
                    $daily = $preferences->max_daily_minutes;
                    $alreadyScheduled = $user->studySessions()
                        ->whereDate('scheduled_date', $date)
                        ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
                        ->sum('duration_minutes');

                    $available = max(0, $daily - $alreadyScheduled);
                    if ($available > 0) {
                        $duration = min($remaining, $available, $preferences->session_duration_minutes);
                        $start = $this->findNextSlot($user, $date, $preferences, $duration);
                        if ($start) {
                            $end = $start->copy()->addMinutes($duration);
                            $session = $user->studySessions()->create([
                                'subject_id' => $task->subject_id,
                                'topic_id' => $task->topic_id,
                                'task_id' => $task->id,
                                'scheduled_date' => $date->toDateString(),
                                'start_time' => $start->format('H:i:s'),
                                'end_time' => $end->format('H:i:s'),
                                'duration_minutes' => $duration,
                                'status' => 'scheduled',
                            ]);
                            $remaining -= $duration;
                            $createdSessions[] = $session->load(['subject:id,name,color', 'topic:id,name', 'task:id,title']);
                        }
                    }
                    $date->addDay();
                }
            }
        });

        $count = count($createdSessions);
        $reason = $count > 0 ? 'success' : 'all_tasks_already_scheduled';
        $message = $count > 0
            ? "{$count} study session(s) scheduled successfully."
            : 'All tasks in this date range already have sufficient study time scheduled.';

        return [
            'created_sessions_count' => $count,
            'created_sessions' => $createdSessions,
            'reason' => $reason,
            'message' => $message,
        ];
    }

    private function findNextSlot(User $user, Carbon $date, StudyPreference $preferences, int $duration): ?Carbon
    {
        $start = $date->copy()->setTimeFromTimeString($preferences->preferred_start_time ?: '18:00');
        $endLimit = $date->copy()->setTimeFromTimeString($preferences->preferred_end_time ?: '22:00');

        // Handle overnight study preferences (e.g. 22:00 to 02:00) (UX-016)
        if ($endLimit->lte($start)) {
            $endLimit->addDay();
        }

        $sessions = $user->studySessions()
            ->whereDate('scheduled_date', $date)
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->orderBy('start_time')
            ->get();

        $cursor = $start->copy();
        foreach ($sessions as $session) {
            $sessionStart = $date->copy()->setTimeFromTimeString($session->start_time);
            $sessionEnd = $date->copy()->setTimeFromTimeString($session->end_time);
            if ($cursor->lt($sessionStart) && $cursor->copy()->addMinutes($duration)->lte($sessionStart)) {
                return $cursor;
            }
            if ($cursor->lt($sessionEnd)) {
                $cursor = $sessionEnd->copy()->addMinutes($preferences->break_duration_minutes);
            }
        }

        return $cursor->copy()->addMinutes($duration)->lte($endLimit) ? $cursor : null;
    }
}
