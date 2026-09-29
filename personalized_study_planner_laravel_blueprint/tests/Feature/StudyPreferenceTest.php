<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_upsert_study_preference(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/student/study-preference', [
                'preferred_time_slots' => ['morning', 'evening'],
                'max_study_hours_per_day' => 6.0,
                'break_duration_minutes' => 15,
                'learning_style' => 'visual',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.learning_style', 'visual');

        // Update existing preference
        $updateResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/student/study-preference', [
                'preferred_time_slots' => ['afternoon'],
                'max_study_hours_per_day' => 8.0,
                'break_duration_minutes' => 20,
                'learning_style' => 'auditory',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.learning_style', 'auditory');

        $this->assertDatabaseHas('study_preferences', [
            'user_id' => $user->id,
            'learning_style' => 'auditory',
        ]);
    }
}
