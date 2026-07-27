<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class RegistrationRoleConfiguration extends Model
{
    protected $fillable = [
        'role_id',
        'configured_by',
    ];

    public function getConnectionName(): ?string
    {
        return config('database.default');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function configuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }
}
