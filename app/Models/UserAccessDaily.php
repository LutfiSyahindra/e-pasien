<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAccessDaily extends Model
{
    protected $table = 'user_access_daily';

    protected $fillable = [
        'user_id',
        'user_access_device_id',
        'access_date',
        'mode',
        'visit_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserAccessDevice::class, 'user_access_device_id');
    }
}
