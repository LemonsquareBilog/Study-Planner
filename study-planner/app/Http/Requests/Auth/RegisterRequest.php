<?php

namespace App\Http\Requests\Auth;

use App\Rules\SafePassword;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['bail', 'required', 'confirmed', 'string', 'min:15', 'max:128', new SafePassword],
            'course' => ['nullable', 'string', 'max:150'],
            'year_level' => ['nullable', 'integer', 'between:1,8'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name.',
            'email.required' => 'An email address is required.',
            'email.email' => 'Please enter a valid email address format (e.g. student@university.edu).',
            'email.unique' => 'This email is already registered. Try signing in instead.',
            'password.required' => 'A password is required.',
            'password.confirmed' => 'Password confirmation does not match the password entered above.',
            'password.min' => 'Password must contain at least 15 characters.',
        ];
    }
}
