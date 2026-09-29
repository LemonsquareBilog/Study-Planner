<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_list_own_subjects(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Subject::factory()->count(3)->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create(['role' => 'student']);
        Subject::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/student/subjects');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_student_can_create_subject(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/student/subjects', [
                'name' => 'Advanced Mathematics',
                'code' => 'MATH301',
                'color' => '#3B82F6',
                'target_grade' => 'A',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Advanced Mathematics');

        $this->assertDatabaseHas('subjects', [
            'user_id' => $user->id,
            'code' => 'MATH301',
        ]);
    }

    public function test_student_cannot_view_others_subject(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $otherUser = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/student/subjects/{$subject->id}");

        $response->assertStatus(403);
    }
}
