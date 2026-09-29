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

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'course',
        'year_level',
        'is_active',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    public function studyPreference()
    {
        return $this->hasOne(StudyPreference::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function studySessions()
    {
        return $this->hasMany(StudySession::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function recommendationViews()
    {
        return $this->hasMany(RecommendationView::class);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    public function getTwoFactorQrCodeSvg(): string
    {
        $google2fa = app('pragmarx.google2fa');
        $qrCode = new \BaconQrCode\Renderer\Image\SvgImageBackEnd();
        $qrCode->setHeight(200);
        $qrCode->setWidth(200);
        
        return $google2fa->getQRCodeInline(
            config('app.name'),
            $this->email,
            $this->two_factor_secret,
            0,
            $qrCode
        );
    }
}
