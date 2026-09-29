<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeadlineApproaching implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->task->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'task.deadline_approaching';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->task->id,
            'title' => $this->task->title,
            'subject' => $this->task->subject?->only(['id', 'name', 'color']),
            'deadline' => $this->task->deadline?->toIso8601String(),
            'priority' => $this->task->priority,
            'status' => $this->task->status,
        ];
    }
}