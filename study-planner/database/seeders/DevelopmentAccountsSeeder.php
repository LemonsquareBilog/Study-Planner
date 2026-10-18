<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Development accounts are forbidden in this environment.');
        }
        $password = config('development.seed_password');
        if (! is_string($password) || mb_strlen($password) < 15) {
            throw new \RuntimeException('Set DEV_SEED_PASSWORD to a private password of at least 15 characters.');
        }
        foreach (['developer@student.test' => 'student', 'developer@admin.test' => 'admin'] as $email => $role) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => 'Development '.ucfirst($role), 'password' => $password,
                'role' => $role, 'is_active' => true,
            ]);
            $user->studyPreference()->firstOrCreate([], [
                'max_daily_minutes' => 120, 'session_duration_minutes' => 50,
                'break_duration_minutes' => 10, 'preferred_start_time' => '18:00',
                'preferred_end_time' => '22:00',
            ]);
        }
    }
}
