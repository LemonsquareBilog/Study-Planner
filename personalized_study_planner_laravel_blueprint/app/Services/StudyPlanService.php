<?php

namespace App\Services;

use App\Models\StudyPreference;
use App\Models\StudySession;
use App\Models\Task;
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

        $preferences = $user->studyPreference;
        if (!$preferences) {
            throw ValidationException::withMessages(['study_preferences' => 'Study preferences are required before generating a plan.']);
        }

        $tasks = $user->tasks()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereBetween('deadline', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByRaw("FIELD(priority, 'urgent','high','medium','low')")
            ->orderBy('deadline')
            ->get();

        $createdSessions = [];
        $created = 0;
        $reason = null;

        if ($tasks->isEmpty()) {
            $reason = 'no_tasks_in_range';
        } else {
            DB::transaction(function () use ($user, $preferences, $tasks, $from, $to, &$created, &$createdSessions, &$reason) {
                $tasksWithRemaining = [];
                foreach ($tasks as $task) {
                    $remaining = $task->estimated_minutes - $task->studySessions()
                        ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
                        ->sum('duration_minutes');

                    if ($remaining > 0) {
                        $tasksWithRemaining[] = ['task' => $task, 'remaining' => $remaining];
                    }
                }

                if (empty($tasksWithRemaining)) {
                    $reason = 'all_tasks_fully_scheduled';
                    return;
                }

                $date = $from->copy()->startOfDay();
                while ($date->lte($to) && !empty($tasksWithRemaining)) {
                    $daily = $preferences->max_daily_minutes;
                    $alreadyScheduled = $user->studySessions()
                        ->whereDate('scheduled_date', $date)
                        ->whereIn('status', ['scheduled', 'in_progress'])
                        ->sum('duration_minutes');

                    $available = max(0, $daily - $alreadyScheduled);
                    
                    if ($available > 0) {
                        foreach ($tasksWithRemaining as $index => $taskData) {
                            if ($taskData['remaining'] <= 0) {
                                continue;
                            }
                            
                            if ($date->gte($taskData['task']->deadline)) {
                                continue;
                            }

                            $duration = min($taskData['remaining'], $available, $preferences->session_duration_minutes);
                            $start = $this->findNextSlot($user, $date, $preferences, $duration);
                            
                            if ($start) {
                                $end = $start->copy()->addMinutes($duration);
                                $session = $user->studySessions()->create([
                                    'subject_id' => $taskData['task']->subject_id,
                                    'topic_id' => $taskData['task']->topic_id,
                                    'task_id' => $taskData['task']->id,
                                    'scheduled_date' => $date->toDateString(),
                                    'start_time' => $start->format('H:i:s'),
                                    'end_time' => $end->format('H:i:s'),
                                    'duration_minutes' => $duration,
                                    'status' => 'scheduled',
                                ]);
                                $createdSessions[] = $session;
                                $taskData['remaining'] -= $duration;
                                $available -= $duration;
                                $created++;
                                $tasksWithRemaining[$index] = $taskData;
                                
                                if ($available <= 0) {
                                    break;
                                }
                            }
                        }
                    }
                    $date->addDay();
                }

                $remainingTotal = collect($tasksWithRemaining)->sum('remaining');
                if ($created === 0) {
                    if ($remainingTotal > 0) {
                        $reason = 'no_available_time_slots';
                    } else {
                        $reason = 'all_tasks_fully_scheduled';
                    }
                }
            });
        }

        // Load relationships for the created sessions
        $createdSessions = collect($createdSessions)->load(['subject:id,name,color', 'topic:id,name', 'task:id,title']);

        return [
            'created_sessions' => $created,
            'sessions' => $createdSessions,
            'reason' => $reason,
        ];
    }

    private function findNextSlot(User $user, Carbon $date, StudyPreference $preferences, int $duration): ?Carbon
    {
        $start = $date->copy()->setTimeFromTimeString($preferences->preferred_start_time ?: '18:00');
        $endLimit = $date->copy()->setTimeFromTimeString($preferences->preferred_end_time ?: '22:00');

        // Handle overnight preferences (e.g., 22:00 to 02:00)
        $isOvernight = $start->greaterThan($endLimit);

        $sessions = $user->studySessions()
            ->whereDate('scheduled_date', $date)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('start_time')
            ->get();

        $cursor = $start->copy();
        
        foreach ($sessions as $session) {
            $sessionStart = $date->copy()->setTimeFromTimeString($session->start_time);
            $sessionEnd = $date->copy()->setTimeFromTimeString($session->end_time);
            
            // Handle overnight sessions
            if ($sessionStart->greaterThan($sessionEnd)) {
                $sessionEnd->addDay();
            }
            
            if ($cursor->lt($sessionStart) && $cursor->copy()->addMinutes($duration)->lte($sessionStart)) {
                return $cursor;
            }
            
            if ($cursor->lt($sessionEnd)) {
                $cursor = $sessionEnd->copy()->addMinutes($preferences->break_duration_minutes);
            }
        }

        // Check if we can fit after the last session
        $endCheck = $cursor->copy()->addMinutes($duration);
        if ($isOvernight) {
            if ($endCheck->lte($endLimit->copy()->addDay())) {
                return $cursor;
            }
        } else {
            if ($endCheck->lte($endLimit)) {
                return $cursor;
            }
        }

        return null;
    }
}
