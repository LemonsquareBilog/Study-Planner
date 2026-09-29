<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'name', 'description', 'difficulty', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['difficulty' => 'integer', 'is_active' => 'boolean'];
    }

    // Predefined color palette for subjects
    public const COLORS = [
        'blue' => '#3B82F6',
        'indigo' => '#6366F1',
        'purple' => '#A855F7',
        'pink' => '#EC4899',
        'red' => '#EF4444',
        'orange' => '#F97316',
        'amber' => '#F59E0B',
        'yellow' => '#EAB308',
        'green' => '#22C55E',
        'teal' => '#14B8A6',
        'cyan' => '#06B6D4',
        'gray' => '#6B7280',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function studySessions()
    {
        return $this->hasMany(StudySession::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'resource_subject');
    }

    public function getColorHexAttribute(): string
    {
        return self::COLORS[$this->color] ?? self::COLORS['blue'];
    }
}
