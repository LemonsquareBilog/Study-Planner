<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'user_id',
        'ip',
        'payload',
        'resource_type',
        'resource_id',
        'description',
    ];

    protected $casts = [
        'payload' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resource()
    {
        return $this->morphTo();
    }

    public static function log(
        string $eventType,
        ?string $description = null,
        array $payload = [],
        ?Model $resource = null
    ): self {
        return self::create([
            'event_type' => $eventType,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'payload' => $payload,
            'resource_type' => $resource?->getMorphClass(),
            'resource_id' => $resource?->getKey(),
            'description' => $description,
        ]);
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByResource($query, string $type, int $id)
    {
        return $query->where('resource_type', $type)->where('resource_id', $id);
    }
}