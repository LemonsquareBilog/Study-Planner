<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'quiz_id' => $this->quiz_id,
            'score' => (float) $this->score,
            'total_questions' => (int) $this->total_questions,
            'correct_answers' => (int) $this->correct_answers,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'quiz' => new QuizResource($this->whenLoaded('quiz')),
            'answers' => $this->whenLoaded('answers'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
