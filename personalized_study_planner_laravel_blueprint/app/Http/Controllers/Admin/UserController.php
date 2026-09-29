<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $perPage = $request->integer('per_page', 30);
        $users = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => UserResource::collection($users),
            'message' => 'Users retrieved successfully.',
        ]);
    }

    /**
     * Show a single user's details.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('studyPreference');
        $user->loadCount(['subjects', 'tasks', 'quizAttempts']);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User retrieved successfully.',
        ]);
    }

    /**
     * Update a user's role, active status, or profile fields.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return response()->json([
            'data' => new UserResource($user->fresh()),
            'message' => 'User updated successfully.',
        ]);
    }

    /**
     * Bulk actions on users (activate, deactivate, delete, change role).
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete,change_role'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
            'role' => ['required_if:action,change_role', 'in:student,admin'],
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        // Prevent admin from modifying themselves
        $currentUserId = $request->user()->id;
        if (in_array($currentUserId, $ids)) {
            throw ValidationException::withMessages([
                'ids' => 'You cannot perform bulk actions on your own account.',
            ]);
        }

        $count = 0;

        DB::transaction(function () use ($action, $ids, $request, &$count) {
            $users = User::withTrashed()->whereIn('id', $ids)->get();

            foreach ($users as $user) {
                match ($action) {
                    'activate' => $user->update(['is_active' => true]),
                    'deactivate' => $user->update(['is_active' => false]),
                    'delete' => $user->delete(),
                    'change_role' => $user->update(['role' => $request->input('role')]),
                };
                $count++;
            }
        });

        return response()->json([
            'data' => ['affected' => $count],
            'message' => "{$count} user(s) {$action}d successfully.",
        ]);
    }

    /**
     * Export users to CSV.
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => ['required', 'in:csv,json'],
            'role' => ['nullable', 'in:student,admin'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->with('studyPreference')->get();

        $format = $request->input('format', 'csv');
        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . ".{$format}";

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($users) {
                $handle = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($handle, [
                    'ID', 'Name', 'Email', 'Role', 'Course', 'Year Level', 
                    'Is Active', 'Email Verified', 'Has 2FA', 'Created At',
                    'Study Preference: Max Daily Minutes', 'Study Preference: Session Duration',
                    'Study Preference: Break Duration', 'Study Preference: Start Time',
                    'Study Preference: End Time',
                ]);

                foreach ($users as $user) {
                    $pref = $user->studyPreference;
                    fputcsv($handle, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->role,
                        $user->course ?? '',
                        $user->year_level ?? '',
                        $user->is_active ? 'Yes' : 'No',
                        $user->email_verified_at ? 'Yes' : 'No',
                        $user->hasTwoFactorEnabled() ? 'Yes' : 'No',
                        $user->created_at?->toIso8601String() ?? '',
                        $pref?->max_daily_minutes ?? '',
                        $pref?->session_duration_minutes ?? '',
                        $pref?->break_duration_minutes ?? '',
                        $pref?->preferred_start_time ?? '',
                        $pref?->preferred_end_time ?? '',
                    ]);
                }
                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        // JSON format
        return response()->json([
            'data' => UserResource::collection($users),
            'message' => 'Users exported successfully.',
        ])->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
