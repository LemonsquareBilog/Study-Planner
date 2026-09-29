<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreSubjectRequest;
use App\Http\Requests\Student\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * List all subjects belonging to the authenticated student.
     * Supports: ?search=keyword
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->subjects()
            ->withCount(['topics', 'tasks'])
            ->with(['topics:id,subject_id,name,difficulty,is_active'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 20);
        $subjects = $query->paginate($perPage);

        return response()->json([
            'data' => SubjectResource::collection($subjects),
            'message' => 'Subjects retrieved successfully.',
        ]);
    }

    /**
     * Show a single subject with its topics and resource mappings.
     */
    public function show(Request $request, Subject $subject): JsonResponse
    {
        $this->authorize('view', $subject);

        $subject->load(['topics.quizzes', 'tasks' => fn ($q) => $q->orderBy('deadline', 'asc')->limit(10), 'resources']);
        $subject->loadCount(['topics', 'tasks']);

        return response()->json([
            'data' => new SubjectResource($subject),
            'message' => 'Subject retrieved successfully.',
        ]);
    }

    /**
     * Create a new subject for the authenticated student.
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = $request->user()->subjects()->create($request->validated());

        return response()->json([
            'data' => new SubjectResource($subject),
            'message' => 'Subject created successfully.',
        ], 201);
    }

    /**
     * Update an existing subject.
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $this->authorize('update', $subject);

        $subject->update($request->validated());

        return response()->json([
            'data' => new SubjectResource($subject->fresh(['topics'])),
            'message' => 'Subject updated successfully.',
        ]);
    }

    /**
     * Soft-delete a subject.
     */
    public function destroy(Request $request, Subject $subject): JsonResponse
    {
        $this->authorize('delete', $subject);

        $subjectName = $subject->name;
        $subjectId = $subject->id;

        $subject->delete();

        return response()->json([
            'data' => [
                'id' => $subjectId,
                'name' => $subjectName,
            ],
            'message' => "Subject '{$subjectName}' deleted successfully.",
        ]);
    }

    /**
     * Restore a soft-deleted subject.
     */
    public function restore(Request $request, $subjectId): JsonResponse
    {
        $subject = $request->user()->subjects()->withTrashed()->whereKey($subjectId)->firstOrFail();
        
        $this->authorize('restore', $subject);

        $subject->restore();

        return response()->json([
            'data' => new SubjectResource($subject->fresh(['topics'])),
            'message' => "Subject '{$subject->name}' restored successfully.",
        ]);
    }
}
