<?php

namespace App\Events;

use App\Models\StudySession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudyPlanGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public array $sessions;

    public function __construct(int $userId, array $sessions)
    {
        $this->userId = $userId;
        $this->sessions = $sessions;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->userId}");
    }

    public function broadcastAs(): string
    {
        return 'study_plan.generated';
    }

    public function broadcastWith(): array
    {
        return [
            'created_sessions' => count($this->sessions),
            'sessions' => $this->sessions,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}