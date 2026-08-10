<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorArrivalEvent extends Model
{
    protected $fillable = [
        'service_date',
        'doctor_code',
        'clinic_code',
        'doctor_name',
        'clinic_name',
        'detected_at',
        'queued_at',
        'dispatched_at',
        'recipients_count',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'detected_at' => 'datetime',
            'queued_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'recipients_count' => 'integer',
        ];
    }
}
