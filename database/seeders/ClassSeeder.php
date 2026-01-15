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
        $year = AcademicYear::where('is_active', true)->firstOrFail();

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