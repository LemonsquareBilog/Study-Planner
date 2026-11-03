<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Notification extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['task_id', 'event_key', 'cancelled_at', 'user_id', 'type', 'title', 'message', 'scheduled_at', 'read_at', 'sent_at', 'delivery_status'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'read_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->id ??= (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
