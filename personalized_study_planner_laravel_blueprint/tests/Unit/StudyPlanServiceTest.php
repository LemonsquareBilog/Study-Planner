<?php

namespace Tests\Unit;

use App\Models\Subject;
use App\Models\Task;
use App\Models\Topic;
use App\Models\User;
use App\Services\StudyPlanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_study_sessions_for_uncompleted_tasks(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['user_id' => $user->id]);
        $topic = Topic::factory()->create(['subject_id' => $subject->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'status' => 'pending',
            'due_date' => Carbon::tomorrow(),
            'estimated_minutes' => 60,
        ]);

        $service = new StudyPlanService();
        $sessions = $service->generateStudyPlan($user, Carbon::today(), Carbon::today()->addDays(7));

        $this->assertNotEmpty($sessions);
        $this->assertDatabaseHas('study_sessions', [
            'user_id' => $user->id,
            'task_id' => $task->id,
        ]);
    }
}
