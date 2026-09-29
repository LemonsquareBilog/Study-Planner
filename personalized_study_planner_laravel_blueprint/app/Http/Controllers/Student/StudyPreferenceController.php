<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpsertStudyPreferenceRequest;
use App\Models\StudyPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudyPreferenceController extends Controller
{
    /**
     * Get the authenticated student's study preferences.
     */
    public function show(Request $request): JsonResponse
    {
        $preferences = $request->user()->studyPreference;

        if (!$preferences) {
            return response()->json([
                'data' => null,
                'message' => 'No study preferences configured yet.',
            ]);
        }

        return response()->json([
            'data' => $preferences,
            'message' => 'Study preferences retrieved successfully.',
        ]);
    }

    /**
     * Create or update the student's study preferences (upsert).
     * A student can only have one set of preferences (enforced by unique FK).
     */
    public function upsert(UpsertStudyPreferenceRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $preferences = $user->studyPreference()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        $isNew = $preferences->wasRecentlyCreated;

        return response()->json([
            'data' => $preferences,
            'message' => $isNew ? 'Study preferences created successfully.' : 'Study preferences updated successfully.',
        ], $isNew ? 201 : 200);
    }

    /**
     * Get the student's onboarding status.
     */
    public function onboardingStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('studyPreference');

        $hasPreferences = (bool) $user->studyPreference;
        $subjectsCount = $user->subjects()->count();
        $tasksCount = $user->tasks()->count();
        $hasStudyPlan = $user->studySessions()->exists();

        $steps = [
            'study_preferences' => $hasPreferences,
            'subjects' => $subjectsCount > 0,
            'tasks' => $tasksCount > 0,
            'study_plan' => $hasStudyPlan,
        ];

        $completedSteps = collect($steps)->filter()->count();
        $totalSteps = count($steps);

        $nextAction = null;
        if (!$hasPreferences) {
            $nextAction = 'create_study_preferences';
        } elseif ($subjectsCount === 0) {
            $nextAction = 'create_first_subject';
        } elseif ($tasksCount === 0) {
            $nextAction = 'create_first_task';
        } elseif (!$hasStudyPlan) {
            $nextAction = 'generate_study_plan';
        }

        return response()->json([
            'data' => [
                'steps' => $steps,
                'completed_steps' => $completedSteps,
                'total_steps' => $totalSteps,
                'completion_percentage' => round(($completedSteps / $totalSteps) * 100),
                'next_action' => $nextAction,
                'is_complete' => $completedSteps === $totalSteps,
            ],
            'message' => 'Onboarding status retrieved successfully.',
        ]);
    }

    /**
     * Create default study preferences for a new user.
     */
    public function createDefaults(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->studyPreference) {
            return response()->json([
                'data' => $user->studyPreference,
                'message' => 'Study preferences already exist.',
            ]);
        }

        $defaults = [
            'max_daily_minutes' => 180,
            'session_duration_minutes' => 50,
            'break_duration_minutes' => 10,
            'preferred_start_time' => '18:00',
            'preferred_end_time' => '22:00',
        ];

        $preferences = $user->studyPreference()->create($defaults);

        return response()->json([
            'data' => $preferences,
            'message' => 'Default study preferences created successfully.',
        ], 201);
    }
}
