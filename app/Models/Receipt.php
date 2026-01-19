<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\ReceiptNumberGenerator;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'invoice_id',
        'amount_paid',
        'payment_method',
        'reference_number',
        'payment_date',
        'issued_at',
        'created_by',
        'note',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_date' => 'datetime',
        'issued_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($receipt) {
            if (! $receipt->receipt_number) {
                $receipt->receipt_number = ReceiptNumberGenerator::generate();
            }

            $receipt->issued_at ??= now();
            $receipt->payment_date ??= now();
            $receipt->created_by ??= auth()->id();
        });
    }

    /* RELATIONS */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ACCESSORS */

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Tunai',
            'bank_transfer' => 'Transfer Bank',
            'va' => 'Virtual Account',
            'qris' => 'QRIS',
            'other' => 'Lainnya',
            default => $this->payment_method,
        };
    }
}
