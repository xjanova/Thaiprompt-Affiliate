<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBuilderSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'page_builder_id',
        'section_type',
        'name',
        'order',
        'settings',
        'content',
        'is_active',
        'is_visible',
    ];

    protected $casts = [
        'settings' => 'array',
        'content' => 'array',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
    ];

    /**
     * Available section types
     */
    public const SECTION_TYPES = [
        'hero' => 'Hero Section',
        'hero_gradient' => 'Hero with Gradient',
        'hero_video' => 'Hero with Video',
        'hero_premium' => 'Hero Premium (with cards)',
        'features' => 'Feature Grid',
        'features_list' => 'Feature List',
        'statistics' => 'Statistics Counter',
        'statistics_realtime' => 'Statistics (Realtime)',
        'testimonials' => 'Testimonials',
        'cta' => 'Call to Action',
        'content_block' => 'Content Block',
        'image_text' => 'Image + Text',
        'gallery' => 'Image Gallery',
        'pricing' => 'Pricing Table',
        'team' => 'Team Members',
        'faq' => 'FAQ Accordion',
        'contact_form' => 'Contact Form',
        'custom_html' => 'Custom HTML',
        'spacer' => 'Spacer',
    ];

    /**
     * Get the page builder that owns this section
     */
    public function pageBuilder(): BelongsTo
    {
        return $this->belongsTo(PageBuilder::class);
    }

    /**
     * Scope to get active sections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get visible sections
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope to order by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get render data for this section
     */
    public function getRenderData(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->section_type,
            'name' => $this->name,
            'settings' => $this->settings ?? [],
            'content' => $this->content ?? [],
        ];
    }

    /**
     * Get the view component name for this section
     */
    public function getComponentName(): string
    {
        // Convert snake_case to kebab-case for component names
        return 'page-builder.sections.' . str_replace('_', '-', $this->section_type);
    }
}
