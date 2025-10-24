<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreTheme;
use App\Services\AddonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    protected $addonService;

    public function __construct(AddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Get all available themes
     */
    public function index()
    {
        $themes = StoreTheme::active()->orderByDesc('popularity_score')->get();
        return response()->json($themes);
    }

    /**
     * Get featured themes
     */
    public function featured()
    {
        $themes = StoreTheme::active()->featured()->get();
        return response()->json($themes);
    }

    /**
     * Get free themes
     */
    public function free()
    {
        $themes = StoreTheme::active()->free()->get();
        return response()->json($themes);
    }

    /**
     * Get premium themes
     */
    public function premium()
    {
        $themes = StoreTheme::active()->premium()->get();
        return response()->json($themes);
    }

    /**
     * Get theme by category
     */
    public function byCategory($category)
    {
        $themes = StoreTheme::active()->ofCategory($category)->get();
        return response()->json($themes);
    }

    /**
     * Get single theme
     */
    public function show($id)
    {
        $theme = StoreTheme::findOrFail($id);
        return response()->json($theme);
    }

    /**
     * Get vendor's current theme
     */
    public function myTheme()
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $theme = $vendor->vendorTheme()->with('storeTheme')->first();

        if (!$theme) {
            return response()->json(['message' => 'No theme configured'], 404);
        }

        return response()->json($theme);
    }

    /**
     * Apply a theme template to vendor's store
     */
    public function apply(Request $request)
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $validated = $request->validate([
            'theme_id' => 'required|exists:store_themes,id',
        ]);

        try {
            $theme = StoreTheme::findOrFail($validated['theme_id']);
            $vendorTheme = $this->addonService->applyStoreTheme($vendor, $theme);

            return response()->json([
                'message' => 'Theme applied successfully',
                'theme' => $vendorTheme,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Customize vendor theme
     */
    public function customize(Request $request)
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $validated = $request->validate([
            'primary_color' => 'nullable|string',
            'secondary_color' => 'nullable|string',
            'accent_color' => 'nullable|string',
            'text_color' => 'nullable|string',
            'background_color' => 'nullable|string',
            'heading_font' => 'nullable|string',
            'body_font' => 'nullable|string',
            'base_font_size' => 'nullable|integer|min:12|max:24',
            'layout_style' => 'nullable|in:grid,list,masonry',
            'products_per_row' => 'nullable|integer|min:2|max:6',
            'show_sidebar' => 'nullable|boolean',
            'header_style' => 'nullable|in:default,centered,minimal',
            'hero_banner' => 'nullable|string',
            'hero_title' => 'nullable|string',
            'hero_subtitle' => 'nullable|string',
            'hero_button_text' => 'nullable|string',
            'hero_button_link' => 'nullable|url',
            'promotional_banners' => 'nullable|array',
            'custom_css' => 'nullable|string',
            'logo' => 'nullable|string',
            'favicon' => 'nullable|string',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'line_url' => 'nullable|string',
            'youtube_url' => 'nullable|url',
            'show_contact_widget' => 'nullable|boolean',
            'contact_widget_position' => 'nullable|in:bottom-right,bottom-left,top-right,top-left',
            'footer_about' => 'nullable|string',
            'footer_links' => 'nullable|array',
        ]);

        try {
            $vendorTheme = $this->addonService->customizeVendorTheme($vendor, $validated);

            return response()->json([
                'message' => 'Theme customized successfully',
                'theme' => $vendorTheme,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Reset vendor theme to defaults
     */
    public function reset()
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $theme = $vendor->vendorTheme;

        if (!$theme) {
            return response()->json(['message' => 'No theme configured'], 404);
        }

        $theme->resetToDefaults();

        return response()->json([
            'message' => 'Theme reset to defaults',
            'theme' => $theme->fresh(),
        ]);
    }

    /**
     * Get public store theme (for storefront display)
     */
    public function publicTheme($vendorId)
    {
        $vendor = \App\Models\Vendor::findOrFail($vendorId);
        $theme = $vendor->vendorTheme;

        if (!$theme || !$theme->is_active) {
            return response()->json(['message' => 'No active theme'], 404);
        }

        return response()->json($theme);
    }

    /**
     * Admin: Create theme template
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:store_themes,slug',
            'description' => 'nullable|string',
            'preview_image' => 'nullable|string',
            'screenshots' => 'nullable|array',
            'color_scheme' => 'nullable|array',
            'typography' => 'nullable|array',
            'layout_options' => 'nullable|array',
            'category' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'is_premium' => 'boolean',
        ]);

        $theme = StoreTheme::create($validated);

        return response()->json($theme, 201);
    }

    /**
     * Admin: Update theme template
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $theme = StoreTheme::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $theme->update($validated);

        return response()->json($theme);
    }
}
