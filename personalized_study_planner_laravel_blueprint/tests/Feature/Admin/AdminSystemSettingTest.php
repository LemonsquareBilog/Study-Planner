<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_system_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = SystemSetting::create([
            'key' => 'reminder_lead_minutes',
            'value' => '30',
            'type' => 'integer',
            'description' => 'Lead time for reminders',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/system-settings/{$setting->id}", [
                'value' => '60',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.value', '60');

        $this->assertDatabaseHas('system_settings', [
            'id' => $setting->id,
            'value' => '60',
        ]);
    }
}
