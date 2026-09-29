<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluates_quiz_submission_and_calculates_score(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['user_id' => $user->id]);
        $topic = Topic::factory()->create(['subject_id' => $subject->id, 'mastery_percentage' => 50.0]);
        $quiz = Quiz::factory()->create(['topic_id' => $topic->id, 'total_questions' => 2]);

        $q1 = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is 2 + 2?',
            'options' => ['3', '4', '5'],
            'correct_answer' => '4',
        ]);

        $q2 = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is 3 + 3?',
            'options' => ['5', '6', '7'],
            'correct_answer' => '6',
        ]);

        $service = new QuizService();
        $attempt = $service->submitQuiz($user, $quiz, [
            $q1->id => '4',
            $q2->id => '5',
        ]);

        $this->assertEquals(50.0, $attempt->score);
        $this->assertEquals(1, $attempt->correct_answers);
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => 50.0,
        ]);
    }
}
