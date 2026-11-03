<?php

namespace Tests\Feature\Student;

use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_aggregates_in_one_query_without_loading_students_records(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $subject = Subject::create(['user_id' => $user->id, 'name' => 'Math']);
        $topic = $subject->topics()->create(['name' => 'Algebra']);
        $quiz = Quiz::create(['topic_id' => $topic->id, 'title' => 'Practice']);
        foreach (['pending', 'completed', 'cancelled'] as $status) {
            $user->tasks()->create([
                'subject_id' => $subject->id, 'title' => $status,
                'deadline' => now()->addDay(), 'estimated_minutes' => 30, 'status' => $status,
            ]);
        }
        $user->tasks()->first()->delete();
        foreach (['completed', 'scheduled'] as $index => $status) {
            $user->studySessions()->create([
                'subject_id' => $subject->id, 'scheduled_date' => today()->addDays($index),
                'start_time' => '18:00', 'end_time' => '18:30',
                'duration_minutes' => 30, 'status' => $status,
            ]);
        }
        foreach ([50, 100] as $percentage) {
            $user->quizAttempts()->create([
                'quiz_id' => $quiz->id, 'score' => $percentage / 50,
                'total_questions' => 2, 'percentage' => $percentage,
                'started_at' => now(), 'completed_at' => now(),
            ]);
        }
        $other->quizAttempts()->create([
            'quiz_id' => $quiz->id, 'score' => 0, 'total_questions' => 2,
            'percentage' => 0, 'started_at' => now(), 'completed_at' => now(),
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $summary = app(ProgressService::class)->summary($user);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(['total' => 2, 'completed' => 1, 'completion_rate' => 50.0], $summary['tasks']);
        $this->assertSame(['total' => 2, 'completed' => 1, 'minutes_completed' => 30], $summary['study_sessions']);
        $this->assertSame(['attempts' => 2, 'average_percentage' => 75.0], $summary['quizzes']);
        $this->assertCount(1, $queries, 'Progress should use one aggregate query, not load record collections.');
    }

    public function test_empty_dashboard_keeps_zero_metrics_and_avoids_recounting_records(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->actingAs($user, 'sanctum');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->getJson('/api/student/dashboard');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk()
            ->assertJsonPath('data.progress.tasks.total', 0)
            ->assertJsonPath('data.progress.study_sessions.minutes_completed', 0)
            ->assertJsonPath('data.setup_status.is_fully_onboarded', false)
            ->assertJsonPath('data.setup_status.tasks_count', 0)
            ->assertJsonPath('data.setup_status.quiz_attempts_count', 0);
        $this->assertLessThanOrEqual(9, count($queries), 'Empty dashboard query budget exceeded.');
    }

    public function test_admin_aggregates_preserve_student_status_counts_and_empty_quiz_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        User::factory()->count(2)->create(['role' => 'student', 'is_active' => true]);
        User::factory()->create(['role' => 'student', 'is_active' => false]);
        User::factory()->create(['role' => 'student', 'is_active' => true])->delete();

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.metrics.total_students', 3)
            ->assertJsonPath('data.metrics.active_students', 2)
            ->assertJsonPath('data.metrics.total_quiz_attempts', 0)
            ->assertJsonPath('data.metrics.average_quiz_score', 0);
    }
}
