<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'subject_id' => $this->subject_id,
            'topic_id' => $this->topic_id,
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date?->toIso8601String(),
            'priority' => $this->priority,
            'status' => $this->status,
            'estimated_minutes' => (int) $this->estimated_minutes,
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'topic' => new TopicResource($this->whenLoaded('topic')),
            'study_sessions' => StudySessionResource::collection($this->whenLoaded('studySessions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
