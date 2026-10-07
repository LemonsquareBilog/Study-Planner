<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitQuizRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(private QuizService $service) {}

    /**
     * Show quiz with its questions (correct_answer hidden by $hidden on Question model).
     */
    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        $this->ensureStudentOwnsQuiz($request, $quiz);

        return response()->json([
            'data' => $quiz->load('topic.subject', 'questions'),
            'message' => 'Quiz retrieved successfully.',
        ]);
    }

    /**
     * Submit quiz answers for server-side scoring.
     */
    public function submit(SubmitQuizRequest $request, Quiz $quiz): JsonResponse
    {
        $this->ensureStudentOwnsQuiz($request, $quiz);

        $attempt = $this->service->submit(
            $request->user(),
            $quiz,
            $request->input('answers'),
            $request->input('started_at'),
        );

        // Include question options and revealed correct answers post-grading (UX-011)
        $attempt->load([
            'quiz.topic.subject',
            'answers.question',
        ]);
        $this->revealCorrectAnswers($attempt);

        return response()->json([
            'data' => $attempt,
            'message' => 'Quiz submitted and graded successfully.',
        ], 201);
    }

    /**
     * List available quizzes for a student (through their subjects' topics).
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

        $quizzes = $query->orderBy('title')->get();

        // Attach student's previous attempt history (UX-022)
        $attemptsByQuiz = $user->quizAttempts()
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->latest('completed_at')
            ->get()
            ->groupBy('quiz_id');

        $quizzes->each(function ($quiz) use ($attemptsByQuiz) {
            $attempts = $attemptsByQuiz->get($quiz->id);
            $quiz->latest_attempt = $attempts?->first();
            $quiz->attempts_count = $attempts?->count() ?? 0;
            $quiz->best_score = $attempts ? $attempts->max('percentage') : null;
        });

        return response()->json([
            'data' => $quizzes,
            'message' => 'Available quizzes retrieved successfully.',
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $attempts = $request->user()->quizAttempts()
            ->with(['quiz.topic.subject', 'answers.question'])
            ->latest('completed_at')
            ->get();

        $attempts->each(fn (QuizAttempt $attempt) => $this->revealCorrectAnswers($attempt));

        return response()->json([
            'data' => $attempts,
            'message' => 'Quiz history retrieved successfully.',
        ]);
    }

    public function attempt(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $ownedAttempt = $request->user()->quizAttempts()
            ->whereKey($attempt->id)
            ->with(['quiz.topic.subject', 'answers.question'])
            ->firstOrFail();

        $this->revealCorrectAnswers($ownedAttempt);

        return response()->json([
            'data' => $ownedAttempt,
            'message' => 'Quiz attempt retrieved successfully.',
        ]);
    }

    private function revealCorrectAnswers(QuizAttempt $attempt): void
    {
        $attempt->answers->each(fn ($answer) => $answer->question?->makeVisible('correct_answer'));
    }

    private function ensureStudentOwnsQuiz(Request $request, Quiz $quiz): void
    {
        abort_unless(
            $quiz->is_active && $request->user()->subjects()->whereKey($quiz->topic()->value('subject_id'))->exists(),
            404,
        );
    }
}
