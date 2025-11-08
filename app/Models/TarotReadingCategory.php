<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TarotReadingCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_th',
        'slug',
        'description_en',
        'description_th',
        'icon',
        'color',
        'price',
        'is_free_first',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_free_first' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get readings in this category
     */
    public function readings()
    {
        return $this->hasMany(TarotReading::class, 'category_id');
    }

    /**
     * Get user limits for this category
     */
    public function userLimits()
    {
        return $this->hasMany(TarotUserLimit::class, 'category_id');
    }

    /**
     * Get name in specified language
     */
    public function getName(string $language = 'th'): string
    {
        $nameField = "name_{$language}";
        return $this->$nameField ?? $this->name_en;
    }

    /**
     * Get description in specified language
     */
    public function getDescription(string $language = 'th'): string
    {
        $descField = "description_{$language}";
        return $this->$descField ?? $this->description_en ?? '';
    }

    /**
     * Check if category is free
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    /**
     * Check if user has used free reading today
     */
    public function hasUsedFreeReadingToday($userId = null, $sessionId = null): bool
    {
        if (!$this->is_free_first) {
            return true; // No free reading available
        }

        $query = TarotUserLimit::where('category_id', $this->id)
            ->where('limit_date', today())
            ->where('free_count', '>', 0);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return false; // Can't check without user or session
        }

        return $query->exists();
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_th');
    }

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name_en);
            }
        });

        static::updating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name_en);
            }
        });
    }
}
