<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VirtualAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'income_type_id',
        'bank_name',
        'va_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ================= RELATIONS ================= */

    public function incomeType()
    {
        return $this->belongsTo(IncomeType::class);
    }
}
