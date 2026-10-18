<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MobileSessionTest extends TestCase
{
    use RefreshDatabase;

    private function login(): array
    {
        User::firstOrCreate(['email' => 'session@example.test'], ['name' => 'Student', 'password' => 'Password123!']);
        Auth::forgetGuards();

        return $this->postJson('/api/auth/login', ['email' => 'session@example.test', 'password' => 'Password123!'])
            ->assertOk()->json('data');
    }

    public function test_expired_access_token_can_be_renewed_and_refresh_token_cannot_be_replayed(): void
    {
        $session = $this->login();
        PersonalAccessToken::findToken($session['token'])->update(['expires_at' => now()->subMinute()]);
        Auth::forgetGuards();
        $this->withToken($session['token'])->getJson('/api/auth/me')->assertUnauthorized();
        $renewed = $this->postJson('/api/auth/refresh', ['refresh_token' => $session['refresh_token']])->assertOk()->json('data');
        Auth::forgetGuards();
        $this->withToken($renewed['token'])->getJson('/api/auth/me')->assertOk();
        $this->postJson('/api/auth/refresh', ['refresh_token' => $session['refresh_token']])->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 2);
    }

    public function test_refresh_tokens_cannot_access_application_endpoints(): void
    {
        $session = $this->login();
        Auth::forgetGuards();
        $this->withToken($session['refresh_token'])->getJson('/api/auth/me')->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken($session['refresh_token'])->getJson('/api/student/dashboard')->assertUnauthorized();
    }

    public function test_logout_revokes_both_tokens_without_logging_out_another_device(): void
    {
        $first = $this->login();
        $second = $this->login();
        Auth::forgetGuards();
        $this->withToken($first['token'])->postJson('/api/auth/logout')->assertNoContent();
        $this->postJson('/api/auth/refresh', ['refresh_token' => $first['refresh_token']])->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken($first['token'])->getJson('/api/auth/me')->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken($second['token'])->getJson('/api/auth/me')->assertOk();
    }

    public function test_expired_and_deactivated_sessions_cannot_refresh(): void
    {
        $first = $this->login();
        PersonalAccessToken::findToken($first['refresh_token'])->update(['expires_at' => now()->subMinute()]);
        $this->postJson('/api/auth/refresh', ['refresh_token' => $first['refresh_token']])->assertUnauthorized();
        $second = $this->login();
        User::first()->update(['is_active' => false]);
        $this->postJson('/api/auth/refresh', ['refresh_token' => $second['refresh_token']])->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken($second['token'])->getJson('/api/auth/me')->assertForbidden();
    }

    public function test_native_cors_preflight_allows_authorization_but_untrusted_origin_is_rejected(): void
    {
        $headers = ['Origin' => 'https://localhost', 'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type'];
        $this->options('/api/auth/login', [], $headers)->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://localhost');
        $headers['Origin'] = 'https://untrusted.example';
        $this->options('/api/auth/login', [], $headers)->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_repeated_login_attempts_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/api/auth/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])
                ->assertUnprocessable();
        }
        $this->postJson('/api/auth/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }
}
