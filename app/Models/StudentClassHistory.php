<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\SchoolClass;

class StudentClassHistory extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'student_id',
        'class_id',
        'academic_year_id',
        'is_active',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
