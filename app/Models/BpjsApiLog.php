<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BpjsApiLog extends Model
{
    protected $fillable = [
        'request_id',
        'service',
        'endpoint',
        'method',
        'request_payload',
        'response_metadata',
        'http_code',
        'duration_ms',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_metadata' => 'array',
            'http_code' => 'integer',
            'duration_ms' => 'integer',
        ];
    }
}
