<?php

namespace App\Policies;

use App\Models\StudySession;
use App\Models\User;

class StudySessionPolicy
{
    public function view(User $user, StudySession $session): bool
    {
        return $user->id === $session->user_id || $user->role === 'admin';
    }

    public function update(User $user, StudySession $session): bool
    {
        return $user->id === $session->user_id;
    }

    public function delete(User $user, StudySession $session): bool
    {
        return $user->id === $session->user_id;
    }
}
