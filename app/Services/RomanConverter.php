<?php

namespace App\Services;

class RomanConverter
{
    public static function fromNumber(int $number): string
    {
        $map = [
            1000 => 'M',
            900 => 'CM',
            500 => 'D',
            400 => 'CD',
            100 => 'C',
            90 => 'XC',
            50 => 'L',
            40 => 'XL',
            10 => 'X',
            9 => 'IX',
            5 => 'V',
            4 => 'IV',
            1 => 'I',
        ];

        $result = '';
        foreach ($map as $value => $symbol) {
            while ($number >= $value) {
                $result .= $symbol;
                $number -= $value;
            }
        }

        return $result;
    }

    public static function toNumber(string $roman): int
    {
        $map = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1,
        ];

        $i = 0;
        $result = 0;

        while ($i < strlen($roman)) {
            // cek dua karakter dulu (misalnya "CM", "IV")
            if ($i + 1 < strlen($roman) && isset($map[substr($roman, $i, 2)])) {
                $result += $map[substr($roman, $i, 2)];
                $i += 2;
            } else {
                $result += $map[$roman[$i]];
                $i++;
            }
        }

        return $result;
    }
}
