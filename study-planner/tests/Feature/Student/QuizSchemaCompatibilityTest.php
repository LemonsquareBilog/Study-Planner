<?php

namespace Tests\Feature\Student;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuizSchemaCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuiz(): Quiz
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create(['user_id' => $student->id, 'name' => 'Science', 'difficulty' => 2, 'color' => 'blue', 'is_active' => true]);
        $topic = Topic::create(['subject_id' => $subject->id, 'name' => 'Atoms', 'difficulty' => 2, 'is_active' => true]);
        $quiz = Quiz::create(['topic_id' => $topic->id, 'title' => 'Practice', 'passing_score' => 70, 'is_active' => true]);
        Question::create(['quiz_id' => $quiz->id, 'question' => 'Choose A', 'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D', 'correct_answer' => 'A', 'question_order' => 1]);
        $this->actingAs($student, 'sanctum');

        return $quiz;
    }

    public function test_pre_migration_allows_legacy_submissions_but_rejects_previously_issued_tokens(): void
    {
        $quiz = $this->makeQuiz();
        $token = $this->getJson('/api/student/quizzes/'.$quiz->id)->assertOk()->assertJsonPath('data.submission_retry_safe', true)->json('data.submission_id');
        $migration = require database_path('migrations/2026_11_03_000001_add_submission_identity_to_quiz_attempts.php');
        $migration->down();
        $this->getJson('/api/student/quizzes/'.$quiz->id)->assertOk()->assertJsonPath('data.submission_retry_safe', false)->assertJsonMissingPath('data.submission_id');
        $payload = ['started_at' => now()->toIso8601String(), 'answers' => []];
        $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', $payload + ['submission_id' => $token])->assertStatus(503);
        $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', $payload + ['submission_id' => $token])->assertStatus(503);
        $this->assertDatabaseCount('quiz_attempts', 0);
        $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', $payload)->assertCreated()->assertJsonPath('data.score', 0);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_partial_schema_does_not_claim_retry_safety_and_preserves_legacy_submissions(): void
    {
        $quiz = $this->makeQuiz();
        $token = $this->getJson('/api/student/quizzes/'.$quiz->id)->json('data.submission_id');
        $migration = require database_path('migrations/2026_11_03_000001_add_submission_identity_to_quiz_attempts.php');
        $migration->down();
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->uuid('submission_id')->nullable();
            $table->string('submission_hash', 64)->nullable();
        });
        $this->getJson('/api/student/quizzes/'.$quiz->id)->assertJsonPath('data.submission_retry_safe', false)->assertJsonMissingPath('data.submission_id');
        DB::statement('CREATE UNIQUE INDEX partial_quiz_retry ON quiz_attempts (user_id, submission_id) WHERE submission_id IS NULL');
        $this->getJson('/api/student/quizzes/'.$quiz->id)->assertJsonPath('data.submission_retry_safe', false)->assertJsonMissingPath('data.submission_id');
        $payload = ['started_at' => now()->toIso8601String(), 'answers' => []];
        $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', $payload + ['submission_id' => $token])->assertStatus(503);
        $this->assertDatabaseCount('quiz_attempts', 0);
        $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', $payload)->assertCreated();
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_schema_introspection_failure_is_not_treated_as_an_old_schema(): void
    {
        Schema::shouldReceive('hasColumns')->once()->andThrow(new \RuntimeException('Schema access failed'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Schema access failed');
        app(QuizService::class)->supportsRetry();
    }
}
