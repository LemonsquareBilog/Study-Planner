<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreSubjectRequest;
use App\Http\Requests\Student\UpdateSubjectRequest;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * List all subjects belonging to the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->subjects()
            ->withCount(['topics', 'tasks'])
            ->with(['topics:id,subject_id,name,difficulty,is_active']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $subjects = $query->orderBy('name')->get();

        return response()->json(['data' => $subjects, 'message' => 'Subjects retrieved successfully.']);
    }

    /**
     * Show a single subject with its topics and resource mappings.
     */
    public function show(Request $request, Subject $subject): JsonResponse
    {
        $this->authorize('view', $subject);

        $subject->load(['topics.quizzes', 'tasks' => fn ($q) => $q->latest('deadline')->limit(10), 'resources']);
        $subject->loadCount(['topics', 'tasks']);

        return response()->json(['data' => $subject, 'message' => 'Subject retrieved successfully.']);
    }

    /**
     * Create a new subject for the authenticated student.
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = $request->user()->subjects()->create($request->validated());

        return response()->json([
            'data' => $subject,
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
            'data' => $subject->fresh(['topics']),
            'message' => 'Subject updated successfully.',
        ]);
    }

    /**
     * Soft-delete a subject.
     */
    public function destroy(Request $request, Subject $subject): JsonResponse
    {
        $this->authorize('delete', $subject);

        $deletedInfo = [
            'id' => $subject->id,
            'name' => $subject->name,
        ];

        $subject->delete();

        return response()->json([
            'data' => $deletedInfo,
            'message' => "Subject '{$deletedInfo['name']}' deleted successfully.",
        ], 200);
    }
}
