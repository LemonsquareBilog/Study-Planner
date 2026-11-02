<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    public function submit(User $user, Quiz $quiz, array $answers, string $startedAt, ?string $submissionToken = null): QuizAttempt
    {
        $questions = $quiz->questions()->get()->keyBy('id');

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages(['quiz' => 'This quiz has no questions.']);
        }

        $submissionId = null;
        if ($submissionToken !== null) {
            try {
                $issued = json_decode(Crypt::decryptString($submissionToken), true, flags: JSON_THROW_ON_ERROR);
                if (($issued['user_id'] ?? null) !== $user->id || ($issued['quiz_id'] ?? null) !== $quiz->id || ! is_string($issued['id'] ?? null)) {
                    throw new \RuntimeException('Invalid submission');
                }
                $submissionId = $issued['id'];
            } catch (\Throwable) {
                throw ValidationException::withMessages(['submission_id' => 'Start this quiz again to obtain a valid submission identifier.']);
            }
        }
        $normalized = [];
        foreach ($questions as $question) {
            $normalized[$question->id] = $answers[$question->id] ?? null;
        }
        ksort($normalized);
        $submissionHash = hash('sha256', json_encode([
            CarbonImmutable::parse($startedAt)->utc()->toIso8601String(), $normalized,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($user, $quiz, $questions, $normalized, $startedAt, $submissionId, $submissionHash) {
            if ($submissionId !== null) {
                // Serialize retries for one user; the unique constraint is the final safeguard.
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $existing = $user->quizAttempts()->where('submission_id', $submissionId)->first();
                if ($existing) {
                    abort_unless($existing->quiz_id === $quiz->id && hash_equals($existing->submission_hash, $submissionHash), 409, 'This submission identifier was already used for different answers.');

                    return $existing;
                }
            }
            $score = 0;
            $attempt = $user->quizAttempts()->create([
                'quiz_id' => $quiz->id,
                'submission_id' => $submissionId,
                'submission_hash' => $submissionId !== null ? $submissionHash : null,
                'score' => 0,
                'total_questions' => $questions->count(),
                'percentage' => 0,
                'started_at' => $startedAt,
                'completed_at' => now(),
            ]);

            foreach ($questions as $question) {
                $selected = $normalized[$question->id];
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

            return $attempt->load('quiz.topic');
        }, 5);
    }
}
