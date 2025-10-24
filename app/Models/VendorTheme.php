<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'store_theme_id',
        'primary_color',
        'secondary_color',
        'accent_color',
        'text_color',
        'background_color',
        'heading_font',
        'body_font',
        'base_font_size',
        'layout_style',
        'products_per_row',
        'show_sidebar',
        'header_style',
        'hero_banner',
        'hero_title',
        'hero_subtitle',
        'hero_button_text',
        'hero_button_link',
        'promotional_banners',
        'custom_css',
        'custom_js',
        'favicon',
        'logo',
        'logo_mobile',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'line_url',
        'youtube_url',
        'show_contact_widget',
        'contact_widget_position',
        'footer_about',
        'footer_links',
        'is_active',
        'activated_at',
    ];

    protected $casts = [
        'base_font_size' => 'integer',
        'products_per_row' => 'integer',
        'show_sidebar' => 'boolean',
        'promotional_banners' => 'array',
        'footer_links' => 'array',
        'show_contact_widget' => 'boolean',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
    ];

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the store theme template
     */
    public function storeTheme(): BelongsTo
    {
        return $this->belongsTo(StoreTheme::class);
    }

    /**
     * Get compiled color scheme
     */
    public function getColorSchemeAttribute(): array
    {
        return [
            'primary' => $this->primary_color,
            'secondary' => $this->secondary_color,
            'accent' => $this->accent_color,
            'text' => $this->text_color,
            'background' => $this->background_color,
        ];
    }

    /**
     * Get compiled typography settings
     */
    public function getTypographyAttribute(): array
    {
        return [
            'heading' => $this->heading_font,
            'body' => $this->body_font,
            'size' => $this->base_font_size,
        ];
    }

    /**
     * Activate theme
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    /**
     * Deactivate theme
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Reset to default theme settings
     */
    public function resetToDefaults(): void
    {
        if ($this->storeTheme) {
            $defaults = $this->storeTheme->color_scheme;
            $this->update([
                'primary_color' => $defaults['primary'] ?? '#3490dc',
                'secondary_color' => $defaults['secondary'] ?? '#ffed4e',
                'accent_color' => $defaults['accent'] ?? '#38c172',
            ]);
        }
    }

    /**
     * Scope: Active themes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
