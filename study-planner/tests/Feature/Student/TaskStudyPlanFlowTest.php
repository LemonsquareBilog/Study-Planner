<?php

namespace Tests\Feature\Student;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStudyPlanFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_task_is_saved_listed_and_scheduled_with_task_details(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create([
            'user_id' => $user->id,
            'name' => 'Java Programming',
            'difficulty' => 3,
            'color' => 'blue',
            'is_active' => true,
        ]);

        $user->studyPreference()->create([
            'max_daily_minutes' => 120,
            'session_duration_minutes' => 50,
            'break_duration_minutes' => 10,
            'preferred_start_time' => '18:00',
            'preferred_end_time' => '22:00',
        ]);

        $this->getJson('/api/student/tasks')->assertUnauthorized();
        $this->actingAs($user, 'sanctum');

        $startDate = today()->addDay();
        $deadline = now()->addDays(4)->setTime(17, 0);
        $created = $this->postJson('/api/student/tasks', [
            'subject_id' => $subject->id,
            'title' => 'Java loops assignment',
            'description' => 'Implement and test loop exercises.',
            'task_type' => 'project',
            'priority' => 'urgent',
            'start_date' => $startDate->toDateString(),
            'deadline' => $deadline->toDateTimeString(),
            'estimated_minutes' => 90,
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Java loops assignment')
            ->assertJsonPath('data.priority', 'urgent');

        $taskId = $created->json('data.id');

        $this->getJson('/api/student/tasks?sort=priority')
            ->assertOk()
            ->assertJsonPath('data.0.id', $taskId);

        $this->postJson('/api/student/study-plan/generate', [
            'from' => today()->toDateString(),
            'to' => $deadline->toDateString(),
        ])->assertOk()
            ->assertJsonPath('data.created_sessions', 2);

        $sessions = $this->getJson('/api/student/study-plan')
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $sessions);
        $this->assertSame($taskId, $sessions[0]['task']['id']);
        $this->assertSame('Java loops assignment', $sessions[0]['task']['title']);
        $this->assertSame('Implement and test loop exercises.', $sessions[0]['task']['description']);
        $this->assertSame('urgent', $sessions[0]['task']['priority']);
        $this->assertSame($startDate->toDateString(), substr($sessions[0]['task']['start_date'], 0, 10));
        $this->assertSame(90, $sessions[0]['task']['estimated_minutes']);
        $this->assertGreaterThanOrEqual($startDate->toDateString(), $sessions[0]['scheduled_date']);
    }
}