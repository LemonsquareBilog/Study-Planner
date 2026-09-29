<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'method',
        'path',
        'full_url',
        'ip',
        'user_agent',
        'user_id',
        'status_code',
        'response_time_ms',
        'query_params',
        'request_headers',
        'exception_class',
        'exception_message',
    ];

    protected $casts = [
        'query_params' => 'json',
        'request_headers' => 'json',
        'response_time_ms' => 'integer',
        'status_code' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSlowRequests($query, int $threshold = 1000)
    {
        return $query->where('response_time_ms', '>', $threshold);
    }

    public function scopeErrors($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPath($query, string $path)
    {
        return $query->where('path', 'like', "%{$path}%");
    }
}