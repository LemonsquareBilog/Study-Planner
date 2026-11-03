<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Task;
use Illuminate\Support\Str;

class TaskNotificationService
{
    public function synchronize(Task $task, bool $created = false): void
    {
        $now = now();
        Notification::where('task_id', $task->id)->where('scheduled_at', '>', $now)
            ->whereNull('cancelled_at')->update(['cancelled_at' => $now]);
        if ($task->trashed() || $task->status === 'cancelled') {
            return;
        }
        $events = $created ? ['created' => $now] : [];
        if ($task->deadline->gt($now)) {
            foreach (config('task_reminders.hours', [24, 1]) as $hours) {
                $at = $task->deadline->copy()->subHours($hours);
                if ($at->gt($now) && $task->status !== 'completed') {
                    $events['before_'.$hours] = $at;
                }
            }
            $events['deadline'] = $task->deadline;
            $events['expired'] = $task->deadline->copy()->addSecond();
        }
        foreach ($events as $event => $at) {
            $key = $task->id.':'.$task->deadline->timestamp.':'.$event;
            $label = match ($event) {
                'created' => 'Task successfully added',
                'deadline' => 'Task deadline reached',
                'expired' => $task->status === 'completed' ? 'Completed task archived' : 'Task overdue',
                default => 'Upcoming task reminder',
            };
            $notification = Notification::firstOrCreate(['event_key' => $key], [
                'id' => (string) Str::uuid(), 'user_id' => $task->user_id, 'task_id' => $task->id,
                'type' => 'task_reminder', 'title' => mb_substr($task->title, 0, 160).' - '.$label,
                'message' => 'Task: '.$task->title.'. Deadline: '.$task->deadline->toIso8601String(),
                'scheduled_at' => $at, 'delivery_status' => 'pending',
            ]);
            $notification->update(['cancelled_at' => null, 'title' => mb_substr($task->title, 0, 160).' - '.$label, 'message' => 'Task: '.$task->title.'. Deadline: '.$task->deadline->toIso8601String()]);
        }
    }
}
