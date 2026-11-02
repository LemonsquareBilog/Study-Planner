<?php

namespace Tests\Feature\Student;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function quiz(User $user, string $title = 'Practice'): Quiz
    {
        $subject = Subject::create(['user_id' => $user->id, 'name' => 'Science', 'difficulty' => 2, 'color' => 'blue', 'is_active' => true]);
        $topic = Topic::create(['subject_id' => $subject->id, 'name' => 'Atoms', 'difficulty' => 2, 'is_active' => true]);
        $quiz = Quiz::create(['topic_id' => $topic->id, 'title' => $title, 'passing_score' => 70, 'is_active' => true]);
        Question::create(['quiz_id' => $quiz->id, 'question' => 'Choose A', 'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D', 'correct_answer' => 'A', 'question_order' => 1]);

        return $quiz;
    }

    public function test_quiz_pages_are_bounded_and_summaries_are_owned_and_deterministic(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $quiz = $this->quiz($student);
        for ($i = 0; $i < 24; $i++) {
            Quiz::create(['topic_id' => $quiz->topic_id, 'title' => 'Practice', 'passing_score' => 70, 'is_active' => true]);
        }
        $this->quiz($other);
        $base = ['quiz_id' => $quiz->id, 'score' => 1, 'total_questions' => 1, 'started_at' => now(), 'completed_at' => now()];
        $student->quizAttempts()->create($base + ['percentage' => 90]);
        $latest = $student->quizAttempts()->create($base + ['percentage' => 50]);
        $other->quizAttempts()->create($base + ['percentage' => 100]);
        $this->actingAs($student, 'sanctum');
        $first = $this->getJson('/api/student/quizzes')->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('total', 25);
        $first->assertJsonPath('data.0.attempts_count', 2)->assertJsonPath('data.0.latest_attempt.id', $latest->id);
        $this->assertEquals(90, $first->json('data.0.best_score'));
        $last = $this->getJson('/api/student/quizzes?page=2')->assertOk()->assertJsonCount(5, 'data');
        $this->assertCount(25, array_unique([...array_column($first->json('data'), 'id'), ...array_column($last->json('data'), 'id')]));
        $this->getJson('/api/student/quizzes?search=missing')->assertJsonCount(0, 'data');
        $this->getJson('/api/student/quizzes?page=-1')->assertUnprocessable();
    }

    public function test_history_is_a_bounded_summary_and_details_are_private(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $quiz = $this->quiz($student);
        $createdIds = [];
        $completedAt = now();
        for ($i = 0; $i < 45; $i++) {
            $attempt = $student->quizAttempts()->create(['quiz_id' => $quiz->id, 'score' => 0, 'total_questions' => 1, 'percentage' => 0, 'started_at' => $completedAt, 'completed_at' => $completedAt]);
            $createdIds[] = $attempt->id;
        }
        $this->actingAs($student, 'sanctum');
        $ids = [];
        foreach ([1 => 20, 2 => 20, 3 => 5] as $page => $count) {
            $response = $this->getJson('/api/student/quiz-attempts?page='.$page)->assertOk()->assertJsonCount($count, 'data')->assertJsonPath('total', 45)->assertJsonMissingPath('data.0.answers');
            array_push($ids, ...array_column($response->json('data'), 'id'));
        }
        $this->assertCount(45, array_unique($ids));
        $this->assertSame(array_reverse($createdIds), $ids);
        $this->actingAs(User::factory()->create(['role' => 'student', 'is_active' => true]), 'sanctum');
        $this->getJson('/api/student/quiz-attempts/'.$ids[0])->assertNotFound();
        $this->getJson('/api/student/quizzes/'.$quiz->id)->assertNotFound();
    }

    public function test_issued_submission_id_deduplicates_retries_and_rejects_conflicts(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $quiz = $this->quiz($student);
        $this->actingAs($student, 'sanctum');
        $token = $this->getJson('/api/student/quizzes/'.$quiz->id)->json('data.submission_id');
        $this->assertIsString($token);
        $payload = ['submission_id' => $token, 'started_at' => now()->toIso8601String(), 'answers' => [$quiz->questions()->first()->id => 'A']];
        $first = $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', $payload)->assertCreated();
        $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', $payload)->assertOk()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertDatabaseCount('quiz_answers', 1);
        $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', [...$payload, 'answers' => []])->assertConflict();
        $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', [...$payload, 'submission_id' => 'forged'])->assertUnprocessable();
        $newToken = $this->getJson('/api/student/quizzes/'.$quiz->id)->json('data.submission_id');
        $this->postJson('/api/student/quizzes/'.$quiz->id.'/submit', [...$payload, 'submission_id' => $newToken])->assertCreated();
        $this->assertDatabaseCount('quiz_attempts', 2);
    }

    public function test_submission_migration_round_trip_preserves_existing_history(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $quiz = $this->quiz($student);
        $attempt = $student->quizAttempts()->create(['quiz_id' => $quiz->id, 'score' => 1, 'total_questions' => 1, 'percentage' => 100, 'started_at' => now(), 'completed_at' => now()]);
        $migration = require database_path('migrations/2026_11_03_000001_add_submission_identity_to_quiz_attempts.php');
        $migration->down();
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt->id, 'score' => 1]);
        $migration->up();
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt->id, 'submission_id' => null, 'score' => 1]);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_answer_write_failure_rolls_back_the_attempt(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $quiz = $this->quiz($student);
        QuizAnswer::creating(function () {
            throw new \RuntimeException('Simulated answer storage failure');
        });
        try {
            app(QuizService::class)->submit($student, $quiz, [], now()->toIso8601String());
            $this->fail('Submission should have failed.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated answer storage failure', $error->getMessage());
        } finally {
            QuizAnswer::flushEventListeners();
        }
        $this->assertDatabaseCount('quiz_attempts', 0);
        $this->assertDatabaseCount('quiz_answers', 0);
    }
}
