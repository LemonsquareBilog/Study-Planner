<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Get personalized resource recommendations for a student based on quiz performance.
     *
     * Optimized: uses database-level AVG/GROUP BY instead of loading all attempts into PHP memory.
     * This reduces memory from O(n) to O(1) where n = total quiz attempts.
     */
    public function forStudent(User $user): Collection
    {
        $weakThreshold = (int) (SystemSetting::where('key', 'weak_topic_threshold')->value('value') ?? 60);
        $reviewThreshold = (int) (SystemSetting::where('key', 'review_topic_threshold')->value('value') ?? 79);

        // Database-level aggregation: O(1) memory instead of loading all attempts
        $topicPerformance = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->where('quiz_attempts.user_id', $user->id)
            ->select('quizzes.topic_id', DB::raw('AVG(quiz_attempts.percentage) as avg_percentage'))
            ->groupBy('quizzes.topic_id')
            ->get();

        $weakTopics = $topicPerformance
            ->filter(fn ($row) => $row->avg_percentage < $weakThreshold)
            ->pluck('topic_id');

        $reviewTopics = $topicPerformance
            ->filter(fn ($row) => $row->avg_percentage >= $weakThreshold && $row->avg_percentage <= $reviewThreshold)
            ->pluck('topic_id');

        $targetTopics = $weakTopics->merge($reviewTopics);
        $subjectIds = $user->subjects()->pluck('id');

        if ($targetTopics->isEmpty()) {
            $targetTopics = $user->subjects()
                ->with('topics:id,subject_id')
                ->get()
                ->pluck('topics')
                ->flatten()
                ->pluck('id');
        }

        if ($targetTopics->isEmpty()) {
            return collect();
        }

        $resources = Resource::query()
            ->where('is_active', true)
            ->where(function ($query) use ($targetTopics, $subjectIds) {
                $query->whereHas('topics', fn ($q) => $q->whereIn('topics.id', $targetTopics))
                    ->orWhereHas('subjects', fn ($q) => $q->whereIn('subjects.id', $subjectIds));
            })
            ->with(['topics:id,name', 'subjects:id,name'])
            ->orderBy('title')
            ->get();

        // Annotate each resource with the classification of topics it addresses
        return $resources->map(function ($resource) use ($weakTopics, $reviewTopics) {
            $resourceTopicIds = $resource->topics->pluck('id');

            $addressesWeak = $resourceTopicIds->intersect($weakTopics)->isNotEmpty();
            $addressesReview = $resourceTopicIds->intersect($reviewTopics)->isNotEmpty();

            $resource->recommendation_reason = $addressesWeak ? 'weak' : ($addressesReview ? 'needs_review' : 'related');

            return $resource;
        });
    }

    /**
     * Get a full topic performance breakdown for a student.
     */
    public function topicPerformance(User $user): Collection
    {
        $weakThreshold = (int) (SystemSetting::where('key', 'weak_topic_threshold')->value('value') ?? 60);
        $reviewThreshold = (int) (SystemSetting::where('key', 'review_topic_threshold')->value('value') ?? 79);

        return DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->join('topics', 'quizzes.topic_id', '=', 'topics.id')
            ->join('subjects', 'topics.subject_id', '=', 'subjects.id')
            ->where('quiz_attempts.user_id', $user->id)
            ->select(
                'topics.id as topic_id',
                'topics.name as topic_name',
                'subjects.name as subject_name',
                DB::raw('AVG(quiz_attempts.percentage) as avg_percentage'),
                DB::raw('COUNT(quiz_attempts.id) as attempt_count'),
                DB::raw('MAX(quiz_attempts.percentage) as best_score'),
                DB::raw('MIN(quiz_attempts.percentage) as worst_score'),
            )
            ->groupBy('topics.id', 'topics.name', 'subjects.name')
            ->orderBy('avg_percentage')
            ->get()
            ->map(function ($row) use ($weakThreshold, $reviewThreshold) {
                $row->classification = match (true) {
                    $row->avg_percentage < $weakThreshold => 'weak',
                    $row->avg_percentage <= $reviewThreshold => 'needs_review',
                    default => 'strong',
                };
                return $row;
            });
    }
}
