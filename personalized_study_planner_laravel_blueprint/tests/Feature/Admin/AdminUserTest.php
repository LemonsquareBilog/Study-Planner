<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_toggle_user_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'message']);

        $toggleResponse = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$student->id}", [
                'is_active' => false,
            ]);

        $toggleResponse->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$admin->id}", [
                'is_active' => false,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);
    }

    public function test_student_cannot_access_admin_users(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student, 'sanctum')
            ->getJson('/api/admin/users');

        $response->assertStatus(403);
    }
}
