<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_attempt_id' => $this->quiz_attempt_id,
            'question_id' => $this->question_id,
            'selected_answer' => $this->selected_answer,
            'is_correct' => (bool) $this->is_correct,
            'correct_answer' => $this->when($this->is_correct === false, $this->correct_answer),
            'question' => new QuestionResource($this->whenLoaded('question')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}