<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IncomeType;

class IncomeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Biaya utama
            ['Uang Pendaftaran', false],
            ['Biaya Sarana & Prasarana', false],
            ['Biaya Perlengkapan & Kegiatan', false],
            ['Biaya Seragam', false],
            ['SPP Siswa Lama', false],
            ['SPP Harian', false],
            ['Denda Keterlambatan', false],
            ['Daftar Ulang TPA', false],

            // Diskon
            ['Diskon Alumni KB Permata Undip', true],
            ['Diskon Sarana & Prasarana Anak Kembar', true],
        ];

        foreach ($types as [$name, $isDiscount]) {
            IncomeType::firstOrCreate(
                ['name' => $name],
                [
                    'is_discount' => $isDiscount,
                    'is_active'   => false,
                ]
            );
        }
    }
}