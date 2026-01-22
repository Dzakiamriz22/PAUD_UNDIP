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

    public function incomeTypes()
    {
        return $this->belongsToMany(IncomeType::class, 'income_type_virtual_account', 'virtual_account_id', 'income_type_id')->withTimestamps();
    }
}
