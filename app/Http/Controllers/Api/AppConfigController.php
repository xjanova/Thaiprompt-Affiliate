<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AppThemeSetting;
use App\Models\AppFeature;
use App\Models\AppBanner;
use App\Models\AppMaintenance;
use App\Models\AppControlSection;
use App\Models\ComponentSetting;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    /**
     * Get all app configuration (settings, theme, features)
     */
    public function config(Request $request)
    {
        $platform = $request->platform ?? 'all';
        $appVersion = $request->app_version ?? '1.0.0';
        $userId = $request->user()->id ?? null;
        $userType = $this->getUserType($request);

        // Check maintenance mode first
        $isInMaintenance = AppMaintenance::isInMaintenanceMode($platform);

        if ($isInMaintenance) {
            $maintenance = AppMaintenance::getInstance();

            // Check if user can bypass
            $bypassKey = $request->bypass_key ?? null;
            $isAdmin = $request->user() ? $this->isAdmin($request->user()) : false;

            $canBypass = $maintenance->canUserBypass($userId, $bypassKey, $isAdmin);

            if (!$canBypass) {
                return response()->json([
                    'success' => false,
                    'is_maintenance' => true,
                    'maintenance' => [
                        'title' => app()->getLocale() === 'th' ? $maintenance->title : $maintenance->title_en,
                        'message' => app()->getLocale() === 'th' ? $maintenance->message : $maintenance->message_en,
                        'image_url' => $maintenance->image_url,
                        'scheduled_end' => $maintenance->getEndTime(),
                        'show_countdown' => $maintenance->show_countdown,
                    ],
                ], 503);
            }
        }

        // Get app settings
        $appSettings = AppSetting::getInstance();

        // Get theme settings
        $themeSettings = AppThemeSetting::getInstance();

        // Get features
        $features = AppFeature::enabled()
            ->byPlatform($platform)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->filter(function ($feature) use ($appVersion) {
                return $feature->isAvailableForVersion($appVersion);
            })
            ->groupBy('category');

        return response()->json([
            'success' => true,
            'is_maintenance' => false,
            'data' => [
                'app_settings' => $appSettings,
                'theme_settings' => $themeSettings,
                'features' => $features,
            ],
        ]);
    }

    /**
     * Get only app settings
     */
    public function settings()
    {
        $appSettings = AppSetting::getInstance();

        return response()->json([
            'success' => true,
            'data' => $appSettings,
        ]);
    }

    /**
     * Get only theme settings
     */
    public function theme()
    {
        $themeSettings = AppThemeSetting::getInstance();

        return response()->json([
            'success' => true,
            'data' => $themeSettings,
        ]);
    }

    /**
     * Get features
     */
    public function features(Request $request)
    {
        $platform = $request->platform ?? 'all';
        $appVersion = $request->app_version ?? '1.0.0';
        $category = $request->category ?? null;

        $query = AppFeature::enabled()
            ->byPlatform($platform)
            ->orderBy('sort_order');

        if ($category) {
            $query->byCategory($category);
        }

        $features = $query->get()
            ->filter(function ($feature) use ($appVersion) {
                return $feature->isAvailableForVersion($appVersion);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }

    /**
     * Get banners
     */
    public function banners(Request $request)
    {
        $platform = $request->platform ?? 'all';
        $position = $request->position ?? null;
        $userId = $request->user()->id ?? null;
        $userType = $this->getUserType($request);

        $query = AppBanner::active()
            ->byPlatform($platform)
            ->orderBy('sort_order');

        if ($position) {
            $query->byPosition($position);
        }

        $banners = $query->get()
            ->filter(function ($banner) use ($userId, $userType) {
                return $banner->isVisibleForUser($userId, $userType);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    /**
     * Track banner view
     */
    public function trackBannerView(Request $request, $bannerId)
    {
        $banner = AppBanner::find($bannerId);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found',
            ], 404);
        }

        $banner->incrementViews();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Track banner click
     */
    public function trackBannerClick(Request $request, $bannerId)
    {
        $banner = AppBanner::find($bannerId);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found',
            ], 404);
        }

        $banner->incrementClicks();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Check maintenance status
     */
    public function maintenanceStatus(Request $request)
    {
        $platform = $request->platform ?? null;
        $isInMaintenance = AppMaintenance::isInMaintenanceMode($platform);

        if (!$isInMaintenance) {
            return response()->json([
                'success' => true,
                'is_maintenance' => false,
            ]);
        }

        $maintenance = AppMaintenance::getInstance();

        // Check if user can bypass
        $userId = $request->user()->id ?? null;
        $bypassKey = $request->bypass_key ?? null;
        $isAdmin = $request->user() ? $this->isAdmin($request->user()) : false;

        $canBypass = $maintenance->canUserBypass($userId, $bypassKey, $isAdmin);

        if ($canBypass) {
            return response()->json([
                'success' => true,
                'is_maintenance' => false,
                'bypass' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'is_maintenance' => true,
            'data' => [
                'title' => app()->getLocale() === 'th' ? $maintenance->title : $maintenance->title_en,
                'message' => app()->getLocale() === 'th' ? $maintenance->message : $maintenance->message_en,
                'image_url' => $maintenance->image_url,
                'scheduled_end' => $maintenance->getEndTime(),
                'show_countdown' => $maintenance->show_countdown,
            ],
        ]);
    }

    /**
     * Check for app updates
     */
    public function checkUpdate(Request $request)
    {
        $currentVersion = $request->app_version ?? '1.0.0';
        $platform = $request->platform ?? null;

        $appSettings = AppSetting::getInstance();

        $needsUpdate = false;
        $forceUpdate = false;

        // Check if current version is below minimum supported version
        if ($appSettings->min_supported_version &&
            version_compare($currentVersion, $appSettings->min_supported_version, '<')) {
            $needsUpdate = true;
            $forceUpdate = true;
        }
        // Check if there's a newer version available
        else if ($appSettings->latest_version &&
                 version_compare($currentVersion, $appSettings->latest_version, '<')) {
            $needsUpdate = true;
            $forceUpdate = $appSettings->force_update;
        }

        $storeUrl = null;
        if ($platform === 'android') {
            $storeUrl = $appSettings->play_store_url;
        } else if ($platform === 'ios') {
            $storeUrl = $appSettings->app_store_url;
        }

        return response()->json([
            'success' => true,
            'needs_update' => $needsUpdate,
            'force_update' => $forceUpdate,
            'current_version' => $currentVersion,
            'latest_version' => $appSettings->latest_version,
            'min_supported_version' => $appSettings->min_supported_version,
            'update_message' => app()->getLocale() === 'th' ?
                $appSettings->update_message_th :
                $appSettings->update_message_en,
            'store_url' => $storeUrl,
        ]);
    }

    /**
     * Helper: Get user type
     */
    private function getUserType(Request $request)
    {
        if (!$request->user()) {
            return 'guest';
        }

        $user = $request->user();

        if ($this->isAdmin($user)) {
            return 'admin';
        }

        if ($user->role === 'seller') {
            return 'seller';
        }

        // Check if user has MLM member
        if ($user->mlmMembers()->exists()) {
            return 'member';
        }

        return 'new_users';
    }

    /**
     * Helper: Check if user is admin
     */
    private function isAdmin($user)
    {
        return $user->is_super_admin ||
               $user->role === 'super_admin' ||
               $user->role === 'admin';
    }

    /**
     * Get control sections for mobile app
     */
    public function controlSections(Request $request)
    {
        $platform = $request->platform ?? 'all';

        $sections = AppControlSection::active()
            ->visible()
            ->forPlatform($platform)
            ->ordered()
            ->get()
            ->map(function ($section) {
                return $section->toApiResponse();
            });

        return response()->json([
            'success' => true,
            'data' => $sections,
        ]);
    }

    /**
     * Get component settings for mobile app
     */
    public function componentSettings(Request $request)
    {
        $platform = $request->platform ?? 'all';
        $componentType = $request->component_type ?? null;

        $query = ComponentSetting::enabled()
            ->forPlatform($platform);

        if ($componentType) {
            $query->ofType($componentType);
        }

        $components = $query->get()
            ->map(function ($component) {
                return $component->toApiResponse();
            })
            ->groupBy('component_type');

        return response()->json([
            'success' => true,
            'data' => $components,
        ]);
    }

    /**
     * Get complete theme configuration including extended fields
     */
    public function completeTheme(Request $request)
    {
        $themeSettings = AppThemeSetting::getInstance();
        $platform = $request->platform ?? 'all';

        // Get control sections
        $sections = AppControlSection::active()
            ->visible()
            ->forPlatform($platform)
            ->ordered()
            ->get()
            ->map(function ($section) {
                return $section->toApiResponse();
            });

        // Get component settings
        $components = ComponentSetting::enabled()
            ->forPlatform($platform)
            ->get()
            ->map(function ($component) {
                return $component->toApiResponse();
            })
            ->groupBy('component_type');

        return response()->json([
            'success' => true,
            'data' => [
                'theme' => $themeSettings,
                'sections' => $sections,
                'components' => $components,
            ],
        ]);
    }
}
