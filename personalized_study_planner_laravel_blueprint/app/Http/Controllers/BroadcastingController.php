<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class BroadcastingController extends Controller
{
    /**
     * Authorize channel subscription.
     */
    public function authorize(Request $request): JsonResponse
    {
        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        // Parse channel name to determine type and parameters
        if (Str::startsWith($channelName, 'private-user.')) {
            return $this->authorizeUserChannel($request, $channelName);
        }

        if (Str::startsWith($channelName, 'private-admin.')) {
            return $this->authorizeAdminChannel($request, $channelName);
        }

        if (Str::startsWith($channelName, 'private-study_session.')) {
            return $this->authorizeStudySessionChannel($request, $channelName);
        }

        if (Str::startsWith($channelName, 'presence-study_room.')) {
            return $this->authorizePresenceChannel($request, $channelName);
        }

        return response()->json([
            'error' => [
                'code' => 'CHANNEL_NOT_AUTHORIZED',
                'message' => 'You are not authorized to subscribe to this channel.',
                'details' => [],
            ],
        ], 403);
    }

    /**
     * Authorize user-specific private channel.
     */
    protected function authorizeUserChannel(Request $request, string $channelName): JsonResponse
    {
        $userId = (int) Str::after($channelName, 'private-user.');
        
        // Users can only subscribe to their own channel
        if ($request->user()->id !== $userId) {
            return response()->json([
                'error' => [
                    'code' => 'CHANNEL_NOT_AUTHORIZED',
                    'message' => 'You can only subscribe to your own channel.',
                    'details' => [],
                ],
            ], 403);
        }

        return response()->json([
            'auth' => $this->generateAuthString($request),
            'channel_data' => json_encode([
                'user_id' => $userId,
                'name' => $request->user()->name,
            ]),
        ]);
    }

    /**
     * Authorize admin channel.
     */
    protected function authorizeAdminChannel(Request $request, string $channelName): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'error' => [
                    'code' => 'CHANNEL_NOT_AUTHORIZED',
                    'message' => 'Admin access required.',
                    'details' => [],
                ],
            ], 403);
        }

        return response()->json([
            'auth' => $this->generateAuthString($request),
            'channel_data' => json_encode([
                'user_id' => $request->user()->id,
                'name' => $request->user()->name,
                'role' => 'admin',
            ]),
        ]);
    }

    /**
     * Authorize study session channel.
     */
    protected function authorizeStudySessionChannel(Request $request, string $channelName): JsonResponse
    {
        $sessionId = (int) Str::after($channelName, 'private-study_session.');
        
        $session = \App\Models\StudySession::find($sessionId);
        
        if (!$session || $session->user_id !== $request->user()->id) {
            return response()->json([
                'error' => [
                    'code' => 'CHANNEL_NOT_AUTHORIZED',
                    'message' => 'Study session not found or access denied.',
                    'details' => [],
                ],
            ], 403);
        }

        return response()->json([
            'auth' => $this->generateAuthString($request),
            'channel_data' => json_encode([
                'session_id' => $sessionId,
                'user_id' => $request->user()->id,
            ]),
        ]);
    }

    /**
     * Authorize presence channel for study rooms.
     */
    protected function authorizePresenceChannel(Request $request, string $channelName): JsonResponse
    {
        $roomId = (int) Str::after($channelName, 'presence-study_room.');
        
        // Verify user has access to this study room (e.g., shared subject/topic)
        $hasAccess = $this->verifyStudyRoomAccess($request->user(), $roomId);
        
        if (!$hasAccess) {
            return response()->json([
                'error' => [
                    'code' => 'CHANNEL_NOT_AUTHORIZED',
                    'message' => 'You do not have access to this study room.',
                    'details' => [],
                ],
            ], 403);
        }

        return response()->json([
            'auth' => $this->generateAuthString($request),
            'channel_data' => json_encode([
                'user_id' => $request->user()->id,
                'name' => $request->user()->name,
                'avatar' => $request->user()->avatar_url ?? null,
                'room_id' => $roomId,
            ]),
        ]);
    }

    /**
     * Generate Pusher auth string.
     */
    protected function generateAuthString(Request $request): string
    {
        $pusher = app('pusher');
        return $pusher->socket_auth($request->input('channel_name'), $request->input('socket_id'));
    }

    /**
     * Verify user has access to study room.
     */
    protected function verifyStudyRoomAccess(User $user, int $roomId): bool
    {
        // Implement your study room access logic here
        // For example: check if user is enrolled in the subject/topic for this room
        return true; // Placeholder
    }
}