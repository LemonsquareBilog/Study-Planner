<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\GenerateStudyPlanRequest;
use App\Http\Resources\StudySessionResource;
use App\Models\StudySession;
use App\Services\StudyPlanService;
use App\Events\StudySessionStarted;
use App\Events\StudySessionCompleted;
use App\Events\StudySessionSkipped;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudyPlanController extends Controller
{
    public function __construct(private StudyPlanService $service) {}

    /**
     * List all study sessions for the authenticated student.
     * Supports: ?from=2026-01-01&to=2026-01-31, ?status=scheduled, ?page=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->studySessions()
            ->with(['subject:id,name,color', 'topic:id,name', 'task:id,title']);

        if ($request->filled('from')) {
            $query->where('scheduled_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('scheduled_date', '<=', $request->input('to'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = $request->integer('per_page', 20);
        $sessions = $query->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->paginate($perPage);

        return response()->json([
            'data' => StudySessionResource::collection($sessions),
            'message' => 'Study sessions retrieved successfully.',
        ]);
    }

    /**
     * Auto-generate study sessions for a given date range.
     */
    public function generate(GenerateStudyPlanRequest $request): JsonResponse
    {
        $result = $this->service->generate(
            $request->user(),
            Carbon::parse($request->from)->startOfDay(),
            Carbon::parse($request->to)->endOfDay(),
        );

        $message = "{$result['created_sessions']} study session(s) generated successfully.";
        
        if ($result['created_sessions'] === 0 && $result['reason']) {
            $reasonMessages = [
                'no_tasks_in_range' => 'No tasks have deadlines in the selected date range. Try a wider date range or create tasks with deadlines in this period.',
                'all_tasks_fully_scheduled' => 'All tasks in the selected range are already fully scheduled.',
                'no_available_time_slots' => 'No available time slots found in your study preferences for the selected date range. Try adjusting your preferred study hours or daily limits.',
            ];
            $message = $reasonMessages[$result['reason']] ?? 'No study sessions were generated.';
        }

        // Broadcast study plan generated event
        if ($result['created_sessions'] > 0) {
            broadcast(new \App\Events\StudyPlanGenerated($request->user(), $result['sessions']))->toOthers();
        }

        return response()->json([
            'data' => [
                'created_sessions' => $result['created_sessions'],
                'sessions' => StudySessionResource::collection($result['sessions']),
                'reason' => $result['reason'],
            ],
            'message' => $message,
        ]);
    }

    /**
     * Update a study session's status.
     * Allowed transitions: scheduled -> in_progress, in_progress -> completed/skipped, scheduled -> skipped
     */
    public function updateStatus(Request $request, StudySession $session): JsonResponse
    {
        $this->authorize('update', $session);

        $validTransitions = [
            'scheduled' => ['in_progress', 'skipped'],
            'in_progress' => ['completed', 'skipped'],
            'completed' => [],
            'skipped' => ['scheduled'],
        ];

        $request->validate([
            'status' => ['required', 'in:scheduled,in_progress,completed,skipped'],
        ]);

        $newStatus = $request->input('status');
        $currentStatus = $session->status;

        if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'message' => "Cannot transition study session from '{$currentStatus}' to '{$newStatus}'.",
            ], 422);
        }

        $updateData = ['status' => $newStatus];
        
        if ($newStatus === 'in_progress') {
            $updateData['started_at'] = now();
        } elseif ($newStatus === 'completed') {
            $updateData['completed_at'] = now();
            if (empty($session->started_at)) {
                $updateData['started_at'] = now();
            }
        } elseif ($newStatus === 'skipped') {
            $updateData['completed_at'] = now();
        } elseif ($newStatus === 'scheduled' && $currentStatus === 'skipped') {
            $updateData['started_at'] = null;
            $updateData['completed_at'] = null;
        }

        $session->update($updateData);

        // Broadcast appropriate event based on new status
        $this->broadcastStatusChange($session, $newStatus);

        return response()->json([
            'data' => new StudySessionResource($session->fresh(['subject:id,name,color', 'topic:id,name', 'task:id,title'])),
            'message' => "Study session marked as {$newStatus}.",
        ]);
    }

    /**
     * Broadcast status change events.
     */
    protected function broadcastStatusChange(StudySession $session, string $newStatus): void
    {
        $session->load('subject:id,name,color', 'topic:id,name', 'task:id,title');

        match ($newStatus) {
            'in_progress' => broadcast(new StudySessionStarted($session))->toOthers(),
            'completed' => broadcast(new StudySessionCompleted($session))->toOthers(),
            'skipped' => broadcast(new StudySessionSkipped($session))->toOthers(),
            default => null,
        };
    }
}
