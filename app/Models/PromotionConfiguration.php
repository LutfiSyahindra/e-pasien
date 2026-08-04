<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionConfiguration extends Model
{
    public const DEFAULT_KEY = 'default';

    protected $fillable = [
        'key',
        'default_duration_value',
        'default_duration_unit',
        'auto_delete_enabled',
        'delete_grace_value',
        'delete_grace_unit',
        'configured_by',
    ];

    protected function casts(): array
    {
        return [
            'auto_delete_enabled' => 'boolean',
            'default_duration_value' => 'integer',
            'delete_grace_value' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['key' => self::DEFAULT_KEY],
            [
                'default_duration_value' => 1,
                'default_duration_unit' => 'day',
                'auto_delete_enabled' => true,
                'delete_grace_value' => 0,
                'delete_grace_unit' => 'hour',
            ],
        );
    }

    public function configuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }

    public function deletionCutoff(?CarbonInterface $at = null): CarbonImmutable
    {
        $cutoff = $at
            ? CarbonImmutable::instance($at)
            : CarbonImmutable::now();
        $value = $this->delete_grace_value;

        return match ($this->delete_grace_unit) {
            'hour' => $cutoff->subHours($value),
            'day' => $cutoff->subDays($value),
            'month' => $cutoff->subMonthsNoOverflow($value),
            'year' => $cutoff->subYearsNoOverflow($value),
            default => $cutoff,
        };
    }
}
