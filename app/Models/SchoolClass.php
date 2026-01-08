<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'category',
        'code',
        'academic_year_id',
        'homeroom_teacher_id',
    ];

    /* ================= RELATIONS ================= */

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher()
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function students()
    {
        return $this->hasMany(StudentClassHistory::class, 'class_id');
    }
}
