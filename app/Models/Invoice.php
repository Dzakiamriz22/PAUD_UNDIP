<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
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
        'issued_at' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'paid_at' => 'datetime',
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

    public function recalculateTotal(): void
    {
        // Ambil semua items dengan relasi tariff dan incomeType
        $items = $this->items()->with('tariff.incomeType')->get();
        
        $subTotal = 0;
        $totalDiscount = 0;
        
        foreach ($items as $item) {
            $amount = (float) $item->final_amount;
            
            // Cek apakah incomeType memiliki is_discount = true
            if ($item->tariff && $item->tariff->incomeType && $item->tariff->incomeType->is_discount) {
                // Jika discount, kurangi dari total
                $totalDiscount += $amount;
            } else {
                // Jika bukan discount, tambahkan ke subtotal
                $subTotal += $amount;
            }
        }
        
        // Discount dari field discount_amount (jika ada)
        $manualDiscount = $this->discount_amount ?? 0;
        $totalDiscount += $manualDiscount;
        
        // Total = subtotal - total discount
        $totalAmount = $subTotal - $totalDiscount;
        
        // Pastikan total tidak negatif
        if ($totalAmount < 0) {
            $totalAmount = 0;
        }

        $this->updateQuietly([
            'sub_total' => $subTotal,
            'discount_amount' => $totalDiscount,
            'total_amount' => $totalAmount,
        ]);
    }
}