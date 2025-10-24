<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\AddonPurchase;
use App\Models\Vendor;
use App\Models\VendorTheme;
use App\Models\StoreTheme;

class AddonService
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Purchase an addon for a vendor
     */
    public function purchaseAddon(
        Vendor $vendor,
        Addon $addon,
        string $paymentMethod = 'wallet',
        ?string $transactionId = null
    ): AddonPurchase {
        // Check if vendor already has this addon
        $existingPurchase = AddonPurchase::where('vendor_id', $vendor->id)
            ->where('addon_id', $addon->id)
            ->valid()
            ->first();

        if ($existingPurchase) {
            throw new \Exception('Vendor already has this addon');
        }

        if (!$addon->is_active) {
            throw new \Exception('This addon is not available for purchase');
        }

        // Create purchase record
        $purchase = AddonPurchase::create([
            'vendor_id' => $vendor->id,
            'addon_id' => $addon->id,
            'purchase_price' => $addon->price,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'transaction_id' => $transactionId,
        ]);

        // Process payment
        if ($paymentMethod === 'wallet') {
            $this->processWalletPayment($vendor, $purchase);
        }

        return $purchase;
    }

    /**
     * Process wallet payment for addon purchase
     */
    protected function processWalletPayment(Vendor $vendor, AddonPurchase $purchase): void
    {
        $wallet = $vendor->user->wallet;

        if (!$wallet || $wallet->balance < $purchase->purchase_price) {
            $purchase->update(['payment_status' => 'failed']);
            throw new \Exception('Insufficient wallet balance');
        }

        try {
            // Debit wallet
            $this->walletService->debit(
                $wallet,
                $purchase->purchase_price,
                'addon_purchase',
                "Purchase of {$purchase->addon->name} addon"
            );

            // Update purchase status
            $purchase->update([
                'payment_status' => 'completed',
                'is_active' => true,
                'activated_at' => now(),
            ]);

            // Activate addon features
            $this->activateAddonFeatures($vendor, $purchase);

        } catch (\Exception $e) {
            $purchase->update(['payment_status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Activate addon features for vendor
     */
    protected function activateAddonFeatures(Vendor $vendor, AddonPurchase $purchase): void
    {
        $addon = $purchase->addon;

        // If it's a store theme addon, create default vendor theme
        if ($addon->type === 'store_theme') {
            $this->createDefaultVendorTheme($vendor);
        }

        // Additional activation logic for other addon types can be added here
    }

    /**
     * Create default vendor theme
     */
    public function createDefaultVendorTheme(Vendor $vendor): VendorTheme
    {
        // Check if vendor already has a theme
        $existingTheme = $vendor->vendorTheme;
        if ($existingTheme) {
            return $existingTheme;
        }

        // Create with default settings
        return VendorTheme::create([
            'vendor_id' => $vendor->id,
            'store_theme_id' => null,
            'primary_color' => '#3490dc',
            'secondary_color' => '#ffed4e',
            'accent_color' => '#38c172',
            'text_color' => '#212529',
            'background_color' => '#ffffff',
            'heading_font' => 'Poppins',
            'body_font' => 'Inter',
            'base_font_size' => 16,
            'layout_style' => 'grid',
            'products_per_row' => 4,
            'show_sidebar' => true,
            'header_style' => 'default',
            'show_contact_widget' => true,
            'contact_widget_position' => 'bottom-right',
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    /**
     * Apply store theme template to vendor
     */
    public function applyStoreTheme(Vendor $vendor, StoreTheme $theme): VendorTheme
    {
        // Check if vendor has store theme addon
        if (!$vendor->hasStoreTheme()) {
            throw new \Exception('Vendor does not have store theme addon');
        }

        // Check if theme requires purchase
        if ($theme->price > 0) {
            // Check if vendor has purchased this specific theme
            // For now, we'll allow free application if they have the addon
        }

        $vendorTheme = $vendor->vendorTheme ?? $this->createDefaultVendorTheme($vendor);

        // Apply theme settings
        $colorScheme = $theme->color_scheme ?? [];
        $typography = $theme->typography ?? [];
        $layoutOptions = $theme->layout_options ?? [];

        $vendorTheme->update([
            'store_theme_id' => $theme->id,
            'primary_color' => $colorScheme['primary'] ?? $vendorTheme->primary_color,
            'secondary_color' => $colorScheme['secondary'] ?? $vendorTheme->secondary_color,
            'accent_color' => $colorScheme['accent'] ?? $vendorTheme->accent_color,
            'heading_font' => $typography['heading'] ?? $vendorTheme->heading_font,
            'body_font' => $typography['body'] ?? $vendorTheme->body_font,
            'layout_style' => $layoutOptions['style'] ?? $vendorTheme->layout_style,
        ]);

        // Increment theme popularity
        $theme->incrementPopularity();

        return $vendorTheme->fresh();
    }

    /**
     * Customize vendor theme
     */
    public function customizeVendorTheme(Vendor $vendor, array $customizations): VendorTheme
    {
        // Check if vendor has store theme addon
        if (!$vendor->hasStoreTheme()) {
            throw new \Exception('Vendor does not have store theme addon');
        }

        $vendorTheme = $vendor->vendorTheme ?? $this->createDefaultVendorTheme($vendor);

        $vendorTheme->update($customizations);

        return $vendorTheme->fresh();
    }

    /**
     * Deactivate addon
     */
    public function deactivateAddon(AddonPurchase $purchase): void
    {
        $purchase->deactivate();

        // Additional cleanup logic can be added here
        // For example, deactivating related features
    }

    /**
     * Get available addons for vendor
     */
    public function getAvailableAddons(Vendor $vendor): array
    {
        $allAddons = Addon::active()->get();
        $purchasedAddonIds = $vendor->addonPurchases()
            ->valid()
            ->pluck('addon_id')
            ->toArray();

        return [
            'available' => $allAddons->whereNotIn('id', $purchasedAddonIds)->values(),
            'purchased' => $allAddons->whereIn('id', $purchasedAddonIds)->values(),
        ];
    }

    /**
     * Get addon statistics
     */
    public function getAddonStats(Addon $addon): array
    {
        $totalPurchases = $addon->purchases()->count();
        $activePurchases = $addon->activePurchases()->count();
        $totalRevenue = $addon->purchases()
            ->where('payment_status', 'completed')
            ->sum('purchase_price');

        $recentPurchases = $addon->purchases()
            ->where('payment_status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total_purchases' => $totalPurchases,
            'active_purchases' => $activePurchases,
            'total_revenue' => $totalRevenue,
            'recent_purchases_30d' => $recentPurchases,
            'conversion_rate' => 0, // Would need view tracking to calculate
        ];
    }
}
