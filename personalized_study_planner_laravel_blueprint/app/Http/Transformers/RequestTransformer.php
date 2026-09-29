<?php

namespace App\Http\Transformers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class RequestTransformer
{
    /**
     * Transform the incoming request data.
     */
    abstract public function transform(Request $request): array;

    /**
     * Get only the specified keys from the request.
     */
    protected function only(Request $request, array $keys): array
    {
        return $request->only($keys);
    }

    /**
     * Get all request data except specified keys.
     */
    protected function except(Request $request, array $keys): array
    {
        return $request->except($keys);
    }

    /**
     * Transform a collection of items.
     */
    protected function transformCollection(Collection $items, callable $transformer): array
    {
        return $items->map($transformer)->toArray();
    }

    /**
     * Cast values to appropriate types.
     */
    protected function cast(array $data, array $casts): array
    {
        foreach ($casts as $key => $type) {
            if (!isset($data[$key])) {
                continue;
            }

            $data[$key] = match ($type) {
                'int', 'integer' => (int) $data[$key],
                'float', 'double' => (float) $data[$key],
                'bool', 'boolean' => filter_var($data[$key], FILTER_VALIDATE_BOOLEAN),
                'string' => (string) $data[$key],
                'array' => is_array($data[$key]) ? $data[$key] : [],
                'json' => json_decode($data[$key], true),
                default => $data[$key],
            };
        }

        return $data;
    }

    /**
     * Sanitize string values.
     */
    protected function sanitizeStrings(array $data, array $exclude = []): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && !in_array($key, $exclude)) {
                $data[$key] = trim(strip_tags($value));
            }
        }

        return $data;
    }

    /**
     * Convert snake_case keys to camelCase.
     */
    protected function toCamelCase(array $data): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            $camelKey = str($key)->camel()->toString();
            
            if (is_array($value)) {
                $result[$camelKey] = $this->toCamelCase($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Convert camelCase keys to snake_case.
     */
    protected function toSnakeCase(array $data): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            $snakeKey = str($key)->snake()->toString();
            
            if (is_array($value)) {
                $result[$snakeKey] = $this->toSnakeCase($value);
            } else {
                $result[$snakeKey] = $value;
            }
        }

        return $result;
    }
}