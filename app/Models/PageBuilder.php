<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageBuilder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'page_type',
        'name',
        'slug',
        'is_active',
        'meta_data',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_data' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get all sections for this page
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PageBuilderSection::class)->orderBy('order');
    }

    /**
     * Get only active and visible sections
     */
    public function activeSections(): HasMany
    {
        return $this->hasMany(PageBuilderSection::class)
            ->where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('order');
    }

    /**
     * Scope to get active pages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by page type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('page_type', $type);
    }

    /**
     * Get page by slug
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Get rendered page data
     */
    public function getRenderData(): array
    {
        return [
            'page' => [
                'type' => $this->page_type,
                'name' => $this->name,
                'slug' => $this->slug,
                'meta' => $this->meta_data ?? [],
                'settings' => $this->settings ?? [],
            ],
            'sections' => $this->activeSections->map(function ($section) {
                return $section->getRenderData();
            })->toArray(),
        ];
    }
}
