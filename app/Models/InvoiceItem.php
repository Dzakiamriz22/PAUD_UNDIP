<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'invoice_id',
        'income_type_id',
        'amount',
        'description',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function incomeType()
    {
        return $this->belongsTo(IncomeType::class);
    }
}