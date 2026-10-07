<?php

namespace Tests\Feature\Student;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizSubmissionHistoryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_and_empty_quiz_submissions_are_saved_and_reviewable_by_the_student(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create([
            'user_id' => $user->id,
            'name' => 'Database Systems',
            'difficulty' => 2,
            'color' => 'blue',
            'is_active' => true,
        ]);
        $topic = Topic::create([
            'subject_id' => $subject->id,
            'name' => 'Indexes',
            'difficulty' => 2,
            'is_active' => true,
        ]);
        $quiz = Quiz::create([
            'topic_id' => $topic->id,
            'title' => 'Index fundamentals',
            'description' => 'Practice database index concepts.',
            'passing_score' => 70,
            'is_active' => true,
        ]);
        $firstQuestion = Question::create([
            'quiz_id' => $quiz->id,
            'question' => 'What does an index improve?',
            'option_a' => 'Read lookup speed',
            'option_b' => 'Password strength',
            'option_c' => 'Disk capacity',
            'option_d' => 'Column names',
            'correct_answer' => 'A',
            'question_order' => 1,
        ]);
        $secondQuestion = Question::create([
            'quiz_id' => $quiz->id,
            'question' => 'Which operation can indexes slow down?',
            'option_a' => 'Reads',
            'option_b' => 'Writes',
            'option_c' => 'Lookups',
            'option_d' => 'Searches',
            'correct_answer' => 'B',
            'question_order' => 2,
        ]);

        $this->getJson('/api/student/quiz-attempts')->assertUnauthorized();
        $this->actingAs($user, 'sanctum');

        $this->getJson("/api/student/quizzes/{$quiz->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.questions.0.correct_answer');

        $startedAt = now()->toIso8601String();
        $partial = $this->postJson("/api/student/quizzes/{$quiz->id}/submit", [
            'started_at' => $startedAt,
            'answers' => [$firstQuestion->id => 'A'],
        ])->assertCreated()
            ->assertJsonPath('data.score', 1)
            ->assertJsonPath('data.total_questions', 2);

        $partialId = $partial->json('data.id');
        $this->assertDatabaseHas('quiz_attempts', ['id' => $partialId, 'user_id' => $user->id, 'score' => 1]);
        $this->assertDatabaseCount('quiz_answers', 2);

        $empty = $this->postJson("/api/student/quizzes/{$quiz->id}/submit", [
            'started_at' => $startedAt,
            'answers' => [],
        ])->assertCreated()
            ->assertJsonPath('data.score', 0)
            ->assertJsonPath('data.total_questions', 2);

        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id' => $empty->json('data.id'),
            'question_id' => $secondQuestion->id,
            'selected_answer' => null,
            'is_correct' => false,
        ]);

        $this->getJson('/api/student/quiz-attempts')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/student/quiz-attempts/{$partialId}")
            ->assertOk()
            ->assertJsonPath('data.answers.0.question.correct_answer', 'A')
            ->assertJsonPath('data.answers.0.selected_answer', 'A');
    }
}