<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Create a single in-app notification for a user.
     */
    public function create(User $user, string $type, string $title, string $message, $scheduledAt = null): Notification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'scheduled_at' => $scheduledAt,
            'delivery_status' => 'pending',
        ]);
    }

    /**
     * Mark a notification as sent (e.g., after email delivery).
     */
    public function markAsSent(Notification $notification): void
    {
        $notification->update([
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark a notification as failed (e.g., email delivery failure).
     */
    public function markAsFailed(Notification $notification): void
    {
        $notification->update([
            'delivery_status' => 'failed',
        ]);
    }

    /**
     * Bulk-create notifications for multiple users (e.g., scheduled reminders).
     */
    public function createBulk(array $notifications): int
    {
        $count = 0;
        foreach ($notifications as $entry) {
            $user = $entry['user'];
            $this->create(
                $user,
                $entry['type'],
                $entry['title'],
                $entry['message'],
                $entry['scheduled_at'] ?? null,
            );
            $count++;
        }
        return $count;
    }
}
