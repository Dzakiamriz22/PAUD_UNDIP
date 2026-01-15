<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Tariff extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'income_type_id',
        'class_category',
        'amount',
        'billing_type',
        'is_active',
        'proposed_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function getBillingTypeLabelAttribute(): string
    {
        return match ($this->billing_type) {
            'once' => 'Sekali Bayar',
            'monthly' => 'Bulanan',
            'yearly' => 'Tahunan',
            'daily' => 'Harian',
            'penalty' => 'Denda',
            default => '-',
        };
    }

    /* ================= UUID AUTO ================= */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /* ================= RELATIONS ================= */

    public function incomeType()
    {
        return $this->belongsTo(IncomeType::class);
    }

    public function proposer()
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}