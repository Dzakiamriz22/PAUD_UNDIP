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

        $classes = [
            ['category' => 'TK', 'code' => 'A1'],
            ['category' => 'TK', 'code' => 'A2'],
            ['category' => 'TK', 'code' => 'B1'],
            ['category' => 'KB', 'code' => 'KB1'],
        ];

        foreach ($classes as $class) {

            $existing = SchoolClass::where([
                'category' => $class['category'],
                'code' => $class['code'],
                'academic_year_id' => $year->id,
            ])->first();

            if (! $existing) {
                SchoolClass::create([
                    'id' => (string) Str::uuid(),
                    'category' => $class['category'],
                    'code' => $class['code'],
                    'academic_year_id' => $year->id,
                    'homeroom_teacher_id' => null,
                ]);
            }
        }
    }
}
