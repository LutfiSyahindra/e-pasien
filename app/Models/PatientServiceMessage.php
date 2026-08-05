<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PatientServiceMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PatientServiceConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'patient_service_message_reads', 'message_id', 'user_id')
            ->withPivot('read_at');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'patient_service_message_deliveries', 'message_id', 'user_id')
            ->withPivot('delivered_at');
    }

    public function getDeliveryStatusAttribute(): string
    {
        if ((int) ($this->readers_count ?? 0) > 0) {
            return 'read';
        }

        if ((int) ($this->recipients_count ?? 0) > 0) {
            return 'delivered';
        }

        return 'sent';
    }

    public function getDeliveryStatusLabelAttribute(): string
    {
        return match ($this->delivery_status) {
            'read' => 'Sudah dibaca',
            'delivered' => 'Sudah masuk',
            default => 'Terkirim',
        };
    }
}
