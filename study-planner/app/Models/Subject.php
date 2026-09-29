<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Subject extends Model {
 use HasFactory,SoftDeletes;
 protected $fillable=['user_id','name','description','difficulty','color','is_active'];
 protected function casts(): array{return ['difficulty'=>'integer','is_active'=>'boolean'];}
 public function user(){return $this->belongsTo(User::class);}
 public function topics(){return $this->hasMany(Topic::class);}
 public function tasks(){return $this->hasMany(Task::class);}
 public function studySessions(){return $this->hasMany(StudySession::class);}
 public function resources(){return $this->belongsToMany(Resource::class,'resource_subject');}
}
