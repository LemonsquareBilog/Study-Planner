<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_topic_under_subject(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/student/subjects/{$subject->id}/topics", [
                'title' => 'Linear Algebra',
                'difficulty_level' => 4,
                'estimated_hours' => 12.5,
                'mastery_percentage' => 20.0,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Linear Algebra');

        $this->assertDatabaseHas('topics', [
            'subject_id' => $subject->id,
            'title' => 'Linear Algebra',
        ]);
    }

    public function test_student_cannot_add_topic_to_others_subject(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $otherUser = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/student/subjects/{$subject->id}/topics", [
                'title' => 'Unauthorized Topic',
                'difficulty_level' => 3,
                'estimated_hours' => 5,
            ]);

        $response->assertStatus(403);
    }
}
