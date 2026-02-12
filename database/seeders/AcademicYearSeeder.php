<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use Illuminate\Support\Str;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        // 2024/2025 - GANJIL (NONAKTIF)
        AcademicYear::firstOrCreate(
            [
                'year' => '2024/2025',
                'semester' => 'ganjil',
            ],
            [
                'id' => (string) Str::uuid(),
                'is_active' => false,
            ]
        );

        // 2024/2025 - GENAP (NONAKTIF)
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

        // 2025/2026 - GANJIL (AKTIF)
        AcademicYear::firstOrCreate(
            [
                'year' => '2025/2026',
                'semester' => 'ganjil',
            ],
            [
                'id' => (string) Str::uuid(),
                'is_active' => true,
            ]
        );

        // 2025/2026 - GENAP (NONAKTIF)
        AcademicYear::firstOrCreate(
            [
                'year' => '2025/2026',
                'semester' => 'genap',
            ],
            [
                'id' => (string) Str::uuid(),
                'is_active' => false,
            ]
        );
    }
}