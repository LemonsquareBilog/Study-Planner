<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = app(Google2FA::class);
    }

    /**
     * Get password requirements for registration.
     */
    public function passwordRules(): JsonResponse
    {
        return response()->json([
            'data' => [
                'requirements' => [
                    'Minimum 8 characters',
                    'At least one uppercase letter',
                    'At least one lowercase letter',
                    'At least one number',
                    'At least one symbol (!@#$%^&*...)',
                ],
                'min_length' => 8,
                'requires_uppercase' => true,
                'requires_lowercase' => true,
                'requires_numbers' => true,
                'requires_symbols' => true,
            ],
            'message' => 'Password requirements retrieved successfully.',
        ]);
    }

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

        $token = $user->createToken('study-planner')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
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
            'two_factor_code' => ['nullable', 'string', 'size:6'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact support at support@studyplanner.test to resolve this.',
            ]);
        }

        // Check 2FA if enabled
        if ($user->hasTwoFactorEnabled()) {
            $code = $data['two_factor_code'] ?? null;
            
            if (!$code) {
                return response()->json([
                    'error' => [
                        'code' => 'TWO_FACTOR_REQUIRED',
                        'message' => 'Two-factor authentication code is required.',
                        'details' => [],
                    ],
                ], 422);
            }

            if (!$this->google2fa->verifyKey($user->two_factor_secret, $code)) {
                // Check recovery codes
                $recoveryCodes = $user->two_factor_recovery_codes ?? [];
                $codeIndex = array_search($code, $recoveryCodes);
                
                if ($codeIndex === false) {
                    throw ValidationException::withMessages([
                        'two_factor_code' => 'Invalid two-factor authentication code.',
                    ]);
                }
                
                // Remove used recovery code
                unset($recoveryCodes[$codeIndex]);
                $user->update(['two_factor_recovery_codes' => array_values($recoveryCodes)]);
            }
        }

        $token = $user->createToken('study-planner')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
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
            'data' => new UserResource($user),
            'message' => 'User profile retrieved.',
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'course' => ['nullable', 'string', 'max:150'],
            'year_level' => ['nullable', 'integer', 'between:1,8'],
        ]);

        $user->update($data);

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => 'Profile updated successfully.',
        ]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'data' => null,
            'message' => 'Password updated successfully.',
        ]);
    }

    /**
     * Send a password reset link to the user's email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Password reset link sent to your email.'])
            : response()->json(['message' => 'Unable to send reset link. Please try again.'], 500);
    }

    /**
     * Reset the user's password using a token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(\Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset successfully.'])
            : response()->json(['message' => 'Invalid or expired reset token.'], 400);
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable2FA(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'error' => [
                    'code' => 'ALREADY_ENABLED',
                    'message' => 'Two-factor authentication is already enabled.',
                    'details' => [],
                ],
            ], 422);
        }

        $secret = $this->google2fa->generateSecretKey();
        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::random(10))->toArray();

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        $qrCodeUrl = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'data' => [
                'secret' => $secret,
                'qr_code_svg' => $qrCodeUrl,
                'recovery_codes' => $recoveryCodes,
            ],
            'message' => 'Two-factor authentication setup initiated. Scan the QR code with your authenticator app and verify with a code to complete setup.',
        ]);
    }

    /**
     * Verify and confirm two-factor authentication setup.
     */
    public function verify2FA(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (!$this->google2fa->verifyKey($user->two_factor_secret, $request->code)) {
            throw ValidationException::withMessages([
                'code' => 'Invalid verification code.',
            ]);
        }

        $user->update([
            'two_factor_confirmed_at' => now(),
        ]);

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => 'Two-factor authentication enabled successfully.',
        ]);
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable2FA(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The password is incorrect.',
            ]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => 'Two-factor authentication disabled successfully.',
        ]);
    }

    /**
     * Get the QR code for 2FA setup.
     */
    public function get2FAQR(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_INITIALIZED',
                    'message' => 'Two-factor authentication not initialized. Call enable2FA first.',
                    'details' => [],
                ],
            ], 422);
        }

        $qrCodeUrl = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $user->two_factor_secret
        );

        return response()->json([
            'data' => [
                'qr_code_svg' => $qrCodeUrl,
                'secret' => $user->two_factor_secret,
            ],
            'message' => 'QR code retrieved successfully.',
        ]);
    }
}
