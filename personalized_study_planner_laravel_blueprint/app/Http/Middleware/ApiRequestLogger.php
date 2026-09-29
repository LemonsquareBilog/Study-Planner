<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiRequestLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = $request->header('X-Request-ID', Str::uuid()->toString());
        
        // Add request ID to response headers
        $request->headers->set('X-Request-ID', $requestId);

        // Log request
        $this->logRequest($request, $requestId);

        try {
            $response = $next($request);
            
            // Calculate response time
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Add response headers
            $response->headers->set('X-Response-Time', $responseTime . 'ms');
            $response->headers->set('X-Request-ID', $requestId);
            
            // Log response
            $this->logResponse($request, $response, $responseTime, $requestId);
            
            // Store in database for monitoring (async)
            $this->storeRequestLog($request, $response, $responseTime, $requestId);
            
            return $response;
        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logException($request, $e, $responseTime, $requestId);
            $this->storeRequestLog($request, null, $responseTime, $requestId, $e);
            
            throw $e;
        }
    }

    /**
     * Log incoming request.
     */
    protected function logRequest(Request $request, string $requestId): void
    {
        if (!config('logging.api_requests', true)) {
            return;
        }

        $user = $request->user();
        
        Log::channel('api')->info('API Request', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'query_params' => $request->query(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
        ]);
    }

    /**
     * Log response.
     */
    protected function logResponse(Request $request, Response $response, float $responseTime, string $requestId): void
    {
        if (!config('logging.api_responses', true)) {
            return;
        }

        $user = $request->user();
        $statusCode = $response->getStatusCode();
        
        $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');
        
        Log::channel('api')->$level('API Response', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $statusCode,
            'response_time_ms' => $responseTime,
            'user_id' => $user?->id,
            'content_length' => $response->headers->get('Content-Length'),
        ]);

        // Log slow requests
        if ($responseTime > config('logging.slow_request_threshold', 1000)) {
            Log::channel('api')->warning('Slow API Request', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $request->path(),
                'response_time_ms' => $responseTime,
                'threshold_ms' => config('logging.slow_request_threshold', 1000),
            ]);
        }
    }

    /**
     * Log exception.
     */
    protected function logException(Request $request, \Throwable $e, float $responseTime, string $requestId): void
    {
        Log::channel('api')->error('API Exception', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'response_time_ms' => $responseTime,
            'user_id' => $request->user()?->id,
        ]);
    }

    /**
     * Store request log in database.
     */
    protected function storeRequestLog(Request $request, ?Response $response, float $responseTime, string $requestId, ?\Throwable $exception = null): void
    {
        if (!config('logging.store_in_database', true)) {
            return;
        }

        try {
            // Use queue for async storage
            \App\Jobs\StoreApiRequestLog::dispatch([
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => $request->user()?->id,
                'status_code' => $response?->getStatusCode() ?? ($exception ? 500 : 0),
                'response_time_ms' => $responseTime,
                'query_params' => $request->query(),
                'request_headers' => $this->sanitizeHeaders($request->headers->all()),
                'exception_class' => $exception ? get_class($exception) : null,
                'exception_message' => $exception?->getMessage(),
            ])->onQueue('logs');
        } catch (\Throwable $e) {
            // Don't let logging errors break the request
            Log::error('Failed to queue API request log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sanitize sensitive headers.
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'cookie', 'x-csrf-token', 'x-xsrf-token'];
        $sanitized = [];
        
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}