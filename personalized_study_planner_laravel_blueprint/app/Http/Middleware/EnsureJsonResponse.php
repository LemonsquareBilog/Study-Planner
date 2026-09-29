<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class EnsureJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to API routes
        if (!$request->is('api/*')) {
            return $next($request);
        }

        // Ensure the request accepts JSON
        $request->headers->set('Accept', 'application/json');

        try {
            $response = $next($request);
            
            // If the response is not already JSON and it's an error status, convert it
            if ($response->getStatusCode() >= 400 && !$response instanceof JsonResponse) {
                return $this->convertToJsonResponse($request, $response);
            }
            
            return $response;
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * Handle exceptions and convert to JSON response
     */
    protected function handleException(Request $request, Throwable $e): JsonResponse
    {
        // Validation errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'details' => $e->errors(),
                ],
            ], 422);
        }

        // Authentication errors
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Unauthenticated. Please log in to continue.',
                    'details' => [],
                ],
            ], 401);
        }

        // Authorization errors
        if ($e instanceof AuthorizationException) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => $e->getMessage() ?: 'You do not have permission to perform this action.',
                    'details' => [],
                ],
            ], 403);
        }

        // Not found
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'The requested resource was not found.',
                    'details' => [],
                ],
            ], 404);
        }

        // Method not allowed
        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'error' => [
                    'code' => 'METHOD_NOT_ALLOWED',
                    'message' => 'The HTTP method is not allowed for this route.',
                    'details' => [],
                ],
            ], 405);
        }

        // HTTP exceptions (403, 404, 500, etc.)
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $messages = [
                403 => 'You do not have permission to access this resource.',
                404 => 'The requested resource was not found.',
                419 => 'Your session has expired. Please refresh and try again.',
                429 => 'Too many requests. Please slow down.',
                500 => 'An internal server error occurred. Please try again later.',
                503 => 'The service is temporarily unavailable. Please try again later.',
            ];

            return response()->json([
                'error' => [
                    'code' => 'HTTP_ERROR',
                    'message' => $messages[$statusCode] ?? $e->getMessage() ?? 'An error occurred.',
                    'details' => [],
                ],
            ], $statusCode);
        }

        // Generic exception
        if (config('app.debug')) {
            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => $e->getMessage(),
                    'details' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ],
                ],
            ], 500);
        }

        return response()->json([
            'error' => [
                'code' => 'SERVER_ERROR',
                'message' => 'An unexpected error occurred. Please try again later.',
                'details' => [],
            ],
        ], 500);
    }

    /**
     * Convert a non-JSON response to JSON
     */
    protected function convertToJsonResponse(Request $request, Response $response): JsonResponse
    {
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();

        // Try to decode existing JSON content
        $data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // Already JSON-like, wrap in standard format if needed
            if (!isset($data['error']) && !isset($data['data']) && !isset($data['message'])) {
                return response()->json([
                    'error' => [
                        'code' => 'HTTP_ERROR',
                        'message' => $data['message'] ?? 'An error occurred.',
                        'details' => $data,
                    ],
                ], $statusCode);
            }
            return response()->json($data, $statusCode);
        }

        // HTML or plain text response - create standard error format
        return response()->json([
            'error' => [
                'code' => 'HTTP_ERROR',
                'message' => $this->extractMessageFromHtml($content) ?: 'An error occurred.',
                'details' => [],
            ],
        ], $statusCode);
    }

    /**
     * Extract a readable message from HTML error pages
     */
    protected function extractMessageFromHtml(string $html): ?string
    {
        // Try to extract from common Laravel error page patterns
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $matches)) {
            return strip_tags($matches[1]);
        }
        
        if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html, $matches)) {
            return strip_tags($matches[1]);
        }

        // Strip all tags and return first 200 chars
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text) ?: null;
    }
}