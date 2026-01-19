<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReceiptNumberGenerator
{
    public static function generate(): string
    {
        return DB::transaction(function () {
            $year = now()->year;
            $month = now()->month;

            $sequence = DB::table('receipt_sequences')
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                DB::table('receipt_sequences')->insert([
                    'year'        => $year,
                    'month'       => $month,
                    'last_number' => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $number = 1;
            } else {
                $number = $sequence->last_number + 1;

                DB::table('receipt_sequences')
                    ->where('year', $year)
                    ->where('month', $month)
                    ->update([
                        'last_number' => $number,
                        'updated_at'  => now(),
                    ]);
            }

            return str_pad($number, 6, '0', STR_PAD_LEFT)
                . '/BPU/A1.02/RCP/' . self::romanMonth() . '/' . $year;
        });
    }

    protected static function romanMonth(): string
    {
        return [
            1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',
            7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII'
        ][now()->month];
    }
}

