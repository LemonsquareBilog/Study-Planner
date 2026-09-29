<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    public function submit(User $user, Quiz $quiz, array $answers, string $startedAt): QuizAttempt
    {
        $questions = $quiz->questions()->get()->keyBy('id');

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages(['quiz' => 'This quiz has no questions.']);
        }

        DB::beginTransaction();
        try {
            $score = 0;
            $attempt = $user->quizAttempts()->create([
                'quiz_id' => $quiz->id,
                'score' => 0,
                'total_questions' => $questions->count(),
                'percentage' => 0,
                'started_at' => $startedAt,
                'completed_at' => now(),
            ]);

            foreach ($questions as $question) {
                $selected = $answers[$question->id] ?? null;
                $correct = $selected !== null && strtoupper($selected) === $question->correct_answer;
                $score += $correct ? 1 : 0;
                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'selected_answer' => $selected,
                    'is_correct' => $correct,
                ]);
            }

            $percentage = round(($score / $questions->count()) * 100, 2);
            $attempt->update(['score' => $score, 'percentage' => $percentage]);

            DB::commit();
            return $attempt->load('quiz.topic');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
