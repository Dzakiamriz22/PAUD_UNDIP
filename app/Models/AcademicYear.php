<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

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

    protected static function booted()
    {
        static::creating(function ($academicYear) {
            if (empty($academicYear->id)) {
                $academicYear->id = (string) Str::uuid();
            }
        });

        static::saving(function ($academicYear) {
            if ($academicYear->is_active) {
                static::where('id', '!=', $academicYear->id)
                    ->update(['is_active' => false]);
            }
        });
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function schoolClasses()
    {
        return $this->hasMany(\App\Models\SchoolClass::class);
    }

    public function getLabelAttribute(): string
    {
        return "{$this->year} – " . ucfirst($this->semester);
    }
}