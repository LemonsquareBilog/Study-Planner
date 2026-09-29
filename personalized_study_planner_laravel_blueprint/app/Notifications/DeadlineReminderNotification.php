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
        public Task $task,
        public int $hoursUntil = 24,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Deadline Reminder: {$this->task->title} (in {$this->hoursUntil} hours)")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your task \"{$this->task->title}\" is due in {$this->hoursUntil} hours.")
            ->line("Deadline: {$this->task->deadline->format('M d, Y g:i A')}")
            ->line("Priority: " . ucfirst($this->task->priority))
            ->line("Subject: {$this->task->subject->name}")
            ->action('View Your Tasks', config('app.frontend_url', config('app.url')) . '/student/tasks')
            ->line('Stay on track with your study plan!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_deadline_approaching',
            'title' => 'Task Deadline Approaching',
            'message' => "Your task \"{$this->task->title}\" is due in {$this->hoursUntil} hours.",
            'task_id' => $this->task->id,
            'deadline' => $this->task->deadline->toIso8601String(),
            'priority' => $this->task->priority,
            'action_url' => '/student/tasks',
        ];
    }

    public function broadcastOn(object $notifiable): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel("user.{$notifiable->id}"),
        ];
    }
}
