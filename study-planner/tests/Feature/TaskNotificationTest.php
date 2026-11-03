<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Subject;
use App\Models\User;
use App\Services\TaskNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_endpoint_requires_configured_secret(): void
    {
        config(['task_reminders.cron_secret' => null]);
        $this->getJson('/api/internal/task-reminders')->assertForbidden();
        config(['task_reminders.cron_secret' => str_repeat('s', 32)]);
        $this->getJson('/api/internal/task-reminders')->assertForbidden();
        $this->withToken(str_repeat('s', 32))->getJson('/api/internal/task-reminders')->assertOk();
    }

    public function test_notification_deadline_tracks_the_current_owned_task(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create(['user_id' => $user->id, 'name' => 'Math']);
        $task = $user->tasks()->create(['subject_id' => $subject->id, 'title' => 'Read', 'deadline' => now()->subHour(), 'estimated_minutes' => 30]);
        $notification = $user->notifications()->create(['task_id' => $task->id, 'type' => 'task_reminder', 'title' => 'Read', 'message' => 'Reminder', 'delivery_status' => 'sent']);
        $this->actingAs($user, 'sanctum')->getJson('/api/student/notifications')
            ->assertOk()->assertJsonPath('data.data.0.task.deadline', $task->deadline->toJSON())
            ->assertJsonPath('data.data.0.task.status', 'pending');

        $task->update(['deadline' => now()->addDay(), 'status' => 'completed']);
        $this->getJson('/api/student/notifications')->assertOk()
            ->assertJsonPath('data.data.0.task.deadline', $task->deadline->toJSON())
            ->assertJsonPath('data.data.0.task.status', 'completed');

        $other = User::factory()->create();
        $task->update(['user_id' => $other->id]);
        $this->getJson('/api/student/notifications')->assertOk()->assertJsonPath('data.data.0.task', null);
        $notification->update(['task_id' => null]);
        $this->getJson('/api/student/notifications')->assertOk()->assertJsonPath('data.data.0.task', null);
    }

    public function test_schedule_is_persistent_idempotent_and_rescheduled(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create(['user_id' => $user->id, 'name' => 'Math']);
        $task = $user->tasks()->create(['subject_id' => $subject->id, 'title' => 'Read', 'deadline' => now()->addDays(2), 'estimated_minutes' => 30]);
        $service = app(TaskNotificationService::class);
        $service->synchronize($task, true);
        $service->synchronize($task, true);
        $this->assertSame(5, Notification::count());
        $this->actingAs($user, 'sanctum');
        $this->getJson('/api/student/notifications')->assertOk()->assertJsonPath('data.total', 1);
        $this->artisan('reminders:tasks')->assertSuccessful();
        $this->artisan('reminders:tasks')->assertSuccessful();
        $this->assertSame(1, Notification::where('delivery_status', 'sent')->count());
        $other = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->actingAs($other, 'sanctum');
        $this->getJson('/api/student/notifications')->assertOk()->assertJsonPath('data.total', 0);
        $id = Notification::first()->id;
        $this->postJson('/api/student/notifications/'.$id.'/read')->assertNotFound();
        $task->update(['deadline' => now()->addMinutes(30)]);
        $service->synchronize($task);
        $this->assertSame(2, Notification::whereNull('cancelled_at')->where('scheduled_at', '>', now())->count());
        $task->delete();
        $service->synchronize($task);
        $this->assertSame(0, Notification::whereNull('cancelled_at')->where('scheduled_at', '>', now())->count());
    }
}
