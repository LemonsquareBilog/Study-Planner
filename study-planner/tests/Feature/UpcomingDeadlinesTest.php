<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpcomingDeadlinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_show_only_owned_unfinished_future_tasks_in_deadline_order(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create(['user_id' => $user->id, 'name' => 'Math']);
        foreach ([3, 1, 2, 4, 5, 6] as $days) {
            $user->tasks()->create(['subject_id' => $subject->id, 'title' => 'Task '.$days, 'deadline' => now()->addDays($days), 'estimated_minutes' => 30]);
        }
        foreach (['completed', 'cancelled'] as $status) {
            $user->tasks()->create(['subject_id' => $subject->id, 'title' => $status, 'status' => $status, 'deadline' => now()->addHour(), 'estimated_minutes' => 30]);
        }
        $user->tasks()->create(['subject_id' => $subject->id, 'title' => 'Expired', 'deadline' => now()->subMinute(), 'estimated_minutes' => 30]);
        $this->actingAs($user, 'sanctum')->getJson('/api/student/notifications?unread=1')
            ->assertOk()->assertJsonCount(5, 'data.upcoming_deadlines')
            ->assertJsonPath('data.upcoming_deadlines.0.title', 'Task 1')
            ->assertJsonPath('data.upcoming_deadlines.4.title', 'Task 5');
        $other = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->actingAs($other, 'sanctum')->getJson('/api/student/notifications')->assertOk()->assertJsonCount(0, 'data.upcoming_deadlines');
    }
}
