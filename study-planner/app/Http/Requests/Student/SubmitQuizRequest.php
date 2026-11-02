<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        return [
            'submission_id' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'started_at' => ['required', 'date'],
            'answers' => ['present', 'array'],
            'answers.*' => ['nullable', 'in:A,B,C,D'],
        ];
    }
}
