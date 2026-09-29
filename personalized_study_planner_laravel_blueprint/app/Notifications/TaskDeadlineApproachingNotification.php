<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDeadlineApproachingNotification extends Notification implements ShouldQueue
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
        $subject = $this->task->subject;
        
        return (new MailMessage)
            ->subject("Task Deadline in {$this->hoursUntil} Hours: {$this->task->title}")
            ->greeting("Hello!")
            ->line("Your task **{$this->task->title}** is due in {$this->hoursUntil} hours.")
            ->line("Subject: **{$subject->name}**")
            ->line("Priority: **{$this->task->priority}**")
            ->line("Due: **{$this->task->deadline->format('l, F j, Y \a\t g:i A')}**")
            ->when($this->task->description, function ($message) {
                $message->line("Description: {$this->task->description}");
            })
            ->action('View Task', url("/student/tasks/{$this->task->id}"))
            ->line('Don\'t forget to complete your task on time!');
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
            'action_url' => "/student/tasks/{$this->task->id}",
        ];
    }

    public function broadcastOn(object $notifiable): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel("user.{$notifiable->id}"),
        ];
    }
}