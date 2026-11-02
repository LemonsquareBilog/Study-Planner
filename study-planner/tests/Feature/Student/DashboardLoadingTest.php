<?php

namespace Tests\Feature\Student;

use App\Models\Quiz;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\SystemSetting;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reuses_progress_totals_for_setup_and_keeps_student_data_isolated(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create(['user_id' => $user->id, 'name' => 'Science', 'difficulty' => 2, 'color' => 'blue', 'is_active' => true]);
        $otherSubject = Subject::create(['user_id' => $other->id, 'name' => 'Private', 'difficulty' => 2, 'color' => 'red', 'is_active' => true]);
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/student/dashboard')->assertOk()
            ->assertJsonPath('data.setup_status.has_preferences', false)
            ->assertJsonPath('data.setup_status.subjects_count', 1)
            ->assertJsonPath('data.setup_status.tasks_count', 0)
            ->assertJsonPath('data.setup_status.is_fully_onboarded', false);

        $user->studyPreference()->create([]);
        $task = $user->tasks()->create(['subject_id' => $subject->id, 'title' => 'Retained task', 'task_type' => 'assignment', 'priority' => 'high', 'status' => 'pending', 'deadline' => now()->addDay(), 'estimated_minutes' => 30]);
        $user->tasks()->create(['subject_id' => $subject->id, 'title' => 'Deleted task', 'task_type' => 'assignment', 'priority' => 'high', 'status' => 'pending', 'deadline' => now()->addDay(), 'estimated_minutes' => 30])->delete();
        $other->tasks()->create(['subject_id' => $otherSubject->id, 'title' => 'Private task', 'task_type' => 'assignment', 'priority' => 'high', 'status' => 'pending', 'deadline' => now()->addDay(), 'estimated_minutes' => 30]);
        $user->studySessions()->create(['subject_id' => $subject->id, 'task_id' => $task->id, 'scheduled_date' => today(), 'start_time' => '08:00', 'end_time' => '08:30', 'duration_minutes' => 30, 'status' => 'scheduled']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->getJson('/api/student/dashboard');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk()->assertJsonPath('data.progress.tasks.total', 1)
            ->assertJsonPath('data.progress.study_sessions.total', 1)
            ->assertJsonPath('data.setup_status.has_preferences', true)
            ->assertJsonPath('data.setup_status.subjects_count', 1)
            ->assertJsonPath('data.setup_status.tasks_count', 1)
            ->assertJsonPath('data.setup_status.study_sessions_count', 1)
            ->assertJsonPath('data.setup_status.quiz_attempts_count', 0)
            ->assertJsonPath('data.setup_status.is_fully_onboarded', true)
            ->assertJsonPath('data.upcoming_tasks.0.title', 'Retained task')
            ->assertJsonMissing(['title' => 'Private task']);
        $this->assertLessThanOrEqual(13, $queryCount, 'Dashboard should avoid duplicate setup count and existence queries.');
    }

    public function test_recommendations_keep_thresholds_and_weak_precedence(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'student', 'is_active' => true]);
        SystemSetting::create(['key' => 'weak_topic_threshold', 'value' => '70', 'type' => 'integer']);
        SystemSetting::create(['key' => 'review_topic_threshold', 'value' => '89', 'type' => 'integer']);
        $topics = [];
        foreach (['weak' => 65, 'review' => 80, 'strong' => 95] as $label => $score) {
            $subject = Subject::create(['user_id' => $user->id, 'name' => ucfirst($label), 'difficulty' => 2, 'color' => 'blue', 'is_active' => true]);
            $topic = Topic::create(['subject_id' => $subject->id, 'name' => ucfirst($label), 'difficulty' => 2, 'is_active' => true]);
            $quiz = Quiz::create(['topic_id' => $topic->id, 'title' => ucfirst($label), 'passing_score' => 70, 'is_active' => true]);
            $user->quizAttempts()->create(['quiz_id' => $quiz->id, 'score' => 1, 'total_questions' => 1, 'percentage' => $score, 'started_at' => now(), 'completed_at' => now()]);
            $other->quizAttempts()->create(['quiz_id' => $quiz->id, 'score' => 1, 'total_questions' => 1, 'percentage' => 100, 'started_at' => now(), 'completed_at' => now()]);
            $topics[$label] = $topic;
        }

        foreach (['weak', 'review', 'strong', 'overlap'] as $name) {
            $resource = Resource::create(['title' => ucfirst($name), 'resource_type' => 'tutorial', 'url' => 'https://example.test/'.$name, 'difficulty' => 'beginner', 'is_active' => true]);
            if ($name === 'overlap') {
                $resource->topics()->attach([$topics['weak']->id, $topics['review']->id]);
            } else {
                $resource->subjects()->attach($topics[$name]->subject_id);
            }
        }

        $this->actingAs($user, 'sanctum');
        $items = $this->getJson('/api/student/dashboard')->assertOk()->json('data.recommendations');
        $this->assertSame(['Overlap', 'Review', 'Weak'], array_column($items, 'title'));
        $this->assertSame(['weak', 'needs_review', 'weak'], array_column($items, 'recommendation_reason'));
    }
}
