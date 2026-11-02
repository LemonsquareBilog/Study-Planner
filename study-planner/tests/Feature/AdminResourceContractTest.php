<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourceContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_ebook_filter_and_update_follow_the_canonical_backend_contract(): void
    {
        $ebook = Resource::create(['title' => 'Database Book', 'resource_type' => 'ebook', 'url' => 'https://example.test/book', 'difficulty' => 'beginner', 'is_active' => true]);
        Resource::create(['title' => 'Video', 'resource_type' => 'video', 'url' => 'https://example.test/video', 'difficulty' => 'beginner', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]), 'sanctum');
        $this->getJson('/api/admin/resources?resource_type=ebook')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ebook->id);
        $this->putJson('/api/admin/resources/'.$ebook->id, ['resource_type' => 'ebook'])->assertOk()->assertJsonPath('data.resource_type', 'ebook');
        $this->putJson('/api/admin/resources/'.$ebook->id, ['resource_type' => 'book'])->assertUnprocessable();
        $this->actingAs(User::factory()->create(['role' => 'student', 'is_active' => true]), 'sanctum');
        $this->getJson('/api/admin/resources')->assertForbidden();
    }
}
