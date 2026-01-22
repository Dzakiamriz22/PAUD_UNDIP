<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Services\InvoiceNumberGenerator;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'invoice_number',
        'status',
        'issued_at',
        'paid_at',
        'due_date',
        'total_amount',
        'discount_amount',
        'sub_total',
        'va_number',
        'va_bank',
        'created_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'sub_total' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->invoice_number ??= InvoiceNumberGenerator::generate();
            $invoice->status = 'unpaid';
            $invoice->issued_at ??= now();
            $invoice->created_by ??= auth()->id();
            $invoice->total_amount ??= 0;
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

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ================= LOGIC ================= */

    public function recalculateTotal(): void
    {
        $items = $this->items()->with('tariff.incomeType')->get();

        $subTotal = 0;
        $totalDiscount = 0;

        foreach ($items as $item) {
            $amount = (float) $item->final_amount;

            if ($item->tariff?->incomeType?->is_discount) {
                $totalDiscount += $amount;
            } else {
                $subTotal += $amount;
            }
        }

        $totalDiscount += $this->discount_amount ?? 0;

        $totalAmount = max(0, $subTotal - $totalDiscount);

        $this->updateQuietly([
            'sub_total' => $subTotal,
            'discount_amount' => $totalDiscount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Override status accessor to show `expired` when unpaid and past due date.
     * This does not change the stored value in the database, only the presented value.
     */
    public function getStatusAttribute($value)
    {
        // If already a non-unpaid status, return it as-is
        if (!empty($value) && $value !== 'unpaid') {
            return $value;
        }

        // If due_date exists and is past, show expired for unpaid invoices
        if ($this->due_date && $this->due_date->isPast()) {
            return 'expired';
        }

        return $value;
    }
}