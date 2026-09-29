<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hoursLeft = now()->diffInHours($this->task->deadline, false);

        return (new MailMessage())
            ->subject("Deadline Reminder: {$this->task->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your task \"{$this->task->title}\" is due in approximately {$hoursLeft} hours.")
            ->line("Deadline: {$this->task->deadline->format('M d, Y g:i A')}")
            ->line("Priority: " . ucfirst($this->task->priority))
            ->line("Subject: {$this->task->subject->name}")
            ->action('View Your Tasks', config('app.frontend_url', config('app.url')) . '/student/tasks')
            ->line('Stay on track with your study plan!');
    }
}
