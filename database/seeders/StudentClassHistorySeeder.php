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
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();
        $classes = SchoolClass::where('academic_year_id', $academicYear->id)->get();
        $students = Student::all();

        if ($classes->isEmpty() || $students->isEmpty()) {
            $this->command->warn('Student atau Class kosong, seeder dibatalkan.');
            return;
        }

        foreach ($students as $index => $student) {

            // Set semua histori lama jadi tidak aktif
            StudentClassHistory::where('student_id', $student->id)
                ->update(['is_active' => false]);

            // Bagi rata siswa ke kelas
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
