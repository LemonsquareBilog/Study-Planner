<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_aggregates_preserve_metrics_and_bound_query_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        User::factory()->create(['role' => 'student', 'is_active' => false]);
        $deleted = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $deleted->delete();
        User::factory()->create(['role' => 'instructor', 'is_active' => true]);
        $subject = Subject::create(['user_id' => $student->id, 'name' => 'Science', 'difficulty' => 2, 'color' => 'blue', 'is_active' => true]);
        $topic = Topic::create(['subject_id' => $subject->id, 'name' => 'Atoms', 'difficulty' => 2, 'is_active' => true]);
        $quiz = Quiz::create(['topic_id' => $topic->id, 'title' => 'Practice', 'passing_score' => 70, 'is_active' => true]);
        $student->quizAttempts()->create(['quiz_id' => $quiz->id, 'score' => 1, 'total_questions' => 1, 'percentage' => 33.33, 'started_at' => now(), 'completed_at' => now()]);
        $student->quizAttempts()->create(['quiz_id' => $quiz->id, 'score' => 1, 'total_questions' => 1, 'percentage' => 66.67, 'started_at' => now(), 'completed_at' => now()]);
        Resource::create(['title' => 'Active', 'resource_type' => 'article', 'url' => 'https://example.test/a', 'difficulty' => 'beginner', 'is_active' => true]);
        $deletedResource = Resource::create(['title' => 'Deleted', 'resource_type' => 'article', 'url' => 'https://example.test/b', 'difficulty' => 'beginner', 'is_active' => true]);
        $deletedResource->delete();
        $this->actingAs($admin, 'sanctum');

        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->getJson('/api/admin/dashboard')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertJsonPath('data.metrics.total_students', 2)
            ->assertJsonPath('data.metrics.active_students', 1)
            ->assertJsonPath('data.metrics.total_resources', 1)
            ->assertJsonPath('data.metrics.total_subjects', 1)
            ->assertJsonPath('data.metrics.total_quizzes', 1)
            ->assertJsonPath('data.metrics.total_quiz_attempts', 2)
            ->assertJsonPath('data.metrics.average_quiz_score', 50)
            ->assertJsonCount(2, 'data.recent_attempts');
        $this->assertLessThanOrEqual(9, count($queries), 'The dashboard should avoid redundant metric queries.');
    }

    public function test_dashboard_requires_an_active_admin_and_empty_metrics_are_zero(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'student', 'is_active' => true]), 'sanctum');
        $this->getJson('/api/admin/dashboard')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]), 'sanctum');
        $this->getJson('/api/admin/dashboard')->assertOk()
            ->assertJsonPath('data.metrics.total_quiz_attempts', 0)
            ->assertJsonPath('data.metrics.average_quiz_score', 0);
    }
}
