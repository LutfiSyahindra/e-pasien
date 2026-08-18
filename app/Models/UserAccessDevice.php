<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAccessDevice extends Model
{
    public const MODE_WEB = 'web';

    public const MODE_PWA = 'pwa';

    protected $fillable = [
        'user_id',
        'device_uuid',
        'platform',
        'browser',
        'last_mode',
        'is_pwa_installed',
        'installed_at',
        'first_seen_at',
        'last_seen_at',
        'last_web_seen_at',
        'last_pwa_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pwa_installed' => 'boolean',
            'installed_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_web_seen_at' => 'datetime',
            'last_pwa_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyAccess(): HasMany
    {
        return $this->hasMany(UserAccessDaily::class);
    }
}
