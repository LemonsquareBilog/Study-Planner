<?php

namespace Tests\Feature\Student;

use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_and_dashboard_show_zeroes_for_a_new_student(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->actingAs($student, 'sanctum');

        foreach (['/api/student/progress' => 'data.summary', '/api/student/dashboard' => 'data.progress'] as $url => $prefix) {
            $this->getJson($url)->assertOk()
                ->assertJsonPath("$prefix.tasks.total", 0)
                ->assertJsonPath("$prefix.tasks.completed", 0)
                ->assertJsonPath("$prefix.tasks.completion_rate", 0)
                ->assertJsonPath("$prefix.study_sessions.total", 0)
                ->assertJsonPath("$prefix.study_sessions.completed", 0)
                ->assertJsonPath("$prefix.study_sessions.minutes_completed", 0)
                ->assertJsonPath("$prefix.quizzes.attempts", 0)
                ->assertJsonPath("$prefix.quizzes.average_percentage", 0);
        }
    }

    public function test_progress_and_dashboard_include_only_the_students_non_deleted_records(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create(['user_id' => $student->id, 'name' => 'Math', 'difficulty' => 2, 'color' => 'blue', 'is_active' => true]);
        $otherSubject = Subject::create(['user_id' => $other->id, 'name' => 'Other', 'difficulty' => 2, 'color' => 'blue', 'is_active' => true]);
        $task = ['title' => 'Assignment', 'task_type' => 'assignment', 'priority' => 'high', 'deadline' => now()->addDay(), 'estimated_minutes' => 30];
        $student->tasks()->create($task + ['subject_id' => $subject->id, 'status' => 'completed']);
        $student->tasks()->create($task + ['subject_id' => $subject->id, 'status' => 'pending']);
        $student->tasks()->create($task + ['subject_id' => $subject->id, 'status' => 'completed'])->delete();
        $other->tasks()->create($task + ['subject_id' => $otherSubject->id, 'status' => 'completed']);
        $session = ['subject_id' => $subject->id, 'scheduled_date' => today(), 'start_time' => '09:00', 'end_time' => '09:30', 'duration_minutes' => 30, 'status' => 'completed'];
        $student->studySessions()->create($session);
        $student->studySessions()->create([...$session, 'start_time' => '10:00', 'end_time' => '10:45', 'duration_minutes' => 45, 'status' => 'scheduled']);
        $other->studySessions()->create([...$session, 'subject_id' => $otherSubject->id, 'duration_minutes' => 90]);
        $topic = Topic::create(['subject_id' => $subject->id, 'name' => 'Algebra', 'difficulty' => 2, 'is_active' => true]);
        $quiz = Quiz::create(['topic_id' => $topic->id, 'title' => 'Algebra', 'passing_score' => 70, 'is_active' => true]);
        $attempt = ['quiz_id' => $quiz->id, 'score' => 1, 'total_questions' => 2, 'percentage' => 50, 'started_at' => now(), 'completed_at' => now()];
        $student->quizAttempts()->create($attempt);
        $student->quizAttempts()->create([...$attempt, 'percentage' => 75.50]);
        $other->quizAttempts()->create([...$attempt, 'percentage' => 100]);
        $this->actingAs($student, 'sanctum');

        foreach (['/api/student/progress' => 'data.summary', '/api/student/dashboard' => 'data.progress'] as $url => $prefix) {
            $this->getJson($url)->assertOk()
                ->assertJsonPath("$prefix.tasks.total", 2)
                ->assertJsonPath("$prefix.tasks.completed", 1)
                ->assertJsonPath("$prefix.tasks.completion_rate", 50)
                ->assertJsonPath("$prefix.study_sessions.total", 2)
                ->assertJsonPath("$prefix.study_sessions.completed", 1)
                ->assertJsonPath("$prefix.study_sessions.minutes_completed", 30)
                ->assertJsonPath("$prefix.quizzes.attempts", 2)
                ->assertJsonPath("$prefix.quizzes.average_percentage", 62.75);
        }
    }
}
