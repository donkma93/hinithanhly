<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'error_uuid',
        'exception_class',
        'message',
        'file',
        'line',
        'status_code',
        'method',
        'url',
        'route_name',
        'ip_address',
        'user_agent',
        'user_id',
        'trace',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
