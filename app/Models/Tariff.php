<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
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
        'status',
        'rejection_note',
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

    public function getClassCategoryLabelAttribute(): string
    {
        return str_replace('_', ' ', (string) $this->class_category);
    }

    /* ================= UUID AUTO ================= */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });

        static::created(function (Tariff $model) {
            $model->approvalHistories()->create([
                'action' => 'submitted',
                'from_status' => null,
                'to_status' => $model->status,
                'note' => 'Tarif diajukan untuk persetujuan.',
                'acted_by' => $model->proposed_by ?? Auth::id(),
                'acted_at' => now(),
            ]);
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

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(TariffApprovalHistory::class)->orderByDesc('acted_at');
    }
}
