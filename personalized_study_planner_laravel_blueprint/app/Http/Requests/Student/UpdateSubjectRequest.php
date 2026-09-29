<?php

namespace App\Http\Requests\Student;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject')?->id ?? $this->route('subject');
        $colorKeys = array_keys(Subject::COLORS);

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('subjects')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('deleted_at'))
                    ->ignore($subjectId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'color' => ['required', 'string', Rule::in($colorKeys)],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'color.in' => 'The selected color is invalid. Please choose from the predefined color palette.',
        ];
    }
}
