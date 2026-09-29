<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject')?->id ?? $this->route('subject');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('topics')
                    ->where(fn ($query) => $query->where('subject_id', $subjectId)->whereNull('deleted_at')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'is_active' => ['boolean'],
        ];
    }
}
