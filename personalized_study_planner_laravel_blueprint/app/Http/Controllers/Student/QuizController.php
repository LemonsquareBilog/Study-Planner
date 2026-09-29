<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitQuizRequest;
use App\Http\Resources\QuizAttemptResource;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function __construct(private QuizService $service) {}

    /**
     * Show quiz with its questions (correct_answer hidden by $hidden on Question model).
     */
    public function show(Quiz $quiz): JsonResponse
    {
        return response()->json([
            'data' => $quiz->load('topic', 'questions'),
            'message' => 'Quiz retrieved successfully.',
        ]);
    }

    /**
     * Start a new quiz attempt (creates draft).
     */
    public function start(Quiz $quiz): JsonResponse
    {
        $attempt = $this->service->start(request()->user(), $quiz);

        return response()->json([
            'data' => new QuizAttemptResource($attempt),
            'message' => 'Quiz attempt started. Your progress will be saved automatically.',
        ], 201);
    }

    /**
     * Save draft answers for an in-progress quiz.
     */
    public function saveDraft(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->authorize('update', $attempt);

        $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'in:A,B,C,D'],
        ]);

        $attempt = $this->service->saveDraft($attempt, $request->input('answers'));

        return response()->json([
            'data' => new QuizAttemptResource($attempt),
            'message' => 'Progress saved.',
        ]);
    }

    /**
     * Resume an existing draft attempt.
     */
    public function resume(QuizAttempt $attempt): JsonResponse
    {
        $this->authorize('view', $attempt);

        $attempt = $this->service->resume($attempt);

        return response()->json([
            'data' => new QuizAttemptResource($attempt),
            'message' => 'Quiz attempt resumed.',
        ]);
    }

    /**
     * Submit quiz answers for server-side scoring.
     */
    public function submit(SubmitQuizRequest $request, Quiz $quiz): JsonResponse
    {
        $attempt = $this->service->submit(
            $request->user(),
            $quiz,
            $request->input('answers'),
            $request->string('started_at'),
        );

        return response()->json([
            'data' => new QuizAttemptResource($attempt),
            'message' => 'Quiz submitted and graded successfully.',
        ], 201);
    }

    /**
     * List available quizzes for a student (through their subjects' topics).
     * Supports: ?search=keyword
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $topicIds = $user->subjects()
            ->with('topics')
            ->get()
            ->pluck('topics')
            ->flatten()
            ->pluck('id');

        $query = Quiz::whereIn('topic_id', $topicIds)
            ->where('is_active', true)
            ->with(['topic:id,name,subject_id', 'topic.subject:id,name'])
            ->withCount('questions');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 20);
        $quizzes = $query->orderBy('title')->paginate($perPage);

        // Include previous attempt info for each quiz
        $quizzes->getCollection()->transform(function ($quiz) use ($user) {
            $lastAttempt = $user->quizAttempts()
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->latest('completed_at')
                ->first();

            $attemptCount = $user->quizAttempts()
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->count();

            $quiz->last_attempt = $lastAttempt ? [
                'id' => $lastAttempt->id,
                'score' => $lastAttempt->score,
                'percentage' => $lastAttempt->percentage,
                'completed_at' => $lastAttempt->completed_at?->toIso8601String(),
            ] : null;

            $quiz->attempt_count = $attemptCount;

            return $quiz;
        });

        return response()->json([
            'data' => $quizzes,
            'message' => 'Available quizzes retrieved successfully.',
        ]);
    }
}
