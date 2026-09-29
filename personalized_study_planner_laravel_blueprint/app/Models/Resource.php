<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'resource_type',
        'url',
        'difficulty',
        'is_active',
        'file_path',
        'file_url',
        'file_name',
        'file_size',
        'file_mime_type',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'resource_subject');
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'resource_topic');
    }

    public function recommendationViews()
    {
        return $this->hasMany(RecommendationView::class);
    }
}
