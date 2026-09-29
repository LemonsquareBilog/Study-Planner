<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public QuizAttempt $attempt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $percentage = $this->attempt->percentage;
        $quiz = $this->attempt->quiz;
        
        $message = (new MailMessage)
            ->subject("Quiz Completed: {$quiz->title} - {$percentage}%")
            ->greeting("Hello!")
            ->line("You've completed the quiz **{$quiz->title}**.")
            ->line("Your score: **{$this->attempt->score} / {$this->attempt->total_questions}** ({$percentage}%)")
            ->line("Completed at: **{$this->attempt->completed_at->format('l, F j, Y \a\t g:i A')}**");

        if ($percentage >= 80) {
            $message->line('🎉 Excellent work! You have a strong understanding of this topic.');
        } elseif ($percentage >= 60) {
            $message->line('Good job! Consider reviewing the topics you missed to strengthen your knowledge.');
        } else {
            $message->line('Keep practicing! Review the recommended resources to improve your understanding.');
        }

        $message->action('View Results', url("/student/quizzes/{$quiz->id}/attempts/{$this->attempt->id}"))
            ->line('Check your detailed results and review the correct answers.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quiz_completed',
            'title' => "Quiz Completed: {$this->attempt->quiz->title}",
            'message' => "You scored {$this->attempt->percentage}% on {$this->attempt->quiz->title}.",
            'attempt_id' => $this->attempt->id,
            'quiz_id' => $this->attempt->quiz_id,
            'score' => $this->attempt->score,
            'total_questions' => $this->attempt->total_questions,
            'percentage' => $this->attempt->percentage,
            'action_url' => "/student/quizzes/{$this->attempt->quiz_id}/attempts/{$this->attempt->id}",
        ];
    }

    public function broadcastOn(object $notifiable): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel("user.{$notifiable->id}"),
        ];
    }
}