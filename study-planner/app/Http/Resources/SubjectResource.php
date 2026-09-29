<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'code' => $this->code,
            'color' => $this->color,
            'target_grade' => $this->target_grade,
            'topics_count' => $this->whenCounted('topics'),
            'tasks_count' => $this->whenCounted('tasks'),
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
