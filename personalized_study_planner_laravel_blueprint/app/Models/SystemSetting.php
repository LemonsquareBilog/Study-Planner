<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'description', 'label', 'group', 'sort_order', 'validation_rules', 'impact_description'];

    protected function casts(): array
    {
        return [
            'validation_rules' => 'json',
            'typed_value' => 'object',
        ];
    }

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'float' => (float) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    public function getValidationRulesAttribute(): array
    {
        return $this->attributes['validation_rules'] ?? [];
    }
}
