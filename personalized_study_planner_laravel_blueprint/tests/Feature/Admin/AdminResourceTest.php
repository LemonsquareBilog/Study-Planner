<?php

namespace Tests\Feature\Admin;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_resource(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/resources', [
                'title' => 'Khan Academy Calculus',
                'url' => 'https://khanacademy.org/math/calculus-1',
                'type' => 'video',
                'description' => 'Calculus video tutorials',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Khan Academy Calculus');

        $this->assertDatabaseHas('resources', [
            'url' => 'https://khanacademy.org/math/calculus-1',
            'type' => 'video',
        ]);
    }
}
