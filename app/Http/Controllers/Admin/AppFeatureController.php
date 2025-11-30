<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppFeature;
use Illuminate\Http\Request;

class AppFeatureController extends Controller
{
    /**
     * Display all features
     */
    public function index()
    {
        $features = AppFeature::orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('admin.app-features.index', compact('features'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.app-features.create');
    }

    /**
     * Store new feature
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'feature_key' => ['required', 'string', 'max:100', 'unique:app_features'],
            'feature_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
            'category' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'config' => ['nullable', 'json'],
            'min_app_version' => ['nullable', 'string', 'max:20'],
            'platform' => ['nullable', 'string', 'in:android,ios,all'],
        ]);

        $validated['is_enabled'] = $request->has('is_enabled');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if (!empty($validated['config'])) {
            $validated['config'] = json_decode($validated['config'], true);
        }

        AppFeature::create($validated);

        return redirect()
            ->route('admin.app-features.index')
            ->with('success', 'เพิ่มฟีเจอร์สำเร็จ');
    }

    /**
     * Show edit form
     */
    public function edit(AppFeature $appFeature)
    {
        return view('admin.app-features.edit', compact('appFeature'));
    }

    /**
     * Update feature
     */
    public function update(Request $request, AppFeature $appFeature)
    {
        $validated = $request->validate([
            'feature_key' => ['required', 'string', 'max:100', 'unique:app_features,feature_key,' . $appFeature->id],
            'feature_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
            'category' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'config' => ['nullable', 'json'],
            'min_app_version' => ['nullable', 'string', 'max:20'],
            'platform' => ['nullable', 'string', 'in:android,ios,all'],
        ]);

        $validated['is_enabled'] = $request->has('is_enabled');

        if (!empty($validated['config'])) {
            $validated['config'] = json_decode($validated['config'], true);
        }

        $appFeature->update($validated);

        return redirect()
            ->route('admin.app-features.index')
            ->with('success', 'อัพเดทฟีเจอร์สำเร็จ');
    }

    /**
     * Delete feature
     */
    public function destroy(AppFeature $appFeature)
    {
        $appFeature->delete();

        return redirect()
            ->route('admin.app-features.index')
            ->with('success', 'ลบฟีเจอร์สำเร็จ');
    }

    /**
     * Toggle feature status
     */
    public function toggle(AppFeature $appFeature)
    {
        $appFeature->update([
            'is_enabled' => !$appFeature->is_enabled,
        ]);

        return response()->json([
            'success' => true,
            'is_enabled' => $appFeature->is_enabled,
        ]);
    }

    /**
     * Get features for API
     */
    public function apiFeatures(Request $request)
    {
        $query = AppFeature::enabled()->orderBy('sort_order');

        if ($request->has('platform')) {
            $query->byPlatform($request->platform);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $features = $query->get();

        // Filter by app version if provided
        if ($request->has('app_version')) {
            $features = $features->filter(function ($feature) use ($request) {
                return $feature->isAvailableForVersion($request->app_version);
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }
}
