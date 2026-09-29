<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_and_complete_task(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/student/tasks', [
                'subject_id' => $subject->id,
                'title' => 'Read Chapter 4',
                'description' => 'Complete chapter exercises',
                'due_date' => now()->addDays(2)->toIso8601String(),
                'priority' => 'high',
                'estimated_minutes' => 90,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Read Chapter 4');

        $taskId = $response->json('data.id');

        $completeResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/student/tasks/{$taskId}/complete");

        $completeResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'status' => 'completed',
        ]);
    }
}
