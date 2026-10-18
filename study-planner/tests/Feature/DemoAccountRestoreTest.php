<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DemoAccountRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoAccountRestoreTest extends TestCase
{
    use RefreshDatabase;

    private function source(): array
    {
        $tables = array_fill_keys(['users', 'study_preferences', 'subjects', 'topics', 'tasks', 'study_sessions',
            'quizzes', 'questions', 'quiz_attempts', 'quiz_answers', 'resources', 'resource_subject',
            'resource_topic', 'notifications', 'recommendation_views'], []);
        foreach (DemoAccountRestoreService::EMAILS as $index => $email) {
            $tables['users'][] = ['id' => $index + 1, 'name' => 'Demo '.($index + 1), 'email' => $email,
                'password' => 'unused source hash', 'role' => $index === 0 ? 'admin' : 'student',
                'is_active' => true, 'deleted_at' => null];
        }
        $tables['subjects'][] = ['id' => 1, 'user_id' => 2, 'name' => 'Preserved subject', 'difficulty' => 3, 'is_active' => true];
        $tables['topics'][] = ['id' => 1, 'subject_id' => 1, 'name' => 'Preserved topic', 'difficulty' => 3, 'is_active' => true];
        $tables['tasks'][] = ['id' => 1, 'user_id' => 2, 'subject_id' => 1, 'topic_id' => 1,
            'title' => 'Preserved task', 'task_type' => 'review', 'priority' => 'medium',
            'deadline' => '2026-11-01 18:00:00', 'estimated_minutes' => 45, 'status' => 'pending'];
        $tables['study_sessions'][] = ['id' => 1, 'user_id' => 2, 'subject_id' => 1, 'topic_id' => 1,
            'task_id' => 1, 'scheduled_date' => '2026-11-01', 'start_time' => '18:00:00',
            'end_time' => '18:45:00', 'duration_minutes' => 45, 'status' => 'scheduled'];

        return ['sha256' => 'test-source', 'tables' => $tables];
    }

    private function passwords(): array
    {
        return array_fill_keys(DemoAccountRestoreService::EMAILS, 'Private fixture password 842!');
    }

    public function test_restore_preserves_real_users_remaps_relationships_and_uses_private_passwords(): void
    {
        $real = User::create(['name' => 'Real user', 'email' => 'real@example.test', 'password' => 'original private password']);
        $hash = $real->password;
        $service = new DemoAccountRestoreService;
        $source = $this->source();
        $service->plan(DB::connection(), $source);
        $this->assertDatabaseCount('users', 1); // Dry run is read-only.
        $result = $service->apply(DB::connection(), $source, $this->passwords());
        $alex = User::where('email', 'alex.chen@student.test')->firstOrFail();
        $this->assertNotSame(2, $result['mapping']['users'][2]);
        $this->assertSame($hash, $real->fresh()->password);
        $this->assertTrue(password_verify($this->passwords()[$alex->email], $alex->password));
        $this->assertDatabaseHas('tasks', ['user_id' => $alex->id, 'title' => 'Preserved task']);
        $this->assertDatabaseHas('study_sessions', ['user_id' => $alex->id,
            'task_id' => $result['mapping']['tasks'][1], 'subject_id' => $result['mapping']['subjects'][1]]);
        $this->assertDatabaseCount('users', 6);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/auth/login', ['email' => $alex->email, 'password' => $this->passwords()[$alex->email]])->assertOk();
    }

    public function test_existing_demo_account_is_never_overwritten(): void
    {
        $user = User::create(['name' => 'Existing Alex', 'email' => 'alex.chen@student.test', 'password' => 'existing private password']);
        $hash = $user->password;
        try {
            (new DemoAccountRestoreService)->apply(DB::connection(), $this->source(), $this->passwords());
            $this->fail('An existing account must stop the import.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('already exists', $error->getMessage());
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_bad_relationship_rolls_back_every_insert(): void
    {
        $source = $this->source();
        $source['tables']['tasks'][0]['subject_id'] = 999;
        try {
            (new DemoAccountRestoreService)->apply(DB::connection(), $source, $this->passwords());
            $this->fail('An unmapped relationship must stop the import.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Unmapped', $error->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('subjects', 0);
        $this->assertDatabaseCount('tasks', 0);
    }
}
