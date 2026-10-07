<?php

namespace App\Support\Epasien;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DoctorScheduleCache
{
    private const REVISION_KEY = 'epasien:khanza:doctor-schedules:revision';

    public static function key(string $key): string
    {
        return $key.':'.Cache::get(self::REVISION_KEY, 'initial');
    }

    public static function invalidate(): void
    {
        Cache::forever(self::REVISION_KEY, (string) Str::uuid());
    }
}
