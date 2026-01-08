<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use Illuminate\Support\Str;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        // GANJIL
        if (! AcademicYear::where('year', '2024/2025')->where('semester', 'Ganjil')->exists()) {
            AcademicYear::create([
                'id' => (string) Str::uuid(),
                'year' => '2024/2025',
                'semester' => 'Ganjil',
                'is_active' => true,
            ]);
        }

        // GENAP
        if (! AcademicYear::where('year', '2024/2025')->where('semester', 'Genap')->exists()) {
            AcademicYear::create([
                'id' => (string) Str::uuid(),
                'year' => '2024/2025',
                'semester' => 'Genap',
                'is_active' => false,
            ]);
        }
    }
}