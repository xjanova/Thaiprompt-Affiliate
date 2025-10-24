<?php

namespace App\Services\Theme;

use App\Models\ThemeCustomization;
use App\Models\Vendor;
use App\Models\VendorPremiumSubscription;
use App\Models\PremiumFeature;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Exception;

class ThemeService
{
    /**
     * Get theme for vendor
     */
    public function getVendorTheme(int $vendorId): ?ThemeCustomization
    {
        return ThemeCustomization::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Create or update theme
     */
    public function saveTheme(int $vendorId, array $data): ThemeCustomization
    {
        // Check if vendor has premium subscription
        $hasPremium = $this->checkPremiumAccess($vendorId);

        if (!$hasPremium && isset($data['is_premium']) && $data['is_premium']) {
            throw new Exception('Premium features require an active subscription');
        }

        $theme = ThemeCustomization::updateOrCreate(
            ['vendor_id' => $vendorId],
            [
                'theme_name' => $data['theme_name'] ?? 'custom',
                'primary_color' => $data['primary_color'] ?? '#4F46E5',
                'secondary_color' => $data['secondary_color'] ?? '#EC4899',
                'accent_color' => $data['accent_color'] ?? '#F59E0B',
                'gradient_colors' => $data['gradient_colors'] ?? null,
                'font_family' => $data['font_family'] ?? 'Inter',
                'custom_css' => $data['custom_css'] ?? null,
                'is_premium' => $hasPremium,
                'is_active' => true,
            ]
        );

        return $theme;
    }

    /**
     * Upload logo
     */
    public function uploadLogo(int $vendorId, UploadedFile $file): string
    {
        $path = $file->store('themes/logos', 'public');

        $theme = ThemeCustomization::where('vendor_id', $vendorId)->first();
        if ($theme) {
            // Delete old logo
            if ($theme->logo_url) {
                Storage::disk('public')->delete($theme->logo_url);
            }

            $theme->update(['logo_url' => $path]);
        }

        return Storage::url($path);
    }

    /**
     * Upload favicon
     */
    public function uploadFavicon(int $vendorId, UploadedFile $file): string
    {
        $path = $file->store('themes/favicons', 'public');

        $theme = ThemeCustomization::where('vendor_id', $vendorId)->first();
        if ($theme) {
            // Delete old favicon
            if ($theme->favicon_url) {
                Storage::disk('public')->delete($theme->favicon_url);
            }

            $theme->update(['favicon_url' => $path]);
        }

        return Storage::url($path);
    }

    /**
     * Upload banner
     */
    public function uploadBanner(int $vendorId, UploadedFile $file): string
    {
        $path = $file->store('themes/banners', 'public');

        $theme = ThemeCustomization::where('vendor_id', $vendorId)->first();
        if ($theme) {
            // Delete old banner
            if ($theme->banner_url) {
                Storage::disk('public')->delete($theme->banner_url);
            }

            $theme->update(['banner_url' => $path]);
        }

        return Storage::url($path);
    }

    /**
     * Check premium access for vendor
     */
    public function checkPremiumAccess(int $vendorId): bool
    {
        $feature = PremiumFeature::where('feature_key', 'theme_customization')->first();

        if (!$feature) {
            return false;
        }

        $subscription = VendorPremiumSubscription::where('vendor_id', $vendorId)
            ->where('premium_feature_id', $feature->id)
            ->where('status', 'active')
            ->first();

        return $subscription && $subscription->isActive();
    }

    /**
     * Subscribe to premium theme
     */
    public function subscribeToPremium(int $vendorId, string $billingCycle = 'monthly'): VendorPremiumSubscription
    {
        $feature = PremiumFeature::where('feature_key', 'theme_customization')->first();

        if (!$feature) {
            throw new Exception('Premium feature not found');
        }

        // Calculate expiry date
        $expiresAt = match ($billingCycle) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            'lifetime' => null,
            default => now()->addMonth(),
        };

        // Create subscription
        $subscription = VendorPremiumSubscription::create([
            'vendor_id' => $vendorId,
            'premium_feature_id' => $feature->id,
            'started_at' => now(),
            'expires_at' => $expiresAt,
            'status' => 'active',
            'amount_paid' => $feature->price,
        ]);

        // Update theme to premium
        $theme = ThemeCustomization::where('vendor_id', $vendorId)->first();
        if ($theme) {
            $theme->update([
                'is_premium' => true,
                'premium_expires_at' => $expiresAt,
            ]);
        }

        return $subscription;
    }

    /**
     * Generate CSS from theme
     */
    public function generateThemeCSS(ThemeCustomization $theme): string
    {
        $css = ":root {\n";

        foreach ($theme->getCssVariables() as $key => $value) {
            $css .= "  {$key}: {$value};\n";
        }

        if ($gradient = $theme->getGradientCss()) {
            $css .= "  --gradient-primary: {$gradient};\n";
        }

        $css .= "}\n";

        // Add custom CSS if exists
        if ($theme->custom_css) {
            foreach ($theme->custom_css as $selector => $rules) {
                $css .= "\n{$selector} {\n";
                foreach ($rules as $property => $value) {
                    $css .= "  {$property}: {$value};\n";
                }
                $css .= "}\n";
            }
        }

        return $css;
    }

    /**
     * Get available color presets
     */
    public function getColorPresets(): array
    {
        return [
            'default' => [
                'name' => 'Default',
                'primary' => '#4F46E5',
                'secondary' => '#EC4899',
                'accent' => '#F59E0B',
            ],
            'ocean' => [
                'name' => 'Ocean',
                'primary' => '#0EA5E9',
                'secondary' => '#06B6D4',
                'accent' => '#14B8A6',
            ],
            'sunset' => [
                'name' => 'Sunset',
                'primary' => '#F97316',
                'secondary' => '#EF4444',
                'accent' => '#EC4899',
            ],
            'forest' => [
                'name' => 'Forest',
                'primary' => '#10B981',
                'secondary' => '#059669',
                'accent' => '#84CC16',
            ],
            'royal' => [
                'name' => 'Royal',
                'primary' => '#7C3AED',
                'secondary' => '#A855F7',
                'accent' => '#EC4899',
            ],
        ];
    }

    /**
     * Get available gradient presets
     */
    public function getGradientPresets(): array
    {
        return [
            'sunset' => ['#FF6B6B', '#FFE66D', '#4ECDC4'],
            'ocean' => ['#2E3192', '#1BFFFF', '#00F5FF'],
            'forest' => ['#134E5E', '#71B280', '#C7E9C0'],
            'purple_haze' => ['#360033', '#0B8793', '#00D4FF'],
            'fire' => ['#FF512F', '#DD2476', '#F7971E'],
            'candy' => ['#FF00CC', '#333399', '#00CCFF'],
        ];
    }
}
