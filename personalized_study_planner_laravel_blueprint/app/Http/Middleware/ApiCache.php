<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiCache
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Skip if caching is disabled globally
        if (!Config::get('api-cache.enabled', true)) {
            return $next($request);
        }

        // Skip if request has cache-busting parameters
        $skipParams = Config::get('api-cache.conditional.skip_params', []);
        foreach ($skipParams as $param) {
            if ($request->has($param)) {
                return $next($request);
            }
        }

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return $next($request);
        }

        $rules = Config::get('api-cache.rules', []);
        if (!isset($rules[$routeName])) {
            return $next($request);
        }

        $rule = $rules[$routeName];
        $ttl = Config::get("api-cache.ttl.{$rule['ttl']}", 300);
        $store = Config::get('api-cache.default_store', 'redis');
        $prefix = Config::get('api-cache.prefix', 'api:v1');

        // Build cache key
        $cacheKey = $this->buildCacheKey($request, $routeName, $rule, $prefix);
        
        // Try to get cached response
        $cached = Cache::store($store)->get($cacheKey);
        
        if ($cached !== null) {
            // Add cache hit header
            $response = $this->restoreResponse($cached);
            return $response->withHeaders([
                'X-Cache' => 'HIT',
                'X-Cache-Key' => $cacheKey,
            ]);
        }

        // Process request
        $response = $next($request);

        // Only cache successful JSON responses
        if ($this->shouldCacheResponse($response, $request)) {
            $this->storeResponse($cacheKey, $response, $ttl, $rule, $store);
        }

        return $response->withHeaders([
            'X-Cache' => 'MISS',
            'X-Cache-Key' => $cacheKey,
        ]);
    }

    /**
     * Build the cache key from request and rule.
     */
    protected function buildCacheKey(Request $request, string $routeName, array $rule, string $prefix): string
    {
        $parts = [$prefix, $routeName];

        // Add vary parameters
        foreach ($rule['vary'] ?? [] as $vary) {
            if ($vary === 'user_id') {
                $parts[] = 'user:' . ($request->user()?->id ?? 'guest');
            } else {
                $value = $request->query($vary);
                if ($value !== null) {
                    $parts[] = "{$vary}:" . Str::slug($value);
                }
            }
        }

        // Add sorted query parameters for consistent keys
        $queryParams = $request->query();
        unset($queryParams['page'], $queryParams['per_page']); // Pagination handled separately
        ksort($queryParams);
        
        if (!empty($queryParams)) {
            $parts[] = 'q:' . md5(http_build_query($queryParams));
        }

        return implode(':', $parts);
    }

    /**
     * Determine if response should be cached.
     */
    protected function shouldCacheResponse(Response $response, Request $request): bool
    {
        // Only cache JSON responses
        if (!($response instanceof JsonResponse)) {
            return false;
        }

        // Only cache successful responses
        if (Config::get('api-cache.conditional.only_success', true) && $response->getStatusCode() >= 400) {
            return false;
        }

        // Don't cache if user is in skip roles
        $skipRoles = Config::get('api-cache.conditional.skip_roles', []);
        if (!empty($skipRoles) && $request->user() && in_array($request->user()->role, $skipRoles)) {
            return false;
        }

        // Don't cache responses with Set-Cookie header
        if ($response->headers->has('Set-Cookie')) {
            return false;
        }

        return true;
    }

    /**
     * Store response in cache.
     */
    protected function storeResponse(string $key, JsonResponse $response, int $ttl, array $rule, string $store): void
    {
        $data = [
            'content' => $response->getContent(),
            'status_code' => $response->getStatusCode(),
            'headers' => $this->getCacheableHeaders($response),
            'cached_at' => now()->toIso8601String(),
        ];

        Cache::store($store)->put($key, $data, $ttl);

        // Tag the cache if tags are defined
        if (!empty($rule['tags'])) {
            $tags = $this->resolveTags($rule['tags'], $request);
            Cache::store($store)->tags($tags)->put($key, $data, $ttl);
        }
    }

    /**
     * Restore response from cached data.
     */
    protected function restoreResponse(array $cached): JsonResponse
    {
        $response = new JsonResponse(
            json_decode($cached['content'], true),
            $cached['status_code']
        );

        foreach ($cached['headers'] as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }

    /**
     * Get headers that should be cached.
     */
    protected function getCacheableHeaders(JsonResponse $response): array
    {
        $cacheableHeaders = [
            'Content-Type',
            'ETag',
            'Last-Modified',
            'Cache-Control',
        ];

        $headers = [];
        foreach ($cacheableHeaders as $header) {
            if ($response->headers->has($header)) {
                $headers[$header] = $response->headers->get($header);
            }
        }

        return $headers;
    }

    /**
     * Resolve tag placeholders.
     */
    protected function resolveTags(array $tags, Request $request): array
    {
        $userId = $request->user()?->id ?? 'guest';
        $subjectId = $request->route('subject')?->id ?? $request->query('subject_id');
        
        return array_map(function ($tag) use ($userId, $subjectId) {
            return str_replace(
                ['{user_id}', '{subject_id}'],
                [$userId, $subjectId ?? 'none'],
                $tag
            );
        }, $tags);
    }
}