<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Receipt extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'payment_id',
        'receipt_number',
        'issued_at',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}