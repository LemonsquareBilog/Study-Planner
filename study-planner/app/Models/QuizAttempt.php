<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class QuizAttempt extends Model {
 use HasFactory;
 protected $fillable=['user_id','quiz_id','score','total_questions','percentage','started_at','completed_at'];
 protected function casts(): array{return ['score'=>'integer','total_questions'=>'integer','percentage'=>'decimal:2','started_at'=>'datetime','completed_at'=>'datetime'];}
 public function user(){return $this->belongsTo(User::class);}
 public function quiz(){return $this->belongsTo(Quiz::class);}
 public function answers(){return $this->hasMany(QuizAnswer::class);}
}
