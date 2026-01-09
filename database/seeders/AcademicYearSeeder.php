<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use Illuminate\Support\Str;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        // GANJIL (AKTIF)
        AcademicYear::firstOrCreate(
            [
                'year' => '2024/2025',
                'semester' => 'ganjil',
            ],
            [
                'id' => (string) Str::uuid(),
                'is_active' => true,
            ]
        );

        // GENAP (NONAKTIF)
        AcademicYear::firstOrCreate(
            [
                'year' => '2024/2025',
                'semester' => 'genap',
            ],
            [
                'id' => (string) Str::uuid(),
                'is_active' => false,
            ]
        );
    }
}