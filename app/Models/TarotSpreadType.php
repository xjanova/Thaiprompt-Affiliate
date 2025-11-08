<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarotSpreadType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_th',
        'description_en',
        'description_th',
        'card_count',
        'positions',
        'image_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'positions' => 'array',
        'is_active' => 'boolean',
        'card_count' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get readings using this spread type
     */
    public function readings()
    {
        return $this->hasMany(TarotReading::class, 'spread_type_id');
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
     * Get position name by index
     */
    public function getPositionName(int $position, string $language = 'th'): string
    {
        $positions = $this->positions ?? [];

        if (isset($positions[$position - 1])) {
            $positionData = $positions[$position - 1];
            $nameField = "name_{$language}";
            return $positionData[$nameField] ?? $positionData['name_en'] ?? "Position {$position}";
        }

        return "Position {$position}";
    }

    /**
     * Scope for active spread types
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
        return $query->orderBy('sort_order')->orderBy('card_count');
    }

    /**
     * Get image URL with fallback
     */
    public function getImageUrlAttribute($value)
    {
        if ($value && file_exists(public_path($value))) {
            return asset($value);
        }

        return null;
    }
}
