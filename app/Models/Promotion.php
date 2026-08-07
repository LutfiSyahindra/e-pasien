<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory;

    public const TIMEZONE = 'Asia/Jakarta';

    public const CATEGORY_PROMOTION = 'promotion';

    public const CATEGORY_INFORMATION = 'information';

    public const CATEGORIES = [self::CATEGORY_PROMOTION, self::CATEGORY_INFORMATION];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const DURATION_UNITS = ['hour', 'day', 'month', 'year'];

    protected $fillable = [
        'creator_id',
        'category',
        'title',
        'caption',
        'image_path',
        'duration_value',
        'duration_unit',
        'starts_at',
        'ends_at',
        'status',
        'published_at',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(PromotionView::class);
    }

    public function scopeActive(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $at = $at
            ? CarbonImmutable::instance($at)->utc()
            : now('UTC')->toImmutable();

        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at);
    }

    public function getStartsAtWibAttribute(): CarbonInterface
    {
        return $this->starts_at->copy()->setTimezone(self::TIMEZONE)->locale('id');
    }

    public function getEndsAtWibAttribute(): CarbonInterface
    {
        return $this->ends_at->copy()->setTimezone(self::TIMEZONE)->locale('id');
    }

    public function getImageUrlAttribute(): string
    {
        $path = str_replace('\\', '/', ltrim($this->image_path, '/\\'));

        return '/storage/'.$path;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->starts_at->lte(now())
            && $this->ends_at->gt(now());
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_ARCHIVED) {
            return 'Diarsipkan';
        }

        if ($this->status === self::STATUS_DRAFT) {
            return 'Draf';
        }

        if ($this->starts_at->isFuture()) {
            return 'Terjadwal';
        }

        if ($this->ends_at->isPast()) {
            return 'Berakhir';
        }

        return 'Sedang tayang';
    }

    public function getDurationLabelAttribute(): string
    {
        $labels = [
            'hour' => 'jam',
            'day' => 'hari',
            'month' => 'bulan',
            'year' => 'tahun',
        ];

        return $this->duration_value.' '.($labels[$this->duration_unit] ?? $this->duration_unit);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_INFORMATION => 'Informasi',
            default => 'Promosi',
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_INFORMATION => 'bi-info-circle-fill',
            default => 'bi-megaphone-fill',
        };
    }
}
