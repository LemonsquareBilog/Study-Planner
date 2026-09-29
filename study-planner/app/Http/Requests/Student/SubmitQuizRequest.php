<?php
namespace App\Http\Requests\Student;
use Illuminate\Foundation\Http\FormRequest;
class SubmitQuizRequest extends FormRequest {
 public function authorize(): bool{return $this->user()?->role==='student';}
 public function rules(): array{return [
  'started_at'=>['required','date'],
  'answers'=>['required','array'],
  'answers.*'=>['nullable','in:A,B,C,D'],
 ];}
}
