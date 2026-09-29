<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e): Response
    {
        if ($request->is('api/*')) {
            return $this->renderApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function renderApiException(Request $request, Throwable $e): Response
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

        // HTTP exceptions
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
}