<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IncomeType;

class IncomeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'SPP Bulanan',
            'SPP Harian',
            'Uang Pendaftaran',
            'Biaya Seragam',
            'Biaya Sarana & Prasarana',
            'Biaya Perlengkapan & Kegiatan',
            'Denda Keterlambatan',
            'Daftar Ulang TPA',
        ];

        foreach ($types as $name) {
            IncomeType::firstOrCreate(
                ['name' => $name],
                [
                    'is_discount' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}