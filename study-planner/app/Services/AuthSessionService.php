<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthSessionService
{
    public function issue(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $access = $user->createToken('study-planner', ['*'], now()->addHour());
            $refresh = $user->createToken('refresh:'.$access->accessToken->id, ['refresh'], now()->addDays(30));

            return [
                'token' => $access->plainTextToken,
                'refresh_token' => $refresh->plainTextToken,
                'user' => $user->load('studyPreference'),
            ];
        });
    }
}
