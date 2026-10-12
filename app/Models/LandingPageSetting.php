<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageSetting extends Model
{
    public const DEFAULT_KEY = 'default';

    protected $fillable = ['key', 'content', 'images', 'updated_by'];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'images' => 'array',
        ];
    }
}
