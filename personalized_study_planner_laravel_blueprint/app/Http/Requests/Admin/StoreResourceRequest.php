<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'resource_type' => ['required', 'in:article,video,documentation,tutorial,ebook,website,other'],
            'url' => ['required', 'url', 'max:2048'],
            'difficulty' => ['required', 'in:beginner,intermediate,advanced'],
            'is_active' => ['boolean'],
            'subject_ids' => ['array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'topic_ids' => ['array'],
            'topic_ids.*' => ['integer', 'exists:topics,id'],
        ];
    }
}
