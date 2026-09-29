<?php
namespace App\Http\Requests\Student;
use Illuminate\Foundation\Http\FormRequest;
class GenerateStudyPlanRequest extends FormRequest {
 public function authorize(): bool{return $this->user()?->role==='student';}
 public function rules(): array{return ['from'=>['required','date'],'to'=>['required','date','after_or_equal:from']];}
}
