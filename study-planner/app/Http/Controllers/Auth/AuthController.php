<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new student account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            ...$request->validated(),
            'password' => Hash::make($request->string('password')),
            'role' => 'student',
        ]);

        // Auto-create sensible study preferences so new students can immediately generate study plans (UX-002)
        $user->studyPreference()->create([
            'max_daily_minutes' => 120,
            'session_duration_minutes' => 50,
            'break_duration_minutes' => 10,
            'preferred_start_time' => '18:00',
            'preferred_end_time' => '22:00',
        ]);

        $token = $user->createToken('study-planner')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $user->load('studyPreference'),
            ],
            'message' => 'Registration successful.',
        ], 201);
    }

    /**
     * Login with email and password, returning an API token.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact support at support@studyplanner.edu or your system administrator to restore access.',
            ]);
        }

        $token = $user->createToken('study-planner')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $user->load('studyPreference'),
            ],
            'message' => 'Login successful.',
        ]);
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('studyPreference');

        return response()->json([
            'data' => $user,
            'message' => 'User profile retrieved.',
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'course' => ['nullable', 'string', 'max:150'],
            'year_level' => ['nullable', 'integer', 'between:1,10'],
        ]);

        $user->update($validated);

        return response()->json([
            'data' => $user->fresh(['studyPreference']),
            'message' => 'Profile updated successfully.',
        ]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($request->string('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Your current password does not match our records.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->string('password')),
        ]);

        return response()->json([
            'data' => null,
            'message' => 'Password updated successfully.',
        ]);
    }
}
