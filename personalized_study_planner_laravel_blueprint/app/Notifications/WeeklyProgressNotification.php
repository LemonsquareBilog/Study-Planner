<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyProgressNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $stats,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tasksCompleted = $this->stats['tasks_completed'] ?? 0;
        $studyMinutes = $this->stats['study_minutes'] ?? 0;
        $quizzesTaken = $this->stats['quizzes_taken'] ?? 0;
        $avgScore = $this->stats['avg_quiz_score'] ?? 0;

        return (new MailMessage)
            ->subject('Your Weekly Progress Report')
            ->greeting("Hello!")
            ->line("Here's your progress summary for this week:")
            ->line("✅ **Tasks Completed:** {$tasksCompleted}")
            ->line("📚 **Study Time:** {$studyMinutes} minutes")
            ->line("📝 **Quizzes Taken:** {$quizzesTaken}")
            ->line("📊 **Average Quiz Score:** {$avgScore}%")
            ->action('View Full Progress', url('/student/progress'))
            ->line('Keep up the great work!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'weekly_progress',
            'title' => 'Weekly Progress Report',
            'message' => "This week: {$this->stats['tasks_completed']} tasks, {$this->stats['study_minutes']} min study, {$this->stats['avg_quiz_score']}% avg quiz score.",
            'stats' => $this->stats,
            'action_url' => '/student/progress',
        ];
    }
}