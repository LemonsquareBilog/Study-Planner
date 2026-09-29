<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Topic extends Model {
 use HasFactory,SoftDeletes;
 protected $fillable=['subject_id','name','description','difficulty','is_active'];
 protected function casts(): array{return ['difficulty'=>'integer','is_active'=>'boolean'];}
 public function subject(){return $this->belongsTo(Subject::class);}
 public function tasks(){return $this->hasMany(Task::class);}
 public function studySessions(){return $this->hasMany(StudySession::class);}
 public function quizzes(){return $this->hasMany(Quiz::class);}
 public function resources(){return $this->belongsToMany(Resource::class,'resource_topic');}
}
