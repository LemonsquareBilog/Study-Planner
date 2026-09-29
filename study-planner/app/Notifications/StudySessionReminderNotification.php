<?php

namespace App\Notifications;

use App\Models\StudySession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudySessionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private StudySession $session,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $startTime = $this->session->scheduled_date->format('M d, Y') . ' at ' . $this->session->start_time;

        return (new MailMessage())
            ->subject("Study Session Reminder: {$this->session->subject->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You have a study session coming up!")
            ->line("Subject: {$this->session->subject->name}")
            ->when($this->session->topic, fn ($mail) => $mail->line("Topic: {$this->session->topic->name}"))
            ->line("Scheduled: {$startTime}")
            ->line("Duration: {$this->session->duration_minutes} minutes")
            ->action('View Your Study Plan', config('app.frontend_url', config('app.url')) . '/student/study-plan')
            ->line('Stay focused and keep learning!');
    }
}
