<?php

namespace App\Notifications;

use App\Models\StudySession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudyPlanGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $sessionCount,
        public string $dateRange,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your New Study Plan is Ready ({$this->sessionCount} sessions)")
            ->greeting("Hello!")
            ->line("Your personalized study plan has been generated with **{$this->sessionCount} study sessions** for {$this->dateRange}.")
            ->line("Your plan includes sessions optimized for your preferred study hours and task priorities.")
            ->action('View Study Plan', url('/student/study-plan'))
            ->line('Happy studying!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'study_plan_generated',
            'title' => 'Study Plan Generated',
            'message' => "Your study plan with {$this->sessionCount} sessions for {$this->dateRange} is ready.",
            'session_count' => $this->sessionCount,
            'date_range' => $this->dateRange,
            'action_url' => '/student/study-plan',
        ];
    }

    public function broadcastOn(object $notifiable): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel("user.{$notifiable->id}"),
        ];
    }
}