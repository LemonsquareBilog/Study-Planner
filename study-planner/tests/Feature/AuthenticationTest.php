<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_an_account_that_can_sign_in_and_restore_its_session(): void
    {
        $credentials = ['email' => 'new-student@example.test', 'password' => 'Password123!'];
        $registration = $this->postJson('/api/auth/register', [
            ...$credentials,
            'name' => 'New Student',
            'password_confirmation' => $credentials['password'],
        ]);

        $registration->assertCreated()->assertJsonPath('data.user.role', 'student');
        $this->assertDatabaseHas('study_preferences', ['user_id' => $registration->json('data.user.id')]);
        $this->assertTrue(Hash::check($credentials['password'], User::first()->password));

        $login = $this->postJson('/api/auth/login', $credentials);
        $login->assertOk()->assertJsonPath('data.user.email', $credentials['email']);
        $this->withToken($login->json('data.token'))->getJson('/api/auth/me')
            ->assertOk()->assertJsonPath('data.email', $credentials['email']);
    }

    public function test_invalid_credentials_return_validation_errors_without_issuing_a_token(): void
    {
        User::create(['name' => 'Student', 'email' => 'student@example.test', 'password' => 'Password123!']);

        $this->postJson('/api/auth/login', ['email' => 'student@example.test', 'password' => 'wrong-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/auth/login', ['email' => 'missing@example.test', 'password' => 'Password123!'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_deactivated_accounts_cannot_sign_in(): void
    {
        User::create([
            'name' => 'Student', 'email' => 'inactive@example.test',
            'password' => 'Password123!', 'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', ['email' => 'inactive@example.test', 'password' => 'Password123!'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_registration_does_not_replace_an_existing_accounts_credentials(): void
    {
        User::create(['name' => 'Student', 'email' => 'student@example.test', 'password' => 'Original123!']);

        $this->postJson('/api/auth/register', [
            'name' => 'Another Student', 'email' => 'student@example.test',
            'password' => 'Different123!', 'password_confirmation' => 'Different123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('Original123!', User::first()->password));
    }
}
