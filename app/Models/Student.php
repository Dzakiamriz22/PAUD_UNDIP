<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Tambahkan ini!

class Student extends Model
{
    use HasFactory, HasUuids; // Pastikan HasUuids terpanggil di sini

    protected $table = 'students';
    
    // Memberitahu Laravel bahwa primary key adalah string UUID
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'nis',
        'parent_name',
        'status',
    ];
}