<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(): JsonResponse
    {
        $subjects = Subject::query()
            ->with(['user:id,name,email', 'topics:id,subject_id,name,description,difficulty,is_active'])
            ->withCount('topics')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $subjects,
            'message' => 'Subjects retrieved successfully.',
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'data' => User::query()
                ->where('role', 'student')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'message' => 'Student options retrieved successfully.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects')->where(fn ($query) => $query->where('user_id', $request->integer('user_id'))->whereNull('deleted_at'))],
            'description' => ['nullable', 'string'],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'color' => ['required', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        abort_unless(User::whereKey($data['user_id'])->where('role', 'student')->exists(), 422, 'Subjects can only be assigned to students.');

        $subject = Subject::create($data);

        return response()->json([
            'data' => $subject->load('user:id,name,email'),
            'message' => 'Subject created successfully.',
        ], 201);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects')->where(fn ($query) => $query->where('user_id', $subject->user_id)->whereNull('deleted_at'))->ignore($subject->id)],
            'description' => ['nullable', 'string'],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'color' => ['required', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        $subject->update($data);

        return response()->json([
            'data' => $subject->fresh(['user:id,name,email', 'topics']),
            'message' => 'Subject updated successfully.',
        ]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
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

    public function storeTopic(Request $request, Subject $subject): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('topics')->where(fn ($query) => $query->where('subject_id', $subject->id)->whereNull('deleted_at'))],
            'description' => ['nullable', 'string'],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'is_active' => ['boolean'],
        ]);

        $topic = $subject->topics()->create($data);

        return response()->json([
            'data' => $topic,
            'message' => 'Topic created successfully.',
        ], 201);
    }

    public function updateTopic(Request $request, Subject $subject, Topic $topic): JsonResponse
    {
        abort_unless($topic->subject_id === $subject->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('topics')->where(fn ($query) => $query->where('subject_id', $subject->id)->whereNull('deleted_at'))->ignore($topic->id)],
            'description' => ['nullable', 'string'],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'is_active' => ['boolean'],
        ]);

        $topic->update($data);

        return response()->json([
            'data' => $topic->fresh(),
            'message' => 'Topic updated successfully.',
        ]);
    }

    public function destroyTopic(Subject $subject, Topic $topic): JsonResponse
    {
        abort_unless($topic->subject_id === $subject->id, 404);

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
}