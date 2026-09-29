<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class StudyPreference extends Model {
 use HasFactory;
 protected $fillable=['user_id','max_daily_minutes','session_duration_minutes','break_duration_minutes','preferred_start_time','preferred_end_time'];
 protected function casts(): array { return ['max_daily_minutes'=>'integer','session_duration_minutes'=>'integer','break_duration_minutes'=>'integer']; }
 public function user(){return $this->belongsTo(User::class);}
}
