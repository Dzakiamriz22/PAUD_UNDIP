<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RoleHasScope extends Model
{
    protected $table = 'role_has_scopes';

    protected $fillable = [
        'model_role_id',
        'scope_type',
        'scope_id',
    ];

    // Relasi ke model_has_roles (custom Spatie's model)
    public function modelRole(): BelongsTo
    {
        return $this->belongsTo(ModelHasRole::class, 'model_role_id');
    }

    // Polymorphic scope
    public function scope(): MorphTo
    {
        return $this->morphTo('scope', 'scope_type', 'scope_id');
    }

    // Convenience: akses langsung ke Role (melalui model_has_roles)
    public function role()
    {
        return $this->modelRole?->role;
    }

    // Convenience: akses langsung ke User/Model pemilik role
    public function model()
    {
        return $this->modelRole?->model;
    }

    public static function getAvailableScopes(): array
    {
        return [
            \App\Models\Unit::class => 'Unit',
        ];
    }
}
