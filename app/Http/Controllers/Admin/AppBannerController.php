<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppBanner;
use App\Services\WebPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppBannerController extends Controller
{
    /**
     * Display all banners
     */
    public function index()
    {
        $banners = AppBanner::orderBy('position')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('position');

        return view('admin.app-banners.index', compact('banners'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.app-banners.create');
    }

    /**
     * Store new banner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'image_url' => ['nullable', 'image', 'max:5120'],
            'image_url_dark' => ['nullable', 'image', 'max:5120'],
            'type' => ['required', 'string', 'in:info,warning,success,promotion,announcement'],
            'position' => ['required', 'string', 'max:50'],
            'action_type' => ['nullable', 'string', 'in:url,route,deeplink,none'],
            'action_value' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_dismissible' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'target_audience' => ['required', 'string', 'max:50'],
            'target_user_ids' => ['nullable', 'string'],
            'platform' => ['nullable', 'string', 'in:android,ios,all'],
        ]);

        // Handle image uploads
        $webpService = app(WebPService::class);

        if ($request->hasFile('image_url')) {
            $result = $webpService->convertAndStore(
                $request->file('image_url'),
                'app-banners',
                85
            );
            $validated['image_url'] = $result['url'];
        }

        if ($request->hasFile('image_url_dark')) {
            $result = $webpService->convertAndStore(
                $request->file('image_url_dark'),
                'app-banners',
                85
            );
            $validated['image_url_dark'] = $result['url'];
        }

        // Handle boolean checkboxes
        $validated['is_active'] = $request->has('is_active');
        $validated['is_dismissible'] = $request->has('is_dismissible');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        // Parse target user IDs
        if (!empty($validated['target_user_ids'])) {
            $validated['target_user_ids'] = array_map('trim', explode(',', $validated['target_user_ids']));
        } else {
            $validated['target_user_ids'] = null;
        }

        AppBanner::create($validated);

        return redirect()
            ->route('admin.app-banners.index')
            ->with('success', 'เพิ่มแบนเนอร์สำเร็จ');
    }

    /**
     * Show edit form
     */
    public function edit(AppBanner $appBanner)
    {
        return view('admin.app-banners.edit', compact('appBanner'));
    }

    /**
     * Update banner
     */
    public function update(Request $request, AppBanner $appBanner)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'image_url' => ['nullable', 'image', 'max:5120'],
            'image_url_dark' => ['nullable', 'image', 'max:5120'],
            'type' => ['required', 'string', 'in:info,warning,success,promotion,announcement'],
            'position' => ['required', 'string', 'max:50'],
            'action_type' => ['nullable', 'string', 'in:url,route,deeplink,none'],
            'action_value' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_dismissible' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'target_audience' => ['required', 'string', 'max:50'],
            'target_user_ids' => ['nullable', 'string'],
            'platform' => ['nullable', 'string', 'in:android,ios,all'],
        ]);

        // Handle image uploads
        $webpService = app(WebPService::class);

        if ($request->hasFile('image_url')) {
            $result = $webpService->convertAndStore(
                $request->file('image_url'),
                'app-banners',
                85
            );
            $validated['image_url'] = $result['url'];
        }

        if ($request->hasFile('image_url_dark')) {
            $result = $webpService->convertAndStore(
                $request->file('image_url_dark'),
                'app-banners',
                85
            );
            $validated['image_url_dark'] = $result['url'];
        }

        // Handle boolean checkboxes
        $validated['is_active'] = $request->has('is_active');
        $validated['is_dismissible'] = $request->has('is_dismissible');

        // Parse target user IDs
        if (!empty($validated['target_user_ids'])) {
            $validated['target_user_ids'] = array_map('trim', explode(',', $validated['target_user_ids']));
        } else {
            $validated['target_user_ids'] = null;
        }

        $appBanner->update($validated);

        return redirect()
            ->route('admin.app-banners.index')
            ->with('success', 'อัพเดทแบนเนอร์สำเร็จ');
    }

    /**
     * Delete banner
     */
    public function destroy(AppBanner $appBanner)
    {
        $appBanner->delete();

        return redirect()
            ->route('admin.app-banners.index')
            ->with('success', 'ลบแบนเนอร์สำเร็จ');
    }

    /**
     * Toggle banner status
     */
    public function toggle(AppBanner $appBanner)
    {
        $appBanner->update([
            'is_active' => !$appBanner->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $appBanner->is_active,
        ]);
    }

    /**
     * Track banner view
     */
    public function trackView(AppBanner $appBanner)
    {
        $appBanner->incrementViews();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Track banner click
     */
    public function trackClick(AppBanner $appBanner)
    {
        $appBanner->incrementClicks();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Get banners for API
     */
    public function apiBanners(Request $request)
    {
        $query = AppBanner::active()->orderBy('sort_order');

        if ($request->has('position')) {
            $query->byPosition($request->position);
        }

        if ($request->has('platform')) {
            $query->byPlatform($request->platform);
        }

        $banners = $query->get();

        // Filter by user context
        $userId = $request->user()->id ?? null;
        $userType = $request->user_type ?? 'all';

        $banners = $banners->filter(function ($banner) use ($userId, $userType) {
            return $banner->isVisibleForUser($userId, $userType);
        })->values();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }
}
