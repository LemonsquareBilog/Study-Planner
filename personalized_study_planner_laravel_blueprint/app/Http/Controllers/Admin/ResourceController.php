<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResourceController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService) {}

    /**
     * List all learning resources with their subject/topic mappings.
     * Supports: ?resource_type=video, ?difficulty=2, ?search=keyword, ?page=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = Resource::with(['subjects:id,name', 'topics:id,name']);

        if ($request->filled('resource_type')) {
            $query->where('resource_type', $request->input('resource_type'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->input('difficulty'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 30);
        $resources = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => ResourceResource::collection($resources),
            'message' => 'Resources retrieved successfully.',
        ]);
    }

    /**
     * Create a new learning resource and sync pivot relationships.
     */
    public function store(StoreResourceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $resource = Resource::create($data);
        $resource->subjects()->sync($data['subject_ids'] ?? []);
        $resource->topics()->sync($data['topic_ids'] ?? []);

        return response()->json([
            'data' => new ResourceResource($resource->load('subjects', 'topics')),
            'message' => 'Resource created successfully.',
        ], 201);
    }

    /**
     * Update an existing learning resource and re-sync pivots.
     */
    public function update(UpdateResourceRequest $request, Resource $resource): JsonResponse
    {
        $data = $request->validated();

        $resource->update($data);

        if (array_key_exists('subject_ids', $data)) {
            $resource->subjects()->sync($data['subject_ids']);
        }
        if (array_key_exists('topic_ids', $data)) {
            $resource->topics()->sync($data['topic_ids']);
        }

        return response()->json([
            'data' => new ResourceResource($resource->fresh(['subjects', 'topics'])),
            'message' => 'Resource updated successfully.',
        ]);
    }

    /**
     * Soft-delete a resource.
     */
    public function destroy(Resource $resource): JsonResponse
    {
        $resourceTitle = $resource->title;
        $resourceId = $resource->id;

        $resource->delete();

        return response()->json([
            'data' => [
                'id' => $resourceId,
                'title' => $resourceTitle,
            ],
            'message' => "Resource '{$resourceTitle}' deleted successfully.",
        ]);
    }

    /**
     * Restore a soft-deleted resource.
     */
    public function restore($resourceId): JsonResponse
    {
        $resource = Resource::withTrashed()->whereKey($resourceId)->firstOrFail();
        $resource->restore();

        return response()->json([
            'data' => new ResourceResource($resource->fresh(['subjects', 'topics'])),
            'message' => "Resource '{$resource->title}' restored successfully.",
        ]);
    }

    /**
     * Upload file for a resource.
     */
    public function uploadFile(Request $request, Resource $resource): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB
            'resource_type' => ['sometimes', 'in:document,video,audio,image'],
        ]);

        $options = [
            'prefix' => "resource_{$resource->id}",
            'subdir' => 'resources/' . $resource->id,
            'allowed_mimes' => $this->getAllowedMimes($request->input('resource_type')),
        ];

        try {
            $result = $this->fileUploadService->upload($request->file('file'), $options);
            
            // Update resource with file info
            $resource->update([
                'file_path' => $result['path'],
                'file_url' => $result['url'],
                'file_name' => $result['original_name'],
                'file_size' => $result['size'],
                'file_mime_type' => $result['mime_type'],
            ]);

            return response()->json([
                'data' => [
                    'resource' => new ResourceResource($resource->fresh(['subjects', 'topics'])),
                    'file' => $result,
                ],
                'message' => 'File uploaded successfully.',
            ]);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete resource file.
     */
    public function deleteFile(Resource $resource): JsonResponse
    {
        if (!$resource->file_path) {
            return response()->json([
                'message' => 'No file to delete.',
            ], 404);
        }

        $this->fileUploadService->delete($resource->file_path);

        $resource->update([
            'file_path' => null,
            'file_url' => null,
            'file_name' => null,
            'file_size' => null,
            'file_mime_type' => null,
        ]);

        return response()->json([
            'message' => 'File deleted successfully.',
        ]);
    }

    /**
     * Get temporary upload URL for direct browser uploads.
     */
    public function getUploadUrl(Request $request, Resource $resource): JsonResponse
    {
        $request->validate([
            'resource_type' => ['sometimes', 'in:document,video,audio,image'],
            'expires_in' => ['sometimes', 'integer', 'min:5', 'max:1440'], // 5 min to 24 hours
        ]);

        $options = [
            'prefix' => "resource_{$resource->id}",
            'subdir' => 'resources/' . $resource->id,
            'extension' => $request->input('extension', 'tmp'),
            'expires_in' => $request->integer('expires_in', 30),
        ];

        $result = $this->fileUploadService->getTemporaryUploadUrl($options);

        return response()->json([
            'data' => $result,
            'message' => 'Temporary upload URL generated.',
        ]);
    }

    /**
     * Bulk actions on resources (activate, deactivate, delete, restore).
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete,restore'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:resources,id'],
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        $count = 0;

        DB::transaction(function () use ($action, $ids, &$count) {
            $resources = Resource::withTrashed()->whereIn('id', $ids)->get();

            foreach ($resources as $resource) {
                match ($action) {
                    'activate' => $resource->update(['is_active' => true]),
                    'deactivate' => $resource->update(['is_active' => false]),
                    'delete' => $resource->delete(),
                    'restore' => $resource->restore(),
                };
                $count++;
            }
        });

        return response()->json([
            'data' => ['affected' => $count],
            'message' => "{$count} resource(s) {$action}d successfully.",
        ]);
    }

    /**
     * Import resources from CSV/JSON file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,json', 'max:10240'],
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($file, $extension, &$imported, &$errors) {
            if ($extension === 'csv') {
                $handle = fopen($file->getRealPath(), 'r');
                $headers = fgetcsv($handle);
                
                $requiredHeaders = ['title', 'resource_type'];
                foreach ($requiredHeaders as $required) {
                    if (!in_array($required, $headers)) {
                        throw ValidationException::withMessages([
                            'file' => "CSV must contain required columns: " . implode(', ', $requiredHeaders),
                        ]);
                    }
                }

                while (($row = fgetcsv($handle)) !== false) {
                    $data = array_combine($headers, $row);
                    
                    try {
                        $resource = Resource::create([
                            'title' => $data['title'] ?? '',
                            'description' => $data['description'] ?? null,
                            'resource_type' => $data['resource_type'] ?? 'link',
                            'url' => $data['url'] ?? null,
                            'difficulty' => (int) ($data['difficulty'] ?? 1),
                            'is_active' => filter_var($data['is_active'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
                            'metadata' => isset($data['metadata']) ? json_decode($data['metadata'], true) : null,
                        ]);

                        if (!empty($data['subject_ids'])) {
                            $resource->subjects()->sync(explode(',', $data['subject_ids']));
                        }
                        if (!empty($data['topic_ids'])) {
                            $resource->topics()->sync(explode(',', $data['topic_ids']));
                        }

                        $imported++;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'row' => $data,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
                fclose($handle);
            } elseif ($extension === 'json') {
                $content = json_decode(file_get_contents($file->getRealPath()), true);
                
                if (!is_array($content)) {
                    throw ValidationException::withMessages([
                        'file' => 'JSON file must contain an array of resources.',
                    ]);
                }

                foreach ($content as $index => $data) {
                    try {
                        $resource = Resource::create([
                            'title' => $data['title'] ?? '',
                            'description' => $data['description'] ?? null,
                            'resource_type' => $data['resource_type'] ?? 'link',
                            'url' => $data['url'] ?? null,
                            'difficulty' => (int) ($data['difficulty'] ?? 1),
                            'is_active' => $data['is_active'] ?? true,
                            'metadata' => $data['metadata'] ?? null,
                        ]);

                        if (!empty($data['subject_ids'])) {
                            $resource->subjects()->sync($data['subject_ids']);
                        }
                        if (!empty($data['topic_ids'])) {
                            $resource->topics()->sync($data['topic_ids']);
                        }

                        $imported++;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'index' => $index,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }
        });

        return response()->json([
            'data' => [
                'imported' => $imported,
                'errors' => $errors,
            ],
            'message' => "Imported {$imported} resource(s)." . (count($errors) > 0 ? " {$count($errors)} error(s)." : ''),
        ], count($errors) > 0 ? 207 : 201);
    }

    /**
     * Get allowed MIME types for resource type.
     */
    protected function getAllowedMimes(?string $resourceType): array
    {
        return match ($resourceType) {
            'document' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
            ],
            'video' => [
                'video/mp4',
                'video/webm',
                'video/quicktime',
            ],
            'audio' => [
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
            ],
            'image' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ],
            default => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'video/mp4',
                'video/webm',
                'audio/mpeg',
                'audio/wav',
                'text/plain',
                'text/csv',
            ],
        };
    }
}
