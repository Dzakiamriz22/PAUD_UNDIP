<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IncomeType;
use Illuminate\Support\Str;

class IncomeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'SPP Bulanan',
            'Uang Pangkal',
            'Seragam',
            'Kegiatan Tahunan',
        ];

        foreach ($types as $type) {
            IncomeType::updateOrCreate(
                ['name' => $type],
                ['id' => Str::uuid()]
            );
        }
    }
}