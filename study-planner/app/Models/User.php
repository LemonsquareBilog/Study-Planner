<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name','email','password','role','course','year_level','is_active'];
    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at'=>'datetime','password'=>'hashed','is_active'=>'boolean'];
    }

    public function studyPreference(){ return $this->hasOne(StudyPreference::class); }
    public function subjects(){ return $this->hasMany(Subject::class); }
    public function tasks(){ return $this->hasMany(Task::class); }
    public function studySessions(){ return $this->hasMany(StudySession::class); }
    public function quizAttempts(){ return $this->hasMany(QuizAttempt::class); }
    public function notifications(){ return $this->hasMany(Notification::class); }
    public function recommendationViews(){ return $this->hasMany(RecommendationView::class); }
}
