<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Quiz extends Model {
 use HasFactory,SoftDeletes;
 protected $fillable=['topic_id','title','description','passing_score','is_active'];
 protected function casts(): array{return ['passing_score'=>'integer','is_active'=>'boolean'];}
 public function topic(){return $this->belongsTo(Topic::class);}
 public function questions(){return $this->hasMany(Question::class)->orderBy('question_order');}
 public function attempts(){return $this->hasMany(QuizAttempt::class);}
}
