<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StudySession;
use App\Models\Task;
use App\Models\QuizAttempt;
use App\Events\StudySessionStarted;
use App\Events\StudySessionCompleted;
use App\Events\StudySessionSkipped;
use App\Events\QuizAttemptCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->student = User::factory()->create(['role' => 'student']);
        $this->token = $this->student->createToken('test')->plainTextToken;
        
        Event::fake();
    }

    /** @test */
    public function study_session_started_event_is_broadcast(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->student->id,
            'status' => 'scheduled',
        ]);

        // Update status to in_progress
        $session->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        Event::assertDispatched(StudySessionStarted::class, function ($event) use ($session) {
            return $event->session->id === $session->id;
        });
    }

    /** @test */
    public function study_session_completed_event_is_broadcast(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $session->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Event::assertDispatched(StudySessionCompleted::class, function ($event) use ($session) {
            return $event->session->id === $session->id;
        });
    }

    /** @test */
    public function study_session_skipped_event_is_broadcast(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->student->id,
            'status' => 'scheduled',
        ]);

        $session->update([
            'status' => 'skipped',
            'completed_at' => now(),
        ]);

        Event::assertDispatched(StudySessionSkipped::class, function ($event) use ($session) {
            return $event->session->id === $session->id;
        });
    }

    /** @test */
    public function quiz_completed_event_is_broadcast(): void
    {
        $quizAttempt = QuizAttempt::factory()->create([
            'user_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $quizAttempt->update([
            'status' => 'completed',
            'completed_at' => now(),
            'percentage' => 85,
        ]);

        Event::assertDispatched(QuizAttemptCompleted::class, function ($event) use ($quizAttempt) {
            return $event->attempt->id === $quizAttempt->id;
        });
    }

    /** @test */
    public function broadcasting_auth_works(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/broadcasting/auth', [
            'channel_name' => "user.{$this->student->id}",
            'socket_id' => '123.456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'auth',
                'channel_data',
            ]);
    }

    /** @test */
    public function user_cannot_subscribe_to_other_user_channel(): void
    {
        $otherUser = \App\Models\User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/broadcasting/auth', [
            'channel_name' => "user.{$otherUser->id}",
            'socket_id' => '123.456',
        ]);

        $response->assertStatus(403);
    }
}