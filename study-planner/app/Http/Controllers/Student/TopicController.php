<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreTopicRequest;
use App\Http\Requests\Student\UpdateTopicRequest;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    /**
     * List all topics for a given subject (verifies subject ownership).
     */
    public function index(Request $request, Subject $subject): JsonResponse
    {
        $this->authorize('view', $subject);

        $topics = $subject->topics()
            ->withCount(['quizzes', 'tasks'])
            ->with(['resources:id,title,resource_type,difficulty'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $topics, 'message' => 'Topics retrieved successfully.']);
    }

    /**
     * Show a single topic with full relationships.
     */
    public function show(Request $request, Subject $subject, Topic $topic): JsonResponse
    {
        $this->authorize('view', $subject);
        $this->ensureTopicBelongsToSubject($topic, $subject);

        $topic->load(['quizzes.questions', 'resources', 'tasks' => fn ($q) => $q->latest('deadline')->limit(10)]);
        $topic->loadCount(['quizzes', 'tasks']);

        return response()->json(['data' => $topic, 'message' => 'Topic retrieved successfully.']);
    }

    /**
     * Create a new topic under the given subject.
     */
    public function store(StoreTopicRequest $request, Subject $subject): JsonResponse
    {
        $this->authorize('update', $subject);

        $topic = $subject->topics()->create($request->validated());

        return response()->json([
            'data' => $topic,
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
            'data' => $topic->fresh(),
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

        $deletedInfo = [
            'id' => $topic->id,
            'name' => $topic->name,
        ];

        $topic->delete();

        return response()->json([
            'data' => $deletedInfo,
            'message' => "Topic '{$deletedInfo['name']}' deleted successfully.",
        ], 200);
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
