<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Events\QuizAttemptCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    /**
     * Start a new quiz attempt (draft mode).
     */
    public function start(User $user, Quiz $quiz): QuizAttempt
    {
        // Check if there's an existing draft for this quiz
        $existingDraft = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->where('status', 'draft')
            ->latest()
            ->first();

        if ($existingDraft) {
            $existingDraft->load('quiz.topic', 'answers.question');
            return $existingDraft;
        }

        $attempt = $user->quizAttempts()->create([
            'quiz_id' => $quiz->id,
            'status' => 'draft',
            'started_at' => now(),
        ]);

        $attempt->load('quiz.topic');
        return $attempt;
    }

    /**
     * Save draft answers for an in-progress quiz.
     */
    public function saveDraft(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        if ($attempt->status === 'completed') {
            throw ValidationException::withMessages(['quiz' => 'Cannot save draft for completed quiz.']);
        }

        $questions = $attempt->quiz->questions()->get()->keyBy('id');

        DB::transaction(function () use ($attempt, $answers, $questions) {
            $attempt->update(['status' => 'in_progress']);

            foreach ($answers as $questionId => $selectedAnswer) {
                $question = $questions->get($questionId);
                if (!$question) {
                    continue;
                }

                $correct = $selectedAnswer !== null && strtoupper($selectedAnswer) === $question->correct_answer;
                
                $attempt->answers()->updateOrCreate(
                    ['question_id' => $questionId],
                    [
                        'selected_answer' => $selectedAnswer,
                        'is_correct' => $correct,
                        'correct_answer' => $correct ? null : $question->correct_answer,
                    ]
                );
            }
        });

        return $attempt->fresh()->load('quiz.topic', 'answers.question');
    }

    /**
     * Submit quiz answers for server-side scoring.
     */
    public function submit(User $user, Quiz $quiz, array $answers, string $startedAt): QuizAttempt
    {
        $questions = $quiz->questions()->get()->keyBy('id');

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages(['quiz' => 'This quiz has no questions.']);
        }

        // Find existing draft/in_progress attempt
        $attempt = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->whereIn('status', ['draft', 'in_progress'])
            ->latest()
            ->first();

        DB::beginTransaction();
        try {
            $score = 0;

            if (!$attempt) {
                $attempt = $user->quizAttempts()->create([
                    'quiz_id' => $quiz->id,
                    'status' => 'in_progress',
                    'score' => 0,
                    'total_questions' => $questions->count(),
                    'percentage' => 0,
                    'started_at' => $startedAt,
                    'completed_at' => now(),
                ]);
            }

            foreach ($questions as $question) {
                $selected = $answers[$question->id] ?? null;
                $correct = $selected !== null && strtoupper($selected) === $question->correct_answer;
                $score += $correct ? 1 : 0;
                
                $attempt->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    [
                        'selected_answer' => $selected,
                        'is_correct' => $correct,
                        'correct_answer' => $correct ? null : $question->correct_answer,
                    ]
                );
            }

            $percentage = round(($score / $questions->count()) * 100, 2);
            $attempt->update([
                'status' => 'completed',
                'score' => $score,
                'total_questions' => $questions->count(),
                'percentage' => $percentage,
                'completed_at' => now(),
            ]);

            DB::commit();
            
            // Load relationships for broadcasting
            $attempt->load('quiz.topic', 'answers.question');
            
            // Broadcast quiz completion event
            broadcast(new QuizAttemptCompleted($attempt))->toOthers();

            return $attempt;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Resume an existing draft attempt.
     */
    public function resume(QuizAttempt $attempt): QuizAttempt
    {
        if ($attempt->status === 'completed') {
            throw ValidationException::withMessages(['quiz' => 'This quiz has already been completed.']);
        }

        return $attempt->load('quiz.topic', 'answers.question');
    }
}
