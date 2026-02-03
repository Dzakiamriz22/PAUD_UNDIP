<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Student extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'nis',
        'gender',
        'birth_date',
        'parent_name',
        'parent_contact',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
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

    public function invoiceItems()
    {
        return $this->hasManyThrough(InvoiceItem::class, Invoice::class);
    }
}
