<?php

namespace App\Services;

class DateService
{
    protected static array $monthNames = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public static function getMonthName(int|string|null $angka): string
    {
        return self::$monthNames[(int) $angka] ?? 0;
    }

    public static function getMonthList(): array
    {
        return self::$monthNames;
    }
}
