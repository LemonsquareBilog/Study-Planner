<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'preferred_time_slots' => $this->preferred_time_slots ?? [],
            'max_study_hours_per_day' => (float) $this->max_study_hours_per_day,
            'break_duration_minutes' => (int) $this->break_duration_minutes,
            'learning_style' => $this->learning_style,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
