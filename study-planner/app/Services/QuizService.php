<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class QuizService
{
    public function supportsRetry(): bool
    {
        if (! Schema::hasColumns('quiz_attempts', ['submission_id', 'submission_hash'])) {
            return false;
        }

        $indexes = collect(Schema::getIndexes('quiz_attempts'))
            ->filter(fn ($index) => $index['unique'] && $index['columns'] === ['user_id', 'submission_id'])
            ->pluck('name');
        if ($indexes->isEmpty()) {
            return false;
        }

        // Laravel's index metadata does not identify partial or invalid indexes.
        // Neither can safely guarantee uniqueness for every submission identity.
        $connection = DB::connection();
        if ($connection->getDriverName() === 'sqlite') {
            $table = $connection->getQueryGrammar()->wrapTable('quiz_attempts');

            return collect($connection->select("PRAGMA index_list($table)"))
                ->contains(fn ($index) => $indexes->contains($index->name) && (int) $index->partial === 0);
        }
        if ($connection->getDriverName() === 'pgsql') {
            $table = $connection->getTablePrefix().'quiz_attempts';
            $validIndexes = $connection->select(
                'select c.relname as name from pg_index i join pg_class c on c.oid = i.indexrelid where i.indrelid = to_regclass(?) and i.indisunique and i.indisvalid and i.indpred is null and i.indexprs is null',
                [$table],
            );

            return collect($validIndexes)->contains(fn ($index) => $indexes->contains($index->name));
        }

        return in_array($connection->getDriverName(), ['mysql', 'mariadb'], true);
    }

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
            abort_unless($this->supportsRetry(), 503, 'Quiz retry protection is temporarily unavailable. Please try again later.');
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
            $attributes = [
                'quiz_id' => $quiz->id,
                'score' => 0,
                'total_questions' => $questions->count(),
                'percentage' => 0,
                'started_at' => $startedAt,
                'completed_at' => now(),
            ];
            if ($submissionId !== null) {
                $attributes['submission_id'] = $submissionId;
                $attributes['submission_hash'] = $submissionHash;
            }
            $attempt = $user->quizAttempts()->create($attributes);

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
