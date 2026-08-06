<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorPhoto extends Model
{
    protected $fillable = [
        'doctor_code',
        'image_path',
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getImageUrlAttribute(): string
    {
        return '/storage/'.str_replace('\\', '/', ltrim($this->image_path, '/\\'));
    }
}
