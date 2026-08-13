<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientGuarantorConfiguration extends Model
{
    public const DEFAULT_KEY = 'online-registration';

    protected $fillable = [
        'key',
        'allowed_guarantor_codes',
        'configured_by',
    ];

    protected function casts(): array
    {
        return [
            'allowed_guarantor_codes' => 'array',
        ];
    }

    public function configuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }
}
