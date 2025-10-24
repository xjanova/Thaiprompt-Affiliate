<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Theme\ThemeService;
use App\Models\ThemeCustomization;
use App\Models\PremiumFeature;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    protected ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Show theme customization page
     */
    public function index()
    {
        $vendorId = auth()->user()->vendor_id;
        $theme = $this->themeService->getVendorTheme($vendorId);
        $hasPremium = $this->themeService->checkPremiumAccess($vendorId);
        $colorPresets = $this->themeService->getColorPresets();
        $gradientPresets = $this->themeService->getGradientPresets();
        $premiumFeature = PremiumFeature::where('feature_key', 'theme_customization')->first();

        return view('admin.theme.index', compact(
            'theme',
            'hasPremium',
            'colorPresets',
            'gradientPresets',
            'premiumFeature'
        ));
    }

    /**
     * Update theme
     */
    public function update(Request $request)
    {
        $request->validate([
            'primary_color' => 'required|string',
            'secondary_color' => 'required|string',
            'accent_color' => 'required|string',
            'font_family' => 'required|string',
            'gradient_colors' => 'nullable|array',
        ]);

        $vendorId = auth()->user()->vendor_id;

        try {
            $theme = $this->themeService->saveTheme($vendorId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Theme updated successfully',
                'theme' => $theme,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|max:2048',
        ]);

        $vendorId = auth()->user()->vendor_id;

        if (!$this->themeService->checkPremiumAccess($vendorId)) {
            return response()->json([
                'success' => false,
                'message' => 'Premium subscription required',
            ], 403);
        }

        $url = $this->themeService->uploadLogo($vendorId, $request->file('logo'));

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    /**
     * Upload favicon
     */
    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|image|max:512',
        ]);

        $vendorId = auth()->user()->vendor_id;

        if (!$this->themeService->checkPremiumAccess($vendorId)) {
            return response()->json([
                'success' => false,
                'message' => 'Premium subscription required',
            ], 403);
        }

        $url = $this->themeService->uploadFavicon($vendorId, $request->file('favicon'));

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    /**
     * Subscribe to premium
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
        ]);

        $vendorId = auth()->user()->vendor_id;

        try {
            $subscription = $this->themeService->subscribeToPremium(
                $vendorId,
                $request->billing_cycle
            );

            return response()->json([
                'success' => true,
                'message' => 'Premium subscription activated',
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview theme
     */
    public function preview(Request $request)
    {
        $vendorId = auth()->user()->vendor_id;
        $theme = $this->themeService->getVendorTheme($vendorId);

        if (!$theme) {
            $theme = new ThemeCustomization($request->all());
        } else {
            $theme->fill($request->all());
        }

        $css = $this->themeService->generateThemeCSS($theme);

        return response()->json([
            'success' => true,
            'css' => $css,
        ]);
    }
}
