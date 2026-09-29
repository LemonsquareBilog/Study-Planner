<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Task extends Model {
 use HasFactory,SoftDeletes;
 protected $fillable=['user_id','subject_id','topic_id','title','description','task_type','priority','deadline','estimated_minutes','status','completed_at'];
 protected function casts(): array{return ['deadline'=>'datetime','completed_at'=>'datetime','estimated_minutes'=>'integer'];}
 public function user(){return $this->belongsTo(User::class);}
 public function subject(){return $this->belongsTo(Subject::class);}
 public function topic(){return $this->belongsTo(Topic::class);}
 public function studySessions(){return $this->hasMany(StudySession::class);}
}
