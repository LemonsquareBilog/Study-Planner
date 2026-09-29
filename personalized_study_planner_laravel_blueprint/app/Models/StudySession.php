<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class StudySession extends Model {
 use HasFactory;
 protected $fillable=['user_id','subject_id','topic_id','task_id','scheduled_date','start_time','end_time','duration_minutes','status','started_at','completed_at'];
 protected function casts(): array{return ['scheduled_date'=>'date','duration_minutes'=>'integer','started_at'=>'datetime','completed_at'=>'datetime'];}
 public function user(){return $this->belongsTo(User::class);}
 public function subject(){return $this->belongsTo(Subject::class);}
 public function topic(){return $this->belongsTo(Topic::class);}
 public function task(){return $this->belongsTo(Task::class);}
}
