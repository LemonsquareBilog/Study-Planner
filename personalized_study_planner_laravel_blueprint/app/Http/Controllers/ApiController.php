<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiController extends Controller
{
    /**
     * Transform a single item using a resource class.
     */
    protected function item($item, JsonResource $resource, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'data' => new $resource($item),
            'message' => $message,
        ]);
    }

    /**
     * Transform a collection using a resource class.
     */
    protected function collection($collection, JsonResource $resource, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'data' => $resource::collection($collection),
            'message' => $message,
        ]);
    }

    /**
     * Transform a paginated collection.
     */
    protected function paginated(LengthAwarePaginator $paginator, JsonResource $resource, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'data' => $resource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'message' => $message,
        ]);
    }

    /**
     * Success response with data.
     */
    protected function success($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * Error response.
     */
    protected function error(string $code, string $message, array $details = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }

    /**
     * Validation error response.
     */
    protected function validationError(array $errors, string $message = 'The given data was invalid.'): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $message,
                'details' => $errors,
            ],
        ], 422);
    }

    /**
     * Not found response.
     */
    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error('NOT_FOUND', $message, [], 404);
    }

    /**
     * Unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return $this->error('UNAUTHORIZED', $message, [], 401);
    }

    /**
     * Forbidden response.
     */
    protected function forbidden(string $message = 'You do not have permission to perform this action.'): JsonResponse
    {
        return $this->error('FORBIDDEN', $message, [], 403);
    }
}