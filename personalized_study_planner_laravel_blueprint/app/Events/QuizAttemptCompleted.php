<?php

namespace App\Events;

use App\Models\QuizAttempt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizAttemptCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public QuizAttempt $attempt;

    public function __construct(QuizAttempt $attempt)
    {
        $this->attempt = $attempt;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->attempt->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'quiz.attempt.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->attempt->id,
            'quiz_id' => $this->attempt->quiz_id,
            'quiz_title' => $this->attempt->quiz?->title,
            'score' => $this->attempt->score,
            'total_questions' => $this->attempt->total_questions,
            'percentage' => $this->attempt->percentage,
            'completed_at' => $this->attempt->completed_at?->toIso8601String(),
        ];
    }
}