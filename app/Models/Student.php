<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'nis',
        'parent_name',
        'status',
    ];

    public function classHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function activeClass()
    {
        return $this->hasOne(StudentClassHistory::class)
            ->where('is_active', true);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}