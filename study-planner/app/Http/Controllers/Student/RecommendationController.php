<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\RecommendationView;
use App\Models\Resource;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(private RecommendationService $service) {}

    /**
     * Get personalized resource recommendations for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $recommendations = $this->service->forStudent($user);

        $reason = 'has_recommendations';
        $message = 'Recommendations retrieved successfully.';

        if ($recommendations->isEmpty()) {
            $hasAttempts = $user->quizAttempts()->exists();
            if (!$hasAttempts) {
                $reason = 'no_quiz_attempts';
                $message = 'Take your first quiz to identify areas for improvement and unlock personalized study resources.';
            } else {
                $hasAreasForReview = $this->service->topicPerformance($user)
                    ->contains(fn ($topic) => $topic->classification !== 'strong');
                $reason = $hasAreasForReview ? 'no_resources_for_weak_areas' : 'all_topics_strong';
                $message = $hasAreasForReview
                    ? 'Your quiz results show areas to strengthen, but no matching active resources are available yet.'
                    : 'Great work! Your quiz scores across all topics are currently strong. Keep reviewing your coursework.';
            }
        }

        return response()->json([
            'data' => $recommendations,
            'meta' => [
                'reason' => $reason,
            ],
            'message' => $message,
        ]);
    }

    /**
     * Record that a student viewed/clicked a recommended resource.
     */
    public function recordView(Request $request, Resource $resource): JsonResponse
    {
        $request->validate([
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
        ]);

        $view = $request->user()->recommendationViews()->create([
            'resource_id' => $resource->id,
            'topic_id' => $request->input('topic_id'),
            'viewed_at' => now(),
        ]);

        return response()->json([
            'data' => $view,
            'message' => 'Recommendation view recorded.',
        ], 201);
    }
}
