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
            'preferred_end_time' => ['required', 'date_format:H:i', 'different:preferred_start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'preferred_end_time.different' => 'The preferred end time must be different from the start time.',
        ];
    }
}
