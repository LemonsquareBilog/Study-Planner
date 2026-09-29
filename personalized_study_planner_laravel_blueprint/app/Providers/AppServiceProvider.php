<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PragmaRX\Google2FA\Google2FA;
use App\Http\Middleware\RateLimitTier;
use App\Http\Middleware\EnsureJsonResponse;
use App\Http\Middleware\ApiCache;
use App\Http\Middleware\ApiRequestLogger;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Google2FA::class, function () {
            $google2fa = new Google2FA();
            $google2fa->setWindow(4); // Allow 4 windows (2 minutes each side) for clock drift
            return $google2fa;
        });
    }

    public function boot(Router $router): void
    {
        Request::macro('isStudent', fn () => $this->user()?->role === 'student');
        
        // Register middleware aliases
        $router->aliasMiddleware('ratelimit.tier', RateLimitTier::class);
        $router->aliasMiddleware('json.response', EnsureJsonResponse::class);
        $router->aliasMiddleware('api.cache', ApiCache::class);
        $router->aliasMiddleware('api.logger', ApiRequestLogger::class);
        
        // Add response macros for consistent API responses
        \Illuminate\Http\JsonResponse::macro('success', function ($data, string $message = 'Success', int $status = 200) {
            return response()->json([
                'data' => $data,
                'message' => $message,
            ], $status);
        });

        \Illuminate\Http\JsonResponse::macro('error', function (string $code, string $message, array $details = [], int $status = 400) {
            return response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'details' => $details,
                ],
            ], $status);
        });
    }
}
