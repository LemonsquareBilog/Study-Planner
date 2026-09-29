<?php

namespace Tests\Unit;

use App\Models\Subject;
use App\Models\Task;
use App\Models\Topic;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_overall_user_progress(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['user_id' => $user->id]);
        $topic = Topic::factory()->create(['subject_id' => $subject->id, 'mastery_percentage' => 80.0]);

        Task::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        Task::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $service = new ProgressService();
        $summary = $service->getProgressSummary($user);

        $this->assertEquals(2, $summary['total_tasks']);
        $this->assertEquals(1, $summary['completed_tasks']);
        $this->assertEquals(50.0, $summary['task_completion_rate']);
        $this->assertEquals(80.0, $summary['overall_mastery']);
    }
}
