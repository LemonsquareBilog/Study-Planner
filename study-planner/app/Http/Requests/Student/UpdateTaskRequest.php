<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'task_type' => ['required', 'in:assignment,project,exam,quiz,reading,review,other'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'deadline' => ['required', 'date'],
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'status' => ['sometimes', 'in:pending,in_progress,completed,cancelled'],
        ];
    }
}
