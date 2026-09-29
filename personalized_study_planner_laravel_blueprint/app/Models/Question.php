<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Question extends Model {
 use HasFactory;
 protected $fillable=['quiz_id','question','option_a','option_b','option_c','option_d','correct_answer','question_order'];
 protected $hidden=['correct_answer'];
 public function quiz(){return $this->belongsTo(Quiz::class);}
 public function answers(){return $this->hasMany(QuizAnswer::class);}
}
