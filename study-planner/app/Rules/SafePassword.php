<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class SafePassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) < 15 || mb_strlen($value) > 128) {
            $fail('Use a password between 15 and 128 characters.');

            return;
        }

        if (str_contains($value, "\0")) {
            $fail('Passwords cannot contain null characters.');

            return;
        }

        // Only a five-character hash prefix leaves the server, never the password.
        $hash = strtoupper(sha1($value));
        try {
            $response = Http::connectTimeout(3)->timeout(6)
                ->withHeaders(['Add-Padding' => 'true'])
                ->get('https://api.pwnedpasswords.com/range/'.substr($hash, 0, 5));
            if (! $response->successful() || ! preg_match('/^[A-F0-9]{35}:\d+/m', $response->body())) {
                $fail('Password safety checking is temporarily unavailable. Please try again shortly.');

                return;
            }
            foreach (explode("\n", $response->body()) as $line) {
                [$suffix, $count] = array_pad(explode(':', trim($line), 2), 2, '0');
                if (hash_equals(substr($hash, 5), $suffix) && (int) $count > 0) {
                    $fail('This password appears in known breaches. Choose a different, unique password.');

                    return;
                }
            }
        } catch (\Throwable) {
            $fail('Password safety checking is temporarily unavailable. Please try again shortly.');
        }
    }
}
