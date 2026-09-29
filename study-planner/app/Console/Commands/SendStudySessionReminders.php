<?php

namespace App\Console\Commands;

use App\Models\StudySession;
use App\Models\SystemSetting;
use App\Notifications\StudySessionReminderNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendStudySessionReminders extends Command
{
    protected $signature = 'reminders:study-sessions';
    protected $description = 'Send reminder notifications for upcoming study sessions.';

    public function __construct(private NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $minutesAhead = (int) (SystemSetting::where('key', 'study_session_reminder_minutes')->value('value') ?? 30);

        $now = now();
        $cutoffTime = $now->copy()->addMinutes($minutesAhead);

        // Find scheduled sessions starting within the reminder window
        $sessions = StudySession::query()
            ->where('status', 'scheduled')
            ->where('scheduled_date', $now->toDateString())
            ->where('start_time', '>=', $now->format('H:i:s'))
            ->where('start_time', '<=', $cutoffTime->format('H:i:s'))
            ->with(['user', 'subject', 'topic'])
            ->get();

        $sent = 0;

        foreach ($sessions as $session) {
            // Idempotency: check if a study_session_reminder was already created
            $alreadyNotified = $session->user->notifications()
                ->where('type', 'study_session_reminder')
                ->where('message', 'like', "%{$session->start_time}%")
                ->where('created_at', '>=', $now->copy()->subMinutes($minutesAhead))
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $topicName = $session->topic?->name ? " ({$session->topic->name})" : '';

            // Create in-app notification
            $this->notificationService->create(
                $session->user,
                'study_session_reminder',
                "Study session starting soon: {$session->subject->name}",
                "Your study session for {$session->subject->name}{$topicName} starts at {$session->start_time}. Duration: {$session->duration_minutes} minutes.",
            );

            // Queue email notification
            $session->user->notify(new StudySessionReminderNotification($session));

            $sent++;
        }

        $this->info("Sent {$sent} study session reminder(s).");

        return self::SUCCESS;
    }
}
