<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::where('is_active', true)->first();

        // If no active academic year exists (e.g. migrations/seeds run in unexpected order),
        // fall back to the first available year or create a sensible default to avoid crash.
        if (! $year) {
            $year = AcademicYear::first();
        }

        if (! $year) {
            // Create a default academic year (current year / next year) as active
            $current = now()->year;
            $next = $current + 1;
            $defaultLabel = "{$current}/{$next}";

            $year = AcademicYear::create([
                'id' => (string) Str::uuid(),
                'year' => $defaultLabel,
                'semester' => 'ganjil',
                'is_active' => true,
            ]);
        }

        $categories = [
            'TK',
            'KB',
            'TPA_PAUD',
            'TPA_SD',
            'TPA_TK',
            'TPA_KB',
        ];

        foreach ($categories as $category) {
            $exists = SchoolClass::where([
                'category' => $category,
                'code' => $category,
                'academic_year_id' => $year->id,
            ])->exists();

            if (! $exists) {
                SchoolClass::create([
                    'id' => (string) Str::uuid(),
                    'category' => $category,
                    'code' => $category,
                    'academic_year_id' => $year->id,
                    'homeroom_teacher_id' => null,
                ]);
            }
        }
    }
}