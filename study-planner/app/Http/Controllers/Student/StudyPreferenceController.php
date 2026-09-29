<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpsertStudyPreferenceRequest;
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
}
