<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreTaskRequest;
use App\Http\Requests\Student\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Subject;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    /**
     * List the authenticated student's tasks with pagination.
     * Supports filters: ?status=pending, ?priority=high, ?subject_id=1, ?search=keyword
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->tasks()->with(['subject:id,name,color', 'topic:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 20);
        $tasks = $query->orderBy('deadline', 'asc')->paginate($perPage);

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'message' => 'Tasks retrieved successfully.',
        ]);
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
            'data' => new TaskResource($task->load(['subject:id,name,color', 'topic:id,name'])),
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
            'data' => new TaskResource($task->fresh(['subject:id,name,color', 'topic:id,name'])),
            'message' => 'Task updated successfully.',
        ]);
    }

    /**
     * Delete a task (soft-delete).
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $taskTitle = $task->title;
        $taskId = $task->id;

        $task->delete();

        return response()->json([
            'data' => [
                'id' => $taskId,
                'title' => $taskTitle,
            ],
            'message' => "Task '{$taskTitle}' deleted successfully.",
        ]);
    }

    /**
     * Restore a soft-deleted task.
     */
    public function restore(Request $request, $taskId): JsonResponse
    {
        $task = $request->user()->tasks()->withTrashed()->whereKey($taskId)->firstOrFail();
        
        $this->authorize('restore', $task);

        $task->restore();

        return response()->json([
            'data' => new TaskResource($task->load(['subject:id,name,color', 'topic:id,name'])),
            'message' => "Task '{$task->title}' restored successfully.",
        ]);
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
            'data' => new TaskResource($task->fresh(['subject:id,name,color', 'topic:id,name'])),
            'message' => 'Task marked as completed.',
        ]);
    }

    /**
     * Update task status with explicit transitions.
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validTransitions = [
            'pending' => ['in_progress', 'completed', 'cancelled'],
            'in_progress' => ['completed', 'pending', 'cancelled'],
            'completed' => ['pending'], // reopen
            'cancelled' => ['pending'],
        ];

        $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
        ]);

        $newStatus = $request->input('status');
        $currentStatus = $task->status;

        if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'message' => "Cannot transition task from '{$currentStatus}' to '{$newStatus}'.",
            ], 422);
        }

        $updateData = ['status' => $newStatus];
        if ($newStatus === 'completed') {
            $updateData['completed_at'] = now();
        } elseif ($currentStatus === 'completed' && $newStatus === 'pending') {
            $updateData['completed_at'] = null;
        }

        $task->update($updateData);

        return response()->json([
            'data' => new TaskResource($task->fresh(['subject:id,name,color', 'topic:id,name'])),
            'message' => "Task status updated to '{$newStatus}'.",
        ]);
    }
}
