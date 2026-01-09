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

    /**
     * Scope: tahun ajaran aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Relasi ke kelas
     */
    public function schoolClasses()
    {
        return $this->hasMany(\App\Models\SchoolClass::class);
    }

    /**
     * Label: 2024/2025 – Ganjil
     */
    public function getLabelAttribute()
    {
        return "{$this->year} – " . ucfirst($this->semester);
    }
}