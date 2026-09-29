<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\StudySession;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User-specific private channel for notifications and real-time updates
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === $userId ? ['id' => $user->id, 'name' => $user->name] : false;
});

// Admin system channel
Broadcast::channel('admin.system', function (User $user) {
    return $user->role === 'admin' ? ['id' => $user->id, 'name' => $user->name, 'role' => 'admin'] : false;
});

// Study session channel for real-time session updates
Broadcast::channel('study_session.{sessionId}', function (User $user, int $sessionId) {
    $session = StudySession::find($sessionId);
    
    if (!$session || $session->user_id !== $user->id) {
        return false;
    }
    
    return ['id' => $session->id, 'user_id' => $user->id, 'status' => $session->status];
});

// Presence channel for collaborative study rooms
Broadcast::channel('study_room.{roomId}', function (User $user, int $roomId) {
    // Verify user has access to this study room
    // This is a placeholder - implement your access logic
    $hasAccess = true; // Replace with actual check
    
    if (!$hasAccess) {
        return false;
    }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar_url ?? null,
        'room_id' => $roomId,
    ];
});