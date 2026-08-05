<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PatientServiceConversation extends Model
{
    use HasFactory;

    public const STATUS_WAITING_ADMIN = 'waiting_admin';

    public const STATUS_WAITING_PATIENT = 'waiting_patient';

    public const STATUS_CLOSED = 'closed';

    public const CATEGORIES = ['question', 'complaint', 'technical', 'suggestion'];

    protected $fillable = [
        'patient_id',
        'assigned_admin_id',
        'category',
        'subject',
        'status',
        'last_message_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PatientServiceMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(PatientServiceMessage::class, 'conversation_id')->latestOfMany();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('EPASIEN.MENU.PASIEN_SERVICE.KELOLA')) {
            return $query;
        }

        return $query->where('patient_id', $user->id);
    }

    public function isVisibleTo(User $user): bool
    {
        return $this->patient_id === $user->id
            || $user->can('EPASIEN.MENU.PASIEN_SERVICE.KELOLA');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'complaint' => 'Laporan Kendala',
            'technical' => 'Kendala Teknis',
            'suggestion' => 'Saran & Masukan',
            default => 'Pertanyaan',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING_PATIENT => 'Menunggu Pasien',
            self::STATUS_CLOSED => 'Selesai',
            default => 'Menunggu Tim Pasien Service',
        };
    }
}
