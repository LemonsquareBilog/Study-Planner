<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * List all users with optional filters.
     * Supports: ?role=student, ?is_active=1, ?search=john
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(30);

        return response()->json($users);
    }

    /**
     * Show a single user's details.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('studyPreference');
        $user->loadCount(['subjects', 'tasks', 'quizAttempts']);

        return response()->json(['data' => $user, 'message' => 'User retrieved successfully.']);
    }

    /**
     * Update a user's role, active status, or profile fields.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return response()->json([
            'data' => $user->fresh(),
            'message' => 'User updated successfully.',
        ]);
    }
}
