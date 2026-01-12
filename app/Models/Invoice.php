<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'invoice_number',
        'status',
        'issued_at',
        'due_date',
        'total_amount',
        'created_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->id)) {
                $invoice->id = (string) Str::uuid();
            }

            $invoice->issued_at ??= now()->toDateString();
            $invoice->created_by ??= auth()->id();
        });
    }

    /* ================= RELATIONS ================= */

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}