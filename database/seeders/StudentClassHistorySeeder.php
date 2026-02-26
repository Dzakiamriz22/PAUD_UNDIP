<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\StudentClassHistory;

class StudentClassHistorySeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        // Fallback: try any academic year, or create a sensible default if none exist
        if (! $academicYear) {
            $academicYear = AcademicYear::first();
        }

        if (! $academicYear) {
            $current = now()->year;
            $next = $current + 1;
            $label = "{$current}/{$next}";

            $academicYear = AcademicYear::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'year' => $label,
                'semester' => 'ganjil',
                'is_active' => true,
            ]);
        }

        $classes = SchoolClass::where('academic_year_id', $academicYear->id)->get();
        $students = Student::all();

        if ($classes->isEmpty() || $students->isEmpty()) {
            $this->command->warn('Student atau Class kosong, seeder dibatalkan.');
            return;
        }

        foreach ($students as $index => $student) {

            // Nonaktifkan histori lama hanya pada tahun ajaran yang sama (gabungkan ganjil/genap berdasarkan `year`)
            $year = $academicYear->year;

            StudentClassHistory::where('student_id', $student->id)
                ->whereHas('academicYear', function ($q) use ($year) {
                    $q->where('year', $year);
                })
                ->update(['is_active' => false]);

            // Bagi rata siswa ke kelas di academic year aktif
            $class = $classes[$index % $classes->count()];

            StudentClassHistory::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'id' => Str::uuid(),
                    'class_id' => $class->id,
                    'is_active' => true,
                ]
            );
        }
    }
}