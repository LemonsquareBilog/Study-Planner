<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deadline_boundary_preserves_history_and_student_isolation(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create(['user_id' => $user->id, 'name' => 'Math']);
        foreach (['pending', 'completed'] as $status) {
            $user->tasks()->create(['subject_id' => $subject->id, 'title' => $status,
                'deadline' => now(), 'estimated_minutes' => 30, 'status' => $status]);
        }
        $active = $user->tasks()->create(['subject_id' => $subject->id, 'title' => 'Future',
            'deadline' => now()->addSecond(), 'estimated_minutes' => 30]);
        $this->actingAs($user, 'sanctum');
        $this->getJson('/api/student/tasks')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $active->id);
        $this->getJson('/api/student/tasks?view=history')->assertOk()->assertJsonCount(2, 'data');
        $this->travel(2)->seconds();
        $this->getJson('/api/student/tasks')->assertOk()->assertJsonCount(0, 'data');
        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => 'pending', 'status' => 'pending', 'deleted_at' => null]);
        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => 'completed', 'status' => 'completed', 'deleted_at' => null]);
        $this->actingAs($other, 'sanctum');
        $this->getJson('/api/student/tasks?view=history')->assertOk()->assertJsonCount(0, 'data');
    }
}
