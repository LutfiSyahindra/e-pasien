<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionNotificationUserConfiguration extends Model
{
    protected $fillable = [
        'user_id',
        'configured_by',
    ];

    public function getConnectionName(): ?string
    {
        return config('database.default');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function configuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }
}
