<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use App\Models\SchoolClass;
use App\Services\InvoiceNumberGenerator;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'income_type_id',
        'invoice_number',
        'status',
        'issued_at',
        'due_date',
        'total_amount',
        'va_number',
        'va_bank',
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

            if (! $invoice->invoice_number) {
                $invoice->invoice_number = InvoiceNumberGenerator::generate();
            }

            $invoice->status = 'unpaid';
            $invoice->issued_at ??= now();
            $invoice->created_by ??= auth()->id();
            $invoice->total_amount ??= 0;
        });
    }

    /* RELATIONS */

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function incomeType()
    {
        return $this->belongsTo(IncomeType::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function recalculateTotal(): void
    {
        $this->updateQuietly([
            'total_amount' => $this->items()->sum('final_amount'),
        ]);
    }
}