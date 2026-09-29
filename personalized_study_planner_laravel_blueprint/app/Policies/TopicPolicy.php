<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    /**
     * Ownership is verified through the parent subject's user_id,
     * since topics don't have a direct user_id column.
     */
    public function view(User $user, Topic $topic): bool
    {
        return $user->id === $topic->subject->user_id || $user->role === 'admin';
    }

    public function update(User $user, Topic $topic): bool
    {
        return $user->id === $topic->subject->user_id;
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $user->id === $topic->subject->user_id;
    }
}
