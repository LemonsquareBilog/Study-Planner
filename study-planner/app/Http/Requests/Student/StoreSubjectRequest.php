<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('subjects')->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('deleted_at')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'color' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
