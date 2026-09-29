<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function options(): JsonResponse
    {
        return response()->json([
            'data' => [
                'subjects' => Subject::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'topics' => Topic::query()->where('is_active', true)->with('subject:id,name')->orderBy('name')->get(['id', 'subject_id', 'name']),
            ],
            'message' => 'Resource options retrieved successfully.',
        ]);
    }

    /**
     * List all learning resources with their subject/topic mappings.
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

        $resources = $query->latest()->paginate(30);

        return response()->json($resources);
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
            'data' => $resource->load('subjects', 'topics'),
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
            'data' => $resource->fresh(['subjects', 'topics']),
            'message' => 'Resource updated successfully.',
        ]);
    }

    /**
     * Soft-delete a resource with confirmation payload (UX-007).
     */
    public function destroy(Resource $resource): JsonResponse
    {
        $deletedInfo = [
            'id' => $resource->id,
            'title' => $resource->title,
        ];

        $resource->delete();

        return response()->json([
            'data' => $deletedInfo,
            'message' => "Resource '{$deletedInfo['title']}' deleted successfully.",
        ], 200);
    }
}
