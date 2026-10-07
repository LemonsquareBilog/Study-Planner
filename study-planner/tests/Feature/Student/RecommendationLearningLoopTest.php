<?php

namespace Tests\Feature\Student;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationLearningLoopTest extends TestCase
{
    use RefreshDatabase;

    public function test_weak_topic_recommends_and_tracks_a_real_resource_then_updates_after_improvement(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create([
            'user_id' => $user->id,
            'name' => 'Java Programming',
            'difficulty' => 3,
            'color' => 'blue',
            'is_active' => true,
        ]);
        $topic = Topic::create([
            'subject_id' => $subject->id,
            'name' => 'Java loops',
            'difficulty' => 2,
            'is_active' => true,
        ]);
        $quiz = Quiz::create([
            'topic_id' => $topic->id,
            'title' => 'Java loop practice',
            'passing_score' => 70,
            'is_active' => true,
        ]);
        $resource = Resource::create([
            'title' => 'Java loop exercises',
            'description' => 'Practice for and while loops.',
            'resource_type' => 'tutorial',
            'url' => 'https://resources.example.test/java-loops',
            'difficulty' => 'beginner',
            'is_active' => true,
        ]);
        $resource->subjects()->attach($subject->id);

        QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => 1,
            'total_questions' => 4,
            'percentage' => 25,
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
        ]);

        $this->getJson('/api/student/recommendations')->assertUnauthorized();
        $this->actingAs($user, 'sanctum');

        $recommendations = $this->getJson('/api/student/recommendations')
            ->assertOk()
            ->assertJsonPath('data.0.id', $resource->id)
            ->assertJsonPath('data.0.url', 'https://resources.example.test/java-loops')
            ->assertJsonPath('data.0.recommendation_reason', 'weak');

        $this->postJson("/api/student/recommendations/{$resource->id}/view", ['topic_id' => $topic->id])
            ->assertCreated();
        $this->assertDatabaseHas('recommendation_views', [
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'topic_id' => $topic->id,
        ]);

        foreach ([100, 100, 100] as $percentage) {
            QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => (int) round($percentage / 25),
                'total_questions' => 4,
                'percentage' => $percentage,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($percentage),
            ]);
        }

        $updated = $this->getJson('/api/student/recommendations')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.reason', 'all_topics_strong');
    }
}