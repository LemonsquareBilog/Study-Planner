<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreTaskRequest;
use App\Http\Requests\Student\UpdateTaskRequest;
use App\Models\Subject;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    /**
     * List the authenticated student's tasks with pagination.
     * Supports filters: ?status=pending, ?priority=high, ?subject_id=1, ?search=assignment, ?sort=deadline_asc
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->tasks()->with(['subject:id,name,color', 'topic:id,name']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        if ($request->filled('from')) {
            $from = $request->input('from');
            $query->where(function ($q) use ($from) {
                $q->where('start_date', '>=', $from)
                  ->orWhere('deadline', '>=', $from);
            });
        }

        if ($request->filled('to')) {
            $to = $request->input('to');
            $query->where(function ($q) use ($to) {
                $q->where('start_date', '<=', $to)
                  ->orWhere('deadline', '<=', $to);
            });
        }

        // Default sort: soonest deadline first (UX-014)
        $sort = $request->input('sort', 'deadline_asc');
        match ($sort) {
            'deadline_desc' => $query->orderBy('deadline', 'desc'),
            'priority' => $query->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")->orderBy('deadline', 'asc'),
            'created' => $query->latest('created_at'),
            default => $query->orderBy('deadline', 'asc'),
        };

        $tasks = $query->paginate(20);

        return response()->json($tasks);
    }

    /**
     * Create a new task for the authenticated student.
     * Validates that subject and optional topic belong to the student.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $subject = Subject::where('id', $data['subject_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$subject) {
            throw ValidationException::withMessages([
                'subject_id' => 'The selected subject does not belong to the current student.',
            ]);
        }

        if (!empty($data['topic_id']) && !$subject->topics()->whereKey($data['topic_id'])->exists()) {
            throw ValidationException::withMessages([
                'topic_id' => 'The selected topic does not belong to the selected subject.',
            ]);
        }

        $task = $user->tasks()->create($data);

        return response()->json([
            'data' => $task->load(['subject:id,name,color', 'topic:id,name']),
            'message' => 'Task created successfully.',
        ], 201);
    }

    /**
     * Update an existing task.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validated();

        // If topic_id is being changed, verify it belongs to the task's subject
        if (!empty($data['topic_id'])) {
            $subject = $task->subject;
            if (!$subject->topics()->whereKey($data['topic_id'])->exists()) {
                throw ValidationException::withMessages([
                    'topic_id' => 'The selected topic does not belong to the task\'s subject.',
                ]);
            }
        }

        $task->update($data);

        return response()->json([
            'data' => $task->fresh(['subject:id,name,color', 'topic:id,name']),
            'message' => 'Task updated successfully.',
        ]);
    }

    /**
     * Update task status (UX-025: status lifecycle transitions).
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
        ]);

        $status = $validated['status'];
        $task->update([
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        return response()->json([
            'data' => $task->fresh(['subject:id,name,color', 'topic:id,name']),
            'message' => "Task status updated to " . ucfirst(str_replace('_', ' ', $status)) . ".",
        ]);
    }

    /**
     * Delete a task (soft-delete) with confirmation payload (UX-007).
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $deletedInfo = [
            'id' => $task->id,
            'title' => $task->title,
        ];

        $task->delete();

        return response()->json([
            'data' => $deletedInfo,
            'message' => "Task '{$deletedInfo['title']}' deleted successfully.",
        ], 200);
    }

    /**
     * Mark a task as completed.
     */
    public function complete(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'data' => $task->fresh(['subject:id,name,color', 'topic:id,name']),
            'message' => 'Task marked as completed.',
        ]);
    }
}
