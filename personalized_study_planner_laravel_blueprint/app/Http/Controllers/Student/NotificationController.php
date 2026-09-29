<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List the student's notifications with pagination.
     * Supports: ?unread=1 to filter only unread.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->notifications();

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        $perPage = $request->integer('per_page', 30);
        $notifications = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $notifications,
            'message' => 'Notifications retrieved successfully.',
        ]);
    }

    /**
     * Get a lightweight count of unread and total notifications.
     */
    public function count(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $unread = $user->notifications()->whereNull('read_at')->count();
        $total = $user->notifications()->count();

        return response()->json([
            'data' => [
                'unread' => $unread,
                'total' => $total,
            ],
            'message' => 'Notification counts retrieved successfully.',
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function read(Request $request, string $notification): JsonResponse
    {
        $item = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $item->update(['read_at' => now()]);

        return response()->json([
            'data' => $item,
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function readAll(Request $request): JsonResponse
    {
        $count = $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => ['marked_read' => $count],
            'message' => "{$count} notification(s) marked as read.",
        ]);
    }
}
