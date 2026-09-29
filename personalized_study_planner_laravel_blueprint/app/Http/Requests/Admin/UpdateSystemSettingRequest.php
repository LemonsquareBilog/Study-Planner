<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:500'],
            'label' => ['nullable', 'string', 'max:100'],
            'group' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'validation_rules' => ['nullable', 'json'],
            'impact_description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
