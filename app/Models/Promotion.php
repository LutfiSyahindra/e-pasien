<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Promotion extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const DURATION_UNITS = ['hour', 'day', 'month', 'year'];

    protected $fillable = [
        'creator_id',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
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
}
