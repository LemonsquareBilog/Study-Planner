<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\GenerateStudyPlanRequest;
use App\Models\StudySession;
use App\Services\StudyPlanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudyPlanController extends Controller
{
    public function __construct(private StudyPlanService $service) {}

    /**
     * List all study sessions for the authenticated student.
     * Supports: ?from=2026-01-01&to=2026-01-31, ?status=scheduled
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->studySessions()
            ->with(['subject:id,name,color', 'topic:id,name', 'task:id,title,description,task_type,priority,start_date,deadline,estimated_minutes,status,created_at']);

        if ($request->filled('from')) {
            $query->where('scheduled_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('scheduled_date', '<=', $request->input('to'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sessions = $query->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data' => $sessions,
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

        return response()->json([
            'data' => [
                'created_sessions' => $result['created_sessions_count'],
                'created_sessions_list' => $result['created_sessions'],
                'reason' => $result['reason'],
            ],
            'message' => $result['message'],
        ]);
    }

    public function updateStatus(Request $request, StudySession $studySession): JsonResponse
    {
        abort_unless($studySession->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:scheduled,in_progress,completed,missed'],
        ]);

        $status = $validated['status'];
        $studySession->forceFill([
            'status' => $status,
            'started_at' => $status === 'in_progress'
                ? ($studySession->started_at ?? now())
                : $studySession->started_at,
            'completed_at' => $status === 'completed' ? ($studySession->completed_at ?? now()) : null,
        ])->save();

        return response()->json([
            'data' => $studySession->fresh()->load(['subject:id,name,color', 'topic:id,name', 'task:id,title,description,task_type,priority,start_date,deadline,estimated_minutes,status,created_at']),
            'message' => 'Study session status updated successfully.',
        ]);
    }
}
