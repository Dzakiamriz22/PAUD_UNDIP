<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicYear extends Model
{
    use HasFactory;

    protected $table = 'academic_years';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'year',
        'semester',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
