<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InvoiceItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'tariff_id',
        'original_amount',
        'discount_amount',
        'final_amount',
        'description',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            $item->final_amount =
                $item->original_amount - $item->discount_amount;
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tariff()
    {
        return $this->belongsTo(Tariff::class);
    }
}