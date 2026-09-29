<?php

namespace App\Http\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ResponseTransformer
{
    /**
     * Transform a single item using a resource class.
     */
    public static function item($item, string $resourceClass): array
    {
        return (new $resourceClass($item))->toArray(request());
    }

    /**
     * Transform a collection using a resource class.
     */
    public static function collection(Collection $collection, string $resourceClass): array
    {
        return $collection->map(fn ($item) => (new $resourceClass($item))->toArray(request()))->toArray();
    }

    /**
     * Transform a paginated collection.
     */
    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass): array
    {
        return [
            'data' => self::collection($paginator->getCollection(), $resourceClass),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * Transform data with conditional fields based on request.
     */
    public static function conditional($data, array $fields, array $allowedFields = []): array
    {
        if (empty($fields)) {
            return $data;
        }

        $allowed = !empty($allowedFields) ? array_intersect($fields, $allowedFields) : $fields;
        
        return array_intersect_key($data, array_flip($allowed));
    }

    /**
     * Transform data with relationships.
     */
    public static function withRelations($item, array $relations = [], array $relationMap = []): array
    {
        $data = $item->toArray();
        
        foreach ($relations as $relation) {
            $mapKey = $relationMap[$relation] ?? $relation;
            
            if ($item->relationLoaded($relation)) {
                $related = $item->getRelation($relation);
                
                if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
                    $data[$mapKey] = $related->toArray();
                } elseif ($related) {
                    $data[$mapKey] = $related->toArray();
                } else {
                    $data[$mapKey] = null;
                }
            }
        }

        return $data;
    }

    /**
     * Create a standard API response.
     */
    public static function success($data = null, string $message = 'Success', int $status = 200): array
    {
        $response = [
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }

    /**
     * Create an error response.
     */
    public static function error(string $code, string $message, array $details = [], int $status = 400): array
    {
        return [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ];
    }

    /**
     * Create a validation error response.
     */
    public static function validationError(array $errors, string $message = 'The given data was invalid.'): array
    {
        return [
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $message,
                'details' => $errors,
            ],
        ];
    }
}