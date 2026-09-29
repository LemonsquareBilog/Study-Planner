<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use App\Models\Task;
use App\Notifications\DeadlineReminderNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    protected $signature = 'reminders:deadlines';
    protected $description = 'Send reminder notifications for tasks with approaching deadlines.';

    public function __construct(private NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $hoursAhead = (int) (SystemSetting::where('key', 'deadline_reminder_hours')->value('value') ?? 24);

        $cutoff = now()->addHours($hoursAhead);

        // Find tasks that:
        // 1. Are not yet completed/cancelled
        // 2. Have a deadline within the reminder window
        // 3. Haven't already been reminded (check notifications table)
        $tasks = Task::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereBetween('deadline', [now(), $cutoff])
            ->with(['user', 'subject'])
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            // Idempotency: check if a deadline_reminder was already created for this task
            $alreadyNotified = $task->user->notifications()
                ->where('type', 'deadline_reminder')
                ->where('title', 'like', "%{$task->title}%")
                ->where('created_at', '>=', now()->subHours($hoursAhead))
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            // Create in-app notification
            $this->notificationService->create(
                $task->user,
                'deadline_reminder',
                "Deadline approaching: {$task->title}",
                "Your task \"{$task->title}\" for {$task->subject->name} is due on {$task->deadline->format('M d, Y g:i A')}.",
            );

            // Queue email notification
            $task->user->notify(new DeadlineReminderNotification($task));

            $sent++;
        }

        $this->info("Sent {$sent} deadline reminder(s).");

        return self::SUCCESS;
    }
}
