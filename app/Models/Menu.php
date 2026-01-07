<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperMenu
 */
class Menu extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'menus';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the navigations for this menu (only root navigations).
     */
    public function navigations(): HasMany
    {
        return $this->hasMany(Navigation::class, 'menu_id')
            ->where('parent_id', -1)
            ->orderBy('order');
    }

    /**
     * Get all navigations (including children) for this menu.
     */
    public function allNavigations(): HasMany
    {
        return $this->hasMany(Navigation::class, 'menu_id')
            ->orderBy('order');
    }

    /**
     * Get navigations that are directly related to this menu OR have a parent that belongs to this menu.
     * This includes:
     * - Navigations where menu_id = this menu.id (direct relationship)
     * - Navigations where parent.menu_id = this menu.id (indirect relationship through parent)
     * 
     * Usage: $menu->navigationsWithParentRelationship()->get()
     */
    public function navigationsWithParentRelationship()
    {
        return Navigation::query()
            ->where(function ($query) {
                $query->where('menu_id', $this->id)
                    ->orWhereHas('parent', function ($q) {
                        $q->where('menu_id', $this->id);
                    });
            })
            ->orderBy('order');
    }

    /**
     * Get navigations that belong to this menu directly (menu_id = this menu.id).
     * This is the same as allNavigations() but more explicit.
     */
    public function directNavigations(): HasMany
    {
        return $this->hasMany(Navigation::class, 'menu_id')
            ->orderBy('order');
    }

    /**
     * Get navigations where the parent belongs to this menu.
     * These are navigations that have a parent navigation belonging to this menu.
     * Note: This will only return navigations where parent exists and parent.menu_id = this menu.id
     * 
     * Usage: $menu->navigationsByParent()->get()
     */
    public function navigationsByParent()
    {
        return Navigation::query()
            ->whereHas('parent', function ($query) {
                $query->where('menu_id', $this->id);
            })
            ->orderBy('order');
    }
}
