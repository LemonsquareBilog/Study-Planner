<?php
namespace App\Http\Requests\Student;
use Illuminate\Foundation\Http\FormRequest;
class StoreTaskRequest extends FormRequest {
 public function authorize(): bool{return $this->user()?->role==='student';}
 public function rules(): array{return [
  'subject_id'=>['required','integer','exists:subjects,id'],
  'topic_id'=>['nullable','integer','exists:topics,id'],
  'title'=>['required','string','max:200'],
  'description'=>['nullable','string'],
  'task_type'=>['required','in:assignment,project,exam,quiz,reading,review,other'],
  'priority'=>['required','in:low,medium,high,urgent'],
  'deadline'=>['required','date','after_or_equal:today'],
  'estimated_minutes'=>['required','integer','min:1','max:1440'],
 ];}

 public function messages(): array {
  return [
   'deadline.after_or_equal' => 'The task deadline must be today or in the future.',
  ];
 }
}
