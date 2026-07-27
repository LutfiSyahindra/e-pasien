<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineRegistrationAudit extends Model
{
    protected $fillable = [
        'no_rawat',
        'no_reg',
        'registration_date',
        'registration_time',
        'patient_medical_record_number',
        'patient_name',
        'doctor_code',
        'doctor_name',
        'clinic_code',
        'clinic_name',
        'guarantor_code',
        'guarantor_name',
        'registered_by_user_id',
        'registered_by_name',
        'registered_by_username',
        'registered_by_roles',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'registered_by_roles' => 'array',
        ];
    }

    public function getConnectionName(): ?string
    {
        return config('database.default');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
