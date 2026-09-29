<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Resource;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    /** @test */
    public function admin_can_get_dashboard(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'users' => [
                        'total',
                        'students',
                        'admins',
                        'active',
                        'inactive',
                    ],
                    'resources' => [
                        'total',
                        'active',
                    ],
                    'subjects' => ['total'],
                    'tasks' => [
                        'total',
                        'completed',
                        'pending',
                        'completion_rate',
                    ],
                    'quizzes' => [
                        'total_attempts',
                        'average_score',
                    ],
                    'recent_registrations',
                ],
                'message',
            ]);
    }

    /** @test */
    public function admin_can_manage_resources(): void
    {
        // Create
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/resources', [
            'title' => 'Test Resource',
            'description' => 'Test description',
            'resource_type' => 'document',
            'difficulty' => 2,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $resourceId = $response->json('data.id');

        // Bulk activate
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/resources/bulk', [
            'action' => 'deactivate',
            'ids' => [$resourceId],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.affected', 1);

        // Import
        $csvContent = "title,resource_type,description,difficulty,is_active\n";
        $csvContent .= "Imported Resource,video,Imported via CSV,1,true\n";

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('resources.csv', $csvContent);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post('/api/v1/admin/resources/import', [
            'file' => $file,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function admin_can_manage_users(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        // Bulk deactivate
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/users/bulk', [
            'action' => 'deactivate',
            'ids' => [$student->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.affected', 1);

        // Export
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/admin/users/export?format=csv');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_manage_system_settings(): void
    {
        // Create settings
        SystemSetting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'type' => 'string',
            'label' => 'Test Setting',
            'group' => 'Testing',
            'description' => 'A test setting',
        ]);

        // Bulk update
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/system-settings/bulk', [
            'settings' => [
                [
                    'key' => 'test_setting',
                    'value' => 'updated_value',
                    'label' => 'Updated Test Setting',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.updated', 1);
    }

    /** @test */
    public function admin_cannot_perform_bulk_action_on_self(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/users/bulk', [
            'action' => 'deactivate',
            'ids' => [$this->admin->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}