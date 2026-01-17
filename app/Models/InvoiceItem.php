<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Tariff;

class InvoiceItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'tariff_id',
        'period_month',
        'period_year',
        'period_day',
        'final_amount',
        'description',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'period_day' => 'date',
        'final_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {
            if ($item->tariff_id && !$item->final_amount) {
                $tariff = Tariff::find($item->tariff_id);
                if ($tariff) {
                    $item->final_amount = $tariff->amount;
                }
            }
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