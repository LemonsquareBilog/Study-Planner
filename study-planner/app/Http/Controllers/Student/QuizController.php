<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitQuizRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    public function __construct(private QuizService $service) {}

    /**
     * Show quiz with its questions (correct_answer hidden by $hidden on Question model).
     */
    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        $this->ensureStudentOwnsQuiz($request, $quiz);

        $quiz->setAttribute('submission_id', Crypt::encryptString(json_encode([
            'id' => (string) Str::uuid(), 'user_id' => $request->user()->id, 'quiz_id' => $quiz->id,
        ], JSON_THROW_ON_ERROR)));

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
            $request->input('submission_id'),
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
        ], $attempt->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * List available quizzes for a student (through their subjects' topics).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate(['page' => ['sometimes', 'integer', 'min:1'], 'search' => ['sometimes', 'nullable', 'string', 'max:200'], 'topic_id' => ['sometimes', 'integer', 'min:1']]);

        $query = Quiz::whereHas('topic.subject', fn ($query) => $query->where('user_id', $user->id))
            ->where('is_active', true)
            ->with(['topic:id,name,subject_id,difficulty', 'topic.subject:id,name'])
            ->withCount('questions')
            ->withCount(['attempts' => fn ($query) => $query->where('user_id', $user->id)])
            ->withMax(['attempts as best_score' => fn ($query) => $query->where('user_id', $user->id)], 'percentage')
            ->addSelect(['latest_attempt_id' => QuizAttempt::select('id')
                ->whereColumn('quiz_id', 'quizzes.id')->where('user_id', $user->id)
                ->orderByDesc('completed_at')->orderByDesc('id')->limit(1)]);

        if ($request->filled('topic_id')) {
            $query->where('topic_id', $request->integer('topic_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $quizzes = $query->orderBy('title')->orderBy('id')->paginate(20);
        $latest = $user->quizAttempts()->whereIn('id', $quizzes->getCollection()->pluck('latest_attempt_id')->filter())->get()->keyBy('id');
        $quizzes->getCollection()->each(function ($quiz) use ($latest) {
            $quiz->setAttribute('latest_attempt', $latest->get($quiz->latest_attempt_id));
            $quiz->offsetUnset('latest_attempt_id');
        });

        return response()->json([
            ...$quizzes->toArray(),
            'message' => 'Available quizzes retrieved successfully.',
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);
        $attempts = $request->user()->quizAttempts()
            ->with(['quiz.topic.subject'])
            ->latest('completed_at')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            ...$attempts->toArray(),
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
