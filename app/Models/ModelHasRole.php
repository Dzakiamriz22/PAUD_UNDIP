<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Role;

class ModelHasRole extends Model
{
    protected $table = 'model_has_roles';

    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'model_type',
        'model_id',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    public function roleScopes(): HasMany
    {
        return $this->hasMany(RoleHasScope::class, 'model_role_id');
    }
}
