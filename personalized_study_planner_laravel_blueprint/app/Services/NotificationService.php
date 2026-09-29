<?php

namespace App\Services;

use App\Models\Notification as NotificationModel;
use App\Models\User;
use App\Models\StudySession;
use App\Models\Task;
use App\Models\QuizAttempt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use App\Notifications\WelcomeNotification;
use App\Notifications\StudySessionReminderNotification;
use App\Notifications\TaskDeadlineApproachingNotification;
use App\Notifications\QuizCompletedNotification;
use App\Notifications\StudyPlanGeneratedNotification;
use App\Notifications\WeeklyProgressNotification;
use App\Notifications\AdminUserRegisteredNotification;
use App\Notifications\AdminSystemAlertNotification;

class NotificationService
{
    /**
     * Create a single in-app notification for a user.
     */
    public function create(User $user, string $type, string $title, string $message, $scheduledAt = null): NotificationModel
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'scheduled_at' => $scheduledAt,
            'delivery_status' => 'pending',
        ]);
    }

    /**
     * Mark a notification as sent (e.g., after email delivery).
     */
    public function markAsSent(NotificationModel $notification): void
    {
        $notification->update([
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark a notification as failed (e.g., email delivery failure).
     */
    public function markAsFailed(NotificationModel $notification): void
    {
        $notification->update([
            'delivery_status' => 'failed',
        ]);
    }

    /**
     * Bulk-create notifications for multiple users (e.g., scheduled reminders).
     */
    public function createBulk(array $notifications): int
    {
        $count = 0;
        foreach ($notifications as $entry) {
            $user = $entry['user'];
            $this->create(
                $user,
                $entry['type'],
                $entry['title'],
                $entry['message'],
                $entry['scheduled_at'] ?? null,
            );
            $count++;
        }
        return $count;
    }

    /**
     * Send welcome notification to new user.
     */
    public function sendWelcome(User $user): void
    {
        $loginUrl = config('app.frontend_url', config('app.url')) . '/login';
        
        NotificationFacade::send($user, new WelcomeNotification(
            $user->name,
            $loginUrl
        ));
    }

    /**
     * Send study session reminder.
     */
    public function sendStudySessionReminder(StudySession $session, int $minutesUntil = 15): void
    {
        NotificationFacade::send($session->user, new StudySessionReminderNotification(
            $session,
            $minutesUntil
        ));
    }

    /**
     * Send task deadline approaching notification.
     */
    public function sendTaskDeadlineApproaching(Task $task, int $hoursUntil = 24): void
    {
        NotificationFacade::send($task->user, new TaskDeadlineApproachingNotification(
            $task,
            $hoursUntil
        ));
    }

    /**
     * Send quiz completed notification.
     */
    public function sendQuizCompleted(QuizAttempt $attempt): void
    {
        NotificationFacade::send($attempt->user, new QuizCompletedNotification($attempt));
    }

    /**
     * Send study plan generated notification.
     */
    public function sendStudyPlanGenerated(User $user, int $sessionCount, string $dateRange): void
    {
        NotificationFacade::send($user, new StudyPlanGeneratedNotification(
            $sessionCount,
            $dateRange
        ));
    }

    /**
     * Send weekly progress notification.
     */
    public function sendWeeklyProgress(User $user, array $stats): void
    {
        NotificationFacade::send($user, new WeeklyProgressNotification($stats));
    }

    /**
     * Send admin user registered notification.
     */
    public function sendAdminUserRegistered(User $newUser): void
    {
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        
        NotificationFacade::send($admins, new AdminUserRegisteredNotification($newUser));
    }

    /**
     * Send admin system alert.
     */
    public function sendAdminSystemAlert(string $alertType, string $message, array $details = []): void
    {
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        
        NotificationFacade::send($admins, new AdminSystemAlertNotification(
            $alertType,
            $message,
            $details
        ));
    }

    /**
     * Schedule study session reminders for upcoming sessions.
     */
    public function scheduleStudySessionReminders(): int
    {
        $now = now();
        $reminderTimes = config('notification.study_session_reminders', [1440, 60, 15]); // 24h, 1h, 15min
        
        $count = 0;
        
        foreach ($reminderTimes as $minutes) {
            $targetTime = $now->copy()->addMinutes($minutes);
            $targetDate = $targetTime->toDateString();
            $targetTimeString = $targetTime->format('H:i:s');
            
            $sessions = StudySession::where('status', 'scheduled')
                ->where('scheduled_date', $targetDate)
                ->where('start_time', $targetTimeString)
                ->with('user')
                ->get();
            
            foreach ($sessions as $session) {
                $this->sendStudySessionReminder($session, $minutes);
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Schedule task deadline reminders.
     */
    public function scheduleTaskDeadlineReminders(): int
    {
        $now = now();
        $reminderHours = config('notification.task_deadline_reminders', [168, 24, 1]); // 1 week, 24h, 1h
        
        $count = 0;
        
        foreach ($reminderHours as $hours) {
            $targetTime = $now->copy()->addHours($hours);
            
            $tasks = Task::whereIn('status', ['pending', 'in_progress'])
                ->where('deadline', '>=', $targetTime->startOfMinute())
                ->where('deadline', '<=', $targetTime->endOfMinute())
                ->with('user', 'subject')
                ->get();
            
            foreach ($tasks as $task) {
                $this->sendTaskDeadlineApproaching($task, $hours);
                $count++;
            }
        }
        
        return $count;
    }
}