<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\RouteNotFoundException;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (getenv('VERCEL') === '1') {
            $middleware->trustProxies(at: '*');
        }
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            Log::info('AuthenticationException caught', [
                'is_api' => $request->is('api/*'),
                'expects_json' => $request->expectsJson(),
                'url' => $request->url(),
            ]);

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
        });

        $exceptions->render(function (RouteNotFoundException $e, Request $request) {
            Log::info('RouteNotFoundException caught', [
                'message' => $e->getMessage(),
                'is_api' => $request->is('api/*'),
                'expects_json' => $request->expectsJson(),
                'url' => $request->url(),
            ]);

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            Log::info('Throwable caught', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'is_api' => $request->is('api/*'),
                'expects_json' => $request->expectsJson(),
                'url' => $request->url(),
            ]);
        });
    })
    ->create();

// Configure Authenticate middleware to not redirect for API routes
Authenticate::redirectUsing(function (Request $request) {
    if ($request->is('api/*') || $request->expectsJson()) {
        return null;
    }

    return route('login');
});
