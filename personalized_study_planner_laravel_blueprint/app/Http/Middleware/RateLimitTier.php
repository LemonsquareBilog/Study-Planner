<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class RateLimitTier
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip rate limiting for health checks
        if ($request->is('health*') || $request->is('api/health*')) {
            return $next($request);
        }

        $tier = $this->resolveTier($request);
        $config = Config::get("ratelimit.tiers.{$tier}", Config::get('ratelimit.tiers.standard'));
        
        $key = $this->resolveKey($request, $tier);
        $maxAttempts = $config['limit'];
        $decayMinutes = $config['window'] / 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            return $this->buildRateLimitResponse($request, $config, $retryAfter, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $key, $maxAttempts, $config);
    }

    /**
     * Resolve the rate limiting tier for the request.
     */
    protected function resolveTier(Request $request): string
    {
        $routeTiers = Config::get('ratelimit.route_tiers', []);
        $routeName = $request->route()?->getName();
        
        // Check exact match first
        if ($routeName && isset($routeTiers[$routeName])) {
            return $routeTiers[$routeName];
        }

        // Check pattern matches
        if ($routeName) {
            foreach ($routeTiers as $pattern => $tier) {
                if ($pattern !== 'default' && fnmatch($pattern, $routeName)) {
                    return $tier;
                }
            }
        }

        // Check by route prefix
        $prefix = $this->getRoutePrefix($request);
        if ($prefix && isset($routeTiers[$prefix . '.*'])) {
            return $routeTiers[$prefix . '.*'];
        }

        return $routeTiers['default'] ?? 'standard';
    }

    /**
     * Get the route prefix (e.g., 'admin', 'student', 'auth').
     */
    protected function getRoutePrefix(Request $request): ?string
    {
        $route = $request->route();
        if (!$route) return null;

        $action = $route->getAction();
        $namespace = $action['namespace'] ?? '';
        
        if (str_contains($namespace, 'Admin')) return 'admin';
        if (str_contains($namespace, 'Student')) return 'student';
        if (str_contains($namespace, 'Auth')) return 'auth';

        return null;
    }

    /**
     * Resolve the rate limiting key.
     */
    protected function resolveKey(Request $request, string $tier): string
    {
        $ip = $request->ip();
        $userId = $request->user()?->id;
        
        // Use user ID if authenticated, otherwise IP
        $identifier = $userId ? "user:{$userId}" : "ip:{$ip}";
        
        return "ratelimit:{$tier}:{$identifier}";
    }

    /**
     * Build the rate limit exceeded response.
     */
    protected function buildRateLimitResponse(Request $request, array $config, int $retryAfter, int $maxAttempts): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => $config['message'],
                'details' => [
                    'retry_after_seconds' => $retryAfter,
                    'limit' => $maxAttempts,
                    'window_seconds' => $config['window'],
                ],
            ],
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => time() + $retryAfter,
        ]);
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addRateLimitHeaders(Response $response, string $key, int $maxAttempts, array $config): Response
    {
        if (!Config::get('ratelimit.headers.enabled', true)) {
            return $response;
        }

        $remaining = max(0, $maxAttempts - RateLimiter::attempts($key));
        $resetTime = time() + RateLimiter::availableIn($key);

        return $response->withHeaders([
            Config::get('ratelimit.headers.limit_header') => $maxAttempts,
            Config::get('ratelimit.headers.remaining_header') => $remaining,
            Config::get('ratelimit.headers.reset_header') => $resetTime,
        ]);
    }
}