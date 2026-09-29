<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'typed_value' => $this->typed_value,
            'type' => $this->type,
            'description' => $this->description,
            'label' => $this->label,
            'group' => $this->group,
            'sort_order' => $this->sort_order,
            'validation_rules' => $this->validation_rules,
            'impact_description' => $this->impact_description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
