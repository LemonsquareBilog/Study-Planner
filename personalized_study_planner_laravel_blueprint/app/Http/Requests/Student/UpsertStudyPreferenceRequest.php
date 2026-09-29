<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpsertStudyPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        return [
            'max_daily_minutes' => ['required', 'integer', 'min:15', 'max:720'],
            'session_duration_minutes' => ['required', 'integer', 'min:10', 'max:180'],
            'break_duration_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'preferred_start_time' => ['required', 'date_format:H:i'],
            'preferred_end_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'preferred_start_time.date_format' => 'The preferred start time must be in HH:MM format (e.g., 18:00).',
            'preferred_end_time.date_format' => 'The preferred end time must be in HH:MM format (e.g., 22:00).',
        ];
    }
}
