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
        $unread = $request->user()->notifications()->whereNull('cancelled_at')->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))->whereNull('read_at')->count();
        $total = $request->user()->notifications()->whereNull('cancelled_at')->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))->count();

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
        $query = $request->user()->notifications()->whereNull('cancelled_at')->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()));

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->paginate(30);
        $upcoming = $request->user()->tasks()
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('deadline', '>', now())
            ->orderBy('deadline')->orderBy('id')
            ->limit(5)->get(['id', 'title', 'deadline', 'status']);

        return response()->json([
            'data' => array_merge($notifications->toArray(), ['upcoming_deadlines' => $upcoming]),
            'message' => 'Notifications retrieved successfully.',
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function read(Request $request, string $notification): JsonResponse
    {
        $item = $request->user()
            ->notifications()->whereNull('cancelled_at')->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))
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
            ->notifications()->whereNull('cancelled_at')->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => ['marked_read' => $count],
            'message' => "{$count} notification(s) marked as read.",
        ]);
    }
}
