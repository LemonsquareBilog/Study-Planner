<?php

namespace App\Events;

use App\Models\StudySession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudySessionCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public StudySession $session;

    public function __construct(StudySession $session)
    {
        $this->session = $session;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->session->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'study_session.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->session->id,
            'status' => $this->session->status,
            'completed_at' => $this->session->completed_at?->toIso8601String(),
        ];
    }
}