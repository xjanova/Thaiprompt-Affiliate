<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeCustomization extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'theme_name',
        'primary_color',
        'secondary_color',
        'accent_color',
        'gradient_colors',
        'font_family',
        'logo_url',
        'favicon_url',
        'banner_url',
        'custom_css',
        'is_premium',
        'premium_expires_at',
        'is_active',
    ];

    protected $casts = [
        'gradient_colors' => 'array',
        'custom_css' => 'array',
        'is_premium' => 'boolean',
        'premium_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the vendor that owns the theme
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Check if premium is active
     */
    public function isPremiumActive(): bool
    {
        if (!$this->is_premium) {
            return false;
        }

        if ($this->premium_expires_at === null) {
            return true; // Lifetime premium
        }

        return $this->premium_expires_at->isFuture();
    }

    /**
     * Get CSS variables for theme
     */
    public function getCssVariables(): array
    {
        return [
            '--color-primary' => $this->primary_color,
            '--color-secondary' => $this->secondary_color,
            '--color-accent' => $this->accent_color,
            '--font-family' => $this->font_family,
        ];
    }

    /**
     * Get gradient CSS
     */
    public function getGradientCss(): ?string
    {
        if (!$this->gradient_colors || count($this->gradient_colors) < 2) {
            return null;
        }

        $colors = implode(', ', $this->gradient_colors);
        return "linear-gradient(135deg, {$colors})";
    }
}
