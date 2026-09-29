<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'in:admin,student,instructor'],
            'is_active' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:100'],
            'course' => ['nullable', 'string', 'max:150'],
            'year_level' => ['nullable', 'integer', 'between:1,8'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $targetUser = $this->route('user');
            $currentUser = $this->user();

            // Prevent admin from deactivating themselves
            if ($targetUser && $currentUser && $targetUser->id === $currentUser->id) {
                if ($this->has('is_active') && !$this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'You cannot deactivate your own account.');
                }
                if ($this->has('role') && $this->input('role') !== 'admin') {
                    $validator->errors()->add('role', 'You cannot remove your own admin role.');
                }
            }
        });
    }
}
