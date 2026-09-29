<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'resource_type' => ['sometimes', 'required', 'in:article,video,documentation,tutorial,ebook,website,other'],
            'url' => ['sometimes', 'required', 'url', 'max:2048'],
            'difficulty' => ['sometimes', 'required', 'in:beginner,intermediate,advanced'],
            'is_active' => ['boolean'],
            'subject_ids' => ['array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'topic_ids' => ['array'],
            'topic_ids.*' => ['integer', 'exists:topics,id'],
        ];
    }
}
