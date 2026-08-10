<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientQueueCallEvent extends Model
{
    protected $fillable = [
        'service_date',
        'visit_number',
        'medical_record_number',
        'queue_number',
        'doctor_code',
        'clinic_code',
        'doctor_name',
        'clinic_name',
        'called_at',
        'detected_at',
        'queued_at',
        'dispatched_at',
        'recipients_count',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'called_at' => 'datetime',
            'detected_at' => 'datetime',
            'queued_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'recipients_count' => 'integer',
        ];
    }
}
