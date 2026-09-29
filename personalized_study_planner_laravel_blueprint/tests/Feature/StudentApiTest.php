<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Task;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->student = User::factory()->create(['role' => 'student']);
        $this->token = $this->student->createToken('test')->plainTextToken;
    }

    /** @test */
    public function student_can_get_dashboard(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/student/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'progress',
                    'recommendations',
                    'upcoming_tasks',
                    'upcoming_sessions',
                    'unread_notifications',
                    'onboarding' => [
                        'steps',
                        'completed_steps',
                        'total_steps',
                        'completion_percentage',
                        'next_action',
                        'is_complete',
                        'counts',
                    ],
                ],
                'message',
            ]);
    }

    /** @test */
    public function student_can_get_onboarding_status(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/student/study-preference/onboarding');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'steps',
                    'completed_steps',
                    'total_steps',
                    'completion_percentage',
                    'next_action',
                    'is_complete',
                ],
                'message',
            ]);
    }

    /** @test */
    public function student_can_create_default_preferences(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/student/study-preference/defaults');

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'max_daily_minutes',
                    'session_duration_minutes',
                    'break_duration_minutes',
                    'preferred_start_time',
                    'preferred_end_time',
                ],
                'message',
            ]);
    }

    /** @test */
    public function student_can_crud_subjects(): void
    {
        // Create
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/student/subjects', [
            'name' => 'Mathematics',
            'description' => 'Advanced mathematics',
            'difficulty' => 4,
            'color' => 'blue',
        ]);

        $response->assertStatus(201);
        $subjectId = $response->json('data.id');

        // Read
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/student/subjects/{$subjectId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Mathematics');

        // Update
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/v1/student/subjects/{$subjectId}", [
            'name' => 'Advanced Mathematics',
            'difficulty' => 5,
            'color' => 'indigo',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Advanced Mathematics');

        // Delete
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/v1/student/subjects/{$subjectId}");

        $response->assertStatus(200);
    }

    /** @test */
    public function student_can_crud_tasks(): void
    {
        $subject = Subject::factory()->create(['user_id' => $this->student->id]);
        
        // Create
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/student/tasks', [
            'subject_id' => $subject->id,
            'title' => 'Complete calculus homework',
            'task_type' => 'assignment',
            'priority' => 'high',
            'deadline' => now()->addDays(3)->format('Y-m-d'),
            'estimated_minutes' => 90,
        ]);

        $response->assertStatus(201);
        $taskId = $response->json('data.id');

        // Read with search
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/student/tasks?search=calculus');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));

        // Update status
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/v1/student/tasks/{$taskId}/status", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        // Delete and restore
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/v1/student/tasks/{$taskId}");

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/student/tasks/{$taskId}/restore");

        $response->assertStatus(200);
    }

    /** @test */
    public function student_can_generate_study_plan(): void
    {
        // Setup preferences
        $this->student->studyPreference()->create([
            'max_daily_minutes' => 180,
            'session_duration_minutes' => 50,
            'break_duration_minutes' => 10,
            'preferred_start_time' => '18:00',
            'preferred_end_time' => '22:00',
        ]);

        $subject = Subject::factory()->create(['user_id' => $this->student->id]);
        
        Task::factory()->create([
            'user_id' => $this->student->id,
            'subject_id' => $subject->id,
            'title' => 'Study chapter 1',
            'priority' => 'high',
            'deadline' => now()->addDays(7),
            'estimated_minutes' => 120,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/student/study-plan/generate', [
            'from' => now()->format('Y-m-d'),
            'to' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'created_sessions',
                    'sessions' => [
                        '*' => [
                            'id',
                            'scheduled_date',
                            'start_time',
                            'end_time',
                            'duration_minutes',
                            'status',
                            'subject',
                            'topic',
                            'task',
                        ],
                    ],
                    'reason',
                ],
                'message',
            ]);
    }

    /** @test */
    public function student_can_take_quiz_with_draft(): void
    {
        $subject = Subject::factory()->create(['user_id' => $this->student->id]);
        $topic = Topic::factory()->create(['subject_id' => $subject->id]);
        
        $quiz = Quiz::factory()->create([
            'topic_id' => $topic->id,
            'title' => 'Test Quiz',
            'is_active' => true,
        ]);

        Question::factory()->count(5)->create([
            'quiz_id' => $quiz->id,
        ]);

        // Start quiz
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/student/quizzes/{$quiz->id}/start");

        $response->assertStatus(201);
        $attemptId = $response->json('data.id');

        // Save draft
        $answers = [];
        $questions = $quiz->questions()->get();
        foreach ($questions as $index => $question) {
            $answers[$question->id] = $index % 2 === 0 ? 'A' : 'B';
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/student/quiz-attempts/{$attemptId}/draft", [
            'answers' => $answers,
        ]);

        $response->assertStatus(200);

        // Resume
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/student/quiz-attempts/{$attemptId}/resume");

        $response->assertStatus(200);

        // Submit
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/student/quizzes/{$quiz->id}/submit", [
            'answers' => $answers,
            'started_at' => now()->subMinutes(30)->toIso8601String(),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'percentage',
                    'answers' => [
                        '*' => ['is_correct', 'correct_answer'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function student_can_get_recommendations_with_reason(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/student/recommendations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'reason',
                'message',
            ]);
    }

    /** @test */
    public function student_can_get_notifications_count(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/student/notifications/count');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'unread',
                    'total',
                ],
                'message',
            ]);
    }
}