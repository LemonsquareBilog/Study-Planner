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
        $result = $this->service->forStudent($request->user());

        $message = 'Recommendations retrieved successfully.';
        if (!empty($result['reason'])) {
            $reasonMessages = [
                'no_quiz_data' => 'Take your first quiz to get personalized resource recommendations!',
                'all_topics_strong' => 'Great job! All your topics are strong. No recommendations needed at this time.',
            ];
            $message = $reasonMessages[$result['reason']] ?? $message;
        }

        return response()->json([
            'data' => $result['data'],
            'reason' => $result['reason'],
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
