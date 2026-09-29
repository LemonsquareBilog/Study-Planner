<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ProgressService;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __construct(
        private ProgressService $progress,
        private RecommendationService $recommendations,
    ) {}

    /**
     * Full progress report for the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'summary' => $this->progress->summary($user),
                'topic_performance' => $this->recommendations->topicPerformance($user),
            ],
            'message' => 'Progress report generated successfully.',
        ]);
    }
}
