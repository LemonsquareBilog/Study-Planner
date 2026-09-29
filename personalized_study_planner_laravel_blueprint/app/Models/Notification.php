<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Illuminate\Broadcasting\PrivateChannel;

class Notification extends Model
{
    use Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'scheduled_at',
        'read_at',
        'sent_at',
        'delivery_status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->id ??= (string) Str::uuid();
        });

        // Broadcast notification when created
        static::created(function ($notification) {
            if ($notification->delivery_status !== 'sent') {
                return;
            }

            broadcast(new \App\Events\NotificationCreated($notification))
                ->toOthers();
        });

        // Broadcast when notification is read
        static::updated(function ($notification) {
            if ($notification->isDirty('read_at') && $notification->read_at) {
                broadcast(new \App\Events\NotificationRead($notification))
                    ->toOthers();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the broadcast channel for this notification.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->user_id}");
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
