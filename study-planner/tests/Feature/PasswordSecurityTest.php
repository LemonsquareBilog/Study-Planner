<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthSessionService;
use Database\Seeders\DevelopmentAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function signup(string $password)
    {
        return $this->postJson('/api/auth/register', [
            'name' => 'Student', 'email' => 'policy@example.test',
            'password' => $password, 'password_confirmation' => $password,
        ]);
    }

    public function test_long_passphrase_is_accepted_and_hashed_without_truncation(): void
    {
        Http::fake(['*' => Http::response(str_repeat('A', 35).':0')]);
        $password = str_repeat('unique violet dusk lantern ', 4);
        $this->signup($password)->assertCreated();
        $hash = User::first()->password;
        $this->assertSame('argon2id', password_get_info($hash)['algoName']);
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify(substr($password, 0, 72), $hash));
    }

    public function test_short_and_breached_passwords_are_rejected(): void
    {
        $this->signup('Short123!')->assertUnprocessable()->assertJsonValidationErrors('password');
        $password = 'commonly breached password';
        Http::fake(['*' => Http::response(substr(strtoupper(sha1($password)), 5).':50')]);
        $this->signup($password)->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_safety_service_outage_does_not_accept_unchecked_passwords(): void
    {
        Http::fake(['*' => Http::response('', 503)]);
        $this->signup('unique violet dusk lantern')->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_development_seeding_preserves_existing_passwords_and_is_blocked_in_production(): void
    {
        config(['development.seed_password' => 'private fixture password 842!']);
        $this->seed(DevelopmentAccountsSeeder::class);
        $hash = User::first()->password;
        config(['development.seed_password' => 'another fixture password 982!']);
        $this->seed(DevelopmentAccountsSeeder::class);
        $this->assertDatabaseCount('users', 2);
        $this->assertSame($hash, User::first()->password);
        app()->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        (new DevelopmentAccountsSeeder)->run();
    }

    public function test_browser_login_sets_http_only_cookies_and_returns_no_bearer_secrets(): void
    {
        User::create(['name' => 'Student', 'email' => 'browser@example.test', 'password' => 'existing password']);
        $response = $this->withHeader('X-Study-Planner-Web', '1')->postJson('/api/auth/login', [
            'email' => 'browser@example.test', 'password' => 'existing password',
        ])->assertOk()->assertJsonPath('data.token', 'cookie-session');
        $cookies = $response->headers->getCookies();
        $this->assertCount(2, $cookies);
        foreach ($cookies as $cookie) {
            $this->assertTrue($cookie->isHttpOnly());
            $this->assertSame('strict', $cookie->getSameSite());
        }
        $this->withHeader('Origin', 'https://attacker.example')->postJson('/api/auth/login', [
            'email' => 'browser@example.test', 'password' => 'existing password',
        ])->assertForbidden();
    }

    public function test_password_change_enforces_policy_and_revokes_all_device_tokens(): void
    {
        Http::fake(['*' => Http::response(str_repeat('A', 35).':0')]);
        $user = User::create(['name' => 'Student', 'email' => 'change@example.test', 'password' => 'existing password']);
        $session = app(AuthSessionService::class)->issue($user);
        $this->withToken($session['token'])->putJson('/api/auth/password', [
            'current_password' => 'existing password', 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->withToken($session['token'])->putJson('/api/auth/password', [
            'current_password' => 'existing password', 'password' => 'unique revised violet lantern',
            'password_confirmation' => 'unique revised violet lantern',
        ])->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(password_verify('unique revised violet lantern', $user->fresh()->password));
    }

    public function test_legacy_bcrypt_login_upgrades_hash_and_accepts_email_case(): void
    {
        $user = User::create(['name' => 'Student', 'email' => 'legacy@example.test', 'password' => 'existing password']);
        DB::table('users')->where('id', $user->id)->update([
            'password' => password_hash('existing password', PASSWORD_BCRYPT),
        ]);
        $this->postJson('/api/auth/login', ['email' => 'LEGACY@example.test', 'password' => 'existing password'])->assertOk();
        $this->assertSame('argon2id', password_get_info(User::first()->password)['algoName']);
    }
}
