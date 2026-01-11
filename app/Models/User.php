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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements FilamentUser, HasAvatar, HasName, HasMedia, MustVerifyEmail
{
    use InteractsWithMedia;
    use HasUuids, HasRoles, SoftDeletes;
    use HasApiTokens, HasFactory, Notifiable;
    use InteractsWithMediaFolders;

    /* ===================== BASIC CONFIG ===================== */

    protected $fillable = [
        'username',
        'email',
        'firstname',
        'lastname',
        'password',
        'kode_unit',
        'telp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'name',
        'is_admin',
    ];

    /* ===================== FILAMENT ===================== */

    public function getFilamentName(): string
    {
        return $this->username;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getMedia('avatars')?->first()?->getUrl('thumb')
            ?? (new UiAvatarsProvider())->get($this);
    }

    /* ===================== ATTRIBUTES ===================== */

    public function getNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->isSuperAdmin() || $this->hasRole('admin');
    }

    /* ===================== ROLE HELPERS ===================== */

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('filament-shield.super_admin.name'));
    }

    public function isGuru(): bool
    {
        return $this->hasRole('guru');
    }

    public function isKepsek(): bool
    {
        return $this->hasRole('kepala_sekolah');
    }

    public function isBendahara(): bool
    {
        return $this->hasRole('bendahara');
    }

    public function canHaveRole(string $role): bool
    {
        if ($role === 'bendahara' && $this->hasRole('kepala_sekolah')) {
            return false;
        }

        if ($role === 'kepala_sekolah' && $this->hasRole('bendahara')) {
            return false;
        }

        return true;
    }

    /* ===================== RELATIONS ===================== */

    public function recipients(): HasOne
    {
        return $this->hasOne(BookingOrderRecipient::class, 'user_id');
    }

    /**
     * Kelas yang diampu sebagai wali kelas
     */
    public function homeroomClasses()
    {
        return $this->hasMany(
            \App\Models\SchoolClass::class,
            'homeroom_teacher_id'
        );
    }



    /* ===================== AUTHORIZATION HELPERS ===================== */

    /**
     * Digunakan untuk filter query (guru hanya lihat kelasnya)
     */
    public function visibleSchoolClassIds(): array
    {
        if ($this->isSuperAdmin() || $this->hasRole('admin') || $this->isKepsek()) {
            return SchoolClass::pluck('id')->toArray();
        }

        if ($this->isGuru()) {
            return $this->homeroomClasses()->pluck('id')->toArray();
        }

        return [];
    }

    /* ===================== MEDIA ===================== */

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
}
