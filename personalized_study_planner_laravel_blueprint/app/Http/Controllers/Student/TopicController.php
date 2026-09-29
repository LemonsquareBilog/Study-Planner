<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreTopicRequest;
use App\Http\Requests\Student\UpdateTopicRequest;
use App\Http\Resources\TopicResource;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    /**
     * List all topics for a given subject (verifies subject ownership).
     * Supports: ?search=keyword
     */
    public function index(Request $request, Subject $subject): JsonResponse
    {
        $this->authorize('view', $subject);

        $query = $subject->topics()
            ->withCount(['quizzes', 'tasks'])
            ->with(['resources:id,title,resource_type,difficulty'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 20);
        $topics = $query->paginate($perPage);

        return response()->json([
            'data' => TopicResource::collection($topics),
            'message' => 'Topics retrieved successfully.',
        ]);
    }

    /**
     * Show a single topic with full relationships.
     */
    public function show(Request $request, Subject $subject, Topic $topic): JsonResponse
    {
        $this->authorize('view', $subject);
        $this->ensureTopicBelongsToSubject($topic, $subject);

        $topic->load(['quizzes.questions', 'resources', 'tasks' => fn ($q) => $q->orderBy('deadline', 'asc')->limit(10)]);
        $topic->loadCount(['quizzes', 'tasks']);

        return response()->json([
            'data' => new TopicResource($topic),
            'message' => 'Topic retrieved successfully.',
        ]);
    }

    /**
     * Create a new topic under the given subject.
     */
    public function store(StoreTopicRequest $request, Subject $subject): JsonResponse
    {
        $this->authorize('update', $subject);

        $topic = $subject->topics()->create($request->validated());

        return response()->json([
            'data' => new TopicResource($topic),
            'message' => 'Topic created successfully.',
        ], 201);
    }

    /**
     * Update an existing topic.
     */
    public function update(UpdateTopicRequest $request, Subject $subject, Topic $topic): JsonResponse
    {
        $this->authorize('update', $subject);
        $this->ensureTopicBelongsToSubject($topic, $subject);

        $topic->update($request->validated());

        return response()->json([
            'data' => new TopicResource($topic->fresh()),
            'message' => 'Topic updated successfully.',
        ]);
    }

    /**
     * Soft-delete a topic.
     */
    public function destroy(Request $request, Subject $subject, Topic $topic): JsonResponse
    {
        $this->authorize('delete', $subject);
        $this->ensureTopicBelongsToSubject($topic, $subject);

        $topicName = $topic->name;
        $topicId = $topic->id;

        $topic->delete();

        return response()->json([
            'data' => [
                'id' => $topicId,
                'name' => $topicName,
            ],
            'message' => "Topic '{$topicName}' deleted successfully.",
        ]);
    }

    /**
     * Restore a soft-deleted topic.
     */
    public function restore(Request $request, Subject $subject, $topicId): JsonResponse
    {
        $this->authorize('view', $subject);

        $topic = $subject->topics()->withTrashed()->whereKey($topicId)->firstOrFail();
        $this->ensureTopicBelongsToSubject($topic, $subject);

        $topic->restore();

        return response()->json([
            'data' => new TopicResource($topic->fresh()),
            'message' => "Topic '{$topic->name}' restored successfully.",
        ]);
    }

    /**
     * Guard: ensure the topic actually belongs to the specified subject.
     * Prevents URL manipulation like /subjects/1/topics/999 where 999 belongs to a different subject.
     */
    private function ensureTopicBelongsToSubject(Topic $topic, Subject $subject): void
    {
        abort_unless($topic->subject_id === $subject->id, 404, 'Topic does not belong to this subject.');
    }
}
