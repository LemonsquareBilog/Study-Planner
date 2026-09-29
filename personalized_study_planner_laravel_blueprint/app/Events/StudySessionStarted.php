<?php

namespace App\Events;

use App\Models\StudySession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudySessionStarted implements ShouldBroadcast
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
        return 'study_session.started';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->session->id,
            'subject_id' => $this->session->subject_id,
            'topic_id' => $this->session->topic_id,
            'task_id' => $this->session->task_id,
            'scheduled_date' => $this->session->scheduled_date?->toDateString(),
            'start_time' => $this->session->start_time,
            'end_time' => $this->session->end_time,
            'duration_minutes' => $this->session->duration_minutes,
            'status' => $this->session->status,
            'started_at' => $this->session->started_at?->toIso8601String(),
        ];
    }
}