<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get lightweight notification unread/total count for badge updates (UX-009).
     */
    public function count(Request $request): JsonResponse
    {
        $unread = $request->user()->notifications()->whereNull('read_at')->count();
        $total = $request->user()->notifications()->count();

        return response()->json([
            'data' => [
                'unread' => $unread,
                'total' => $total,
            ],
            'message' => 'Notification counts retrieved.',
        ]);
    }

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

        $notifications = $query->latest()->paginate(30);

        return response()->json([
            'data' => $notifications,
            'message' => 'Notifications retrieved successfully.',
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
