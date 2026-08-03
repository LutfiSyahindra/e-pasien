<?php

namespace App\Support\Epasien;

final class DoctorScheduleDay
{
    public const EPASIEN_DAYS = [
        'SENIN',
        'SELASA',
        'RABU',
        'KAMIS',
        'JUMAT',
        'SABTU',
        'MINGGU',
    ];

    public const ACCEPTED_DAYS = [
        ...self::EPASIEN_DAYS,
        'AKHAD',
        'AHAD',
    ];

    public const ACCEPTED_FILTER_DAYS = [
        'SEMUA',
        ...self::ACCEPTED_DAYS,
    ];

    public static function toEpasien(mixed $day): string
    {
        $day = strtoupper(trim((string) $day));

        return match ($day) {
            'AKHAD', 'AHAD' => 'MINGGU',
            default => $day,
        };
    }

    public static function toDatabase(mixed $day): string
    {
        $day = self::toEpasien($day);

        return $day === 'MINGGU' ? 'AKHAD' : $day;
    }
}
