<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageBuilderTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_type',
        'name',
        'slug',
        'description',
        'preview_image',
        'category',
        'default_settings',
        'default_content',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_settings' => 'array',
        'default_content' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Template categories
     */
    public const CATEGORIES = [
        'modern' => 'Modern',
        'classic' => 'Classic',
        'minimal' => 'Minimal',
        'gradient' => 'Gradient',
        'animated' => 'Animated',
        'business' => 'Business',
        'creative' => 'Creative',
    ];

    /**
     * Scope to get active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Scope to get by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get template data for section creation
     */
    public function getTemplateData(): array
    {
        return [
            'section_type' => $this->template_type,
            'settings' => $this->default_settings ?? [],
            'content' => $this->default_content ?? [],
        ];
    }
}
