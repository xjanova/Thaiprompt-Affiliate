<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_location',
        'title',
        'url',
        'route',
        'target',
        'icon',
        'order',
        'parent_id',
        'is_active',
        'conditions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];

    /**
     * Get the parent menu item.
     */
    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get the child menu items.
     */
    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->ordered()->active();
    }

    /**
     * Scope for specific menu location.
     */
    public function scopeForLocation($query, $location)
    {
        return $query->where('menu_location', $location);
    }

    /**
     * Scope for active menu items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered menu items.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope for root menu items (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get menu items for a specific location.
     */
    public static function getForLocation($location = 'header')
    {
        return static::forLocation($location)->root()->active()->ordered()->with('children')->get();
    }

    /**
     * Get the URL for this menu item.
     */
    public function getUrlAttribute($value)
    {
        // If route is set, use it
        if (!empty($this->attributes['route'])) {
            try {
                return route($this->attributes['route']);
            } catch (\Exception $e) {
                // Route doesn't exist, fall back to URL
            }
        }

        // Otherwise use the URL value
        return $value;
    }

    /**
     * Check if this menu item should be displayed based on conditions.
     */
    public function shouldDisplay()
    {
        if (empty($this->conditions)) {
            return true;
        }

        $conditions = $this->conditions;

        // Check login requirement
        if (isset($conditions['logged_in'])) {
            if ($conditions['logged_in'] && !auth()->check()) {
                return false;
            }
            if (!$conditions['logged_in'] && auth()->check()) {
                return false;
            }
        }

        // Check role requirement
        if (isset($conditions['roles']) && auth()->check()) {
            $userRole = auth()->user()->role;
            if (!in_array($userRole, $conditions['roles'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reorder menu items for a location.
     */
    public static function reorderForLocation($location, $items)
    {
        foreach ($items as $index => $item) {
            static::where('id', $item['id'])
                ->where('menu_location', $location)
                ->update(['order' => $index]);

            // Reorder children if present
            if (isset($item['children'])) {
                foreach ($item['children'] as $childIndex => $child) {
                    static::where('id', $child['id'])->update(['order' => $childIndex]);
                }
            }
        }
    }
}
