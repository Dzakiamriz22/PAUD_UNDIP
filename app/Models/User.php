<?php

namespace App\Models;

use Filament\AvatarProviders\UiAvatarsProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use TomatoPHP\FilamentMediaManager\Traits\InteractsWithMediaFolders;
use Spatie\Permission\Models\Role;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements FilamentUser, HasAvatar, HasName, HasMedia, MustVerifyEmail
{
    use InteractsWithMedia;
    use HasUuids, HasRoles, SoftDeletes;
    use HasApiTokens, HasFactory, Notifiable;
    use InteractsWithMediaFolders;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'firstname',
        'lastname',
        'password',
        'kode_unit',
        'telp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = [
        'name', // ini untuk kebutuhan plugin yang memakai default user laravel , $user->name
        'is_admin'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (User $user) {
            // dd($user);
        });

        static::updating(function (User $user) {
            // dd($user);
        });

        static::deleting(function (User $user) {
            //
        });
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // if ($panel->getId() === 'admin') {
        //     return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
        // }

        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        // if (empty($this->avatar_url))
        //    return (new UiAvatarsProvider())->get($this);

        return $this->getMedia('avatars')?->first()?->getUrl() ?? $this->getMedia('avatars')?->first()?->getUrl('thumb') ?? (new UiAvatarsProvider())->get($this);
    }

    public function getVerifiedStatusAttribute(): string
    {
        return empty($this->email_verified_at) ? 'Unverified' : 'Verified';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('filament-shield.super_admin.name'));
    }

    public function recipients(): HasOne
    {
        return $this->hasOne(BookingOrderRecipient::class, 'user_id');
    }

    public function getNameAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getIsAdminAttribute()
    {
        return $this->isSuperAdmin()
            || $this->hasRole('admin');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(Media|null $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function canBeImpersonated()
    {
        // Let's prevent impersonating other users at our own company
        return !$this->isSuperAdmin() && !$this->trashed();
    }

    public function canImpersonate()
    {
        return $this->isSuperAdmin()
            || $this->hasRole('admin');
    }

    public function hasOnlyRole(string|array $role): bool
    {
        $allRoles = $this->getRoleNames();

        $roleList = is_array($role) ? $role : [$role];

        return $allRoles->count() === count($roleList)
            && $allRoles->sort()->values()->all() === collect($roleList)->sort()->values()->all();
    }

    public function modelHasRoles(): MorphMany
    {
        return $this->morphMany(ModelHasRole::class, 'model');
    }

    public function getRoleScopeObjects(string $roleName, string $modelClass): \Illuminate\Support\Collection
    {
        $model_role_ids = $this->modelHasRoles()
            ->whereHas('role', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })->pluck('id');

        $scope_ids = \App\Models\RoleHasScope::whereHas('modelRole', function ($query) {
            $query->where('model_type', self::class)
                ->where('model_id', $this->id);
        })
            ->whereIn('model_role_id', $model_role_ids ?? ['-'])
            ->where('scope_type', $modelClass)
            ->pluck('scope_id');

        return $modelClass::whereIn('id', $scope_ids)->get();
    }

    /**
     * Get users who have a specific role on a specific unit/scope
     * 
     * @param string $roleName The role name (e.g., 'spv_unit')
     * @param string $scopeType The scope model class (e.g., Unit::class)
     * @param string|int $scopeId The scope ID (e.g., unit ID)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUsersByRoleAndScope(string $roleName, string $scopeType, $scopeId): \Illuminate\Database\Eloquent\Collection
    {
        // Get all ModelHasRole that have the specified role
        $modelRoleIds = ModelHasRole::whereHas('role', function ($query) use ($roleName) {
            $query->where('name', $roleName);
        })
            ->where('model_type', self::class)
            ->pluck('id');

        // Get RoleHasScope that match the scope type and ID
        $roleScopeIds = RoleHasScope::whereIn('model_role_id', $modelRoleIds)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->pluck('model_role_id');

        // Get the user IDs from ModelHasRole
        $userIds = ModelHasRole::whereIn('id', $roleScopeIds)
            ->where('model_type', self::class)
            ->pluck('model_id');

        // Return the users
        return self::whereIn('id', $userIds)->get();
    }

}
