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
        public StudySession $session,
        public int $minutesUntil = 15,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->session->subject;
        $topic = $this->session->topic;
        
        return (new MailMessage)
            ->subject("Study Session Starting in {$this->minutesUntil} Minutes")
            ->greeting("Hello!")
            ->line("Your study session for **{$subject->name}** is starting in {$this->minutesUntil} minutes.")
            ->when($topic, function ($message) use ($topic) {
                $message->line("Topic: **{$topic->name}**");
            })
            ->line("Scheduled: **{$this->session->scheduled_date->format('l, F j, Y')}** at **{$this->session->start_time}**")
            ->line("Duration: **{$this->session->duration_minutes} minutes**")
            ->action('View Study Plan', url("/student/study-plan"))
            ->line('Good luck with your study session!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'study_session_reminder',
            'title' => 'Study Session Starting Soon',
            'message' => "Your {$this->session->subject->name} session starts in {$this->minutesUntil} minutes.",
            'session_id' => $this->session->id,
            'scheduled_at' => $this->session->scheduled_date->format('Y-m-d') . ' ' . $this->session->start_time,
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