<?php

namespace App\Models\Approvals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Role;

class Approval extends Model
{
    protected $table = 'approvals';
    
    protected $fillable = [
        'sort',
        'approvable_type',
        'approval_name',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate sort from title
        static::creating(function (Approval $approval) {
            $approval->sort = static::max('sort') + 1;
        });

        static::deleted(function (Approval $approval) {
            $record_list = static::getModel()::all()->sortBy('sort');

            foreach ($record_list as $i => $record) {
                $record->sort = $i + 1;
                $record->save();
            }
        });

        static::updating(function (Approval $approval) {
            // dd($approval);
        });
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'approval_has_role', 'approval_id', 'role_id');
    }
}
