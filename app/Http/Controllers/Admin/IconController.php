<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\IconHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IconController extends Controller
{
    /**
     * Display icon manager
     */
    public function index(Request $request)
    {
        $category = $request->get('category', 'system');
        $categories = IconHelper::categories();
        $icons = IconHelper::list($category);

        return view('admin.icons.index', compact('category', 'categories', 'icons'));
    }

    /**
     * Upload icon
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'icon' => 'required|file|mimes:svg,png,jpg,jpeg,webp|max:2048',
            'category' => 'required|in:system,theme,custom,social,flags',
            'name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $file = $request->file('icon');
        $category = $request->input('category');
        $name = $request->input('name');

        // Sanitize name if provided
        if ($name) {
            $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        }

        $filename = IconHelper::upload($file, $category, $name);

        if ($filename) {
            return response()->json([
                'success' => true,
                'message' => __('Icon uploaded successfully'),
                'data' => [
                    'filename' => $filename,
                    'url' => IconHelper::url($filename, $category),
                    'category' => $category,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Failed to upload icon'),
        ], 500);
    }

    /**
     * Delete icon
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'category' => 'required|in:system,theme,custom,social,flags',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $name = $request->input('name');
        $category = $request->input('category');

        // Only allow deleting from custom category for safety
        if ($category !== 'custom' && !auth()->user()->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => __('You can only delete custom icons'),
            ], 403);
        }

        $deleted = IconHelper::delete($name, $category);

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => __('Icon deleted successfully'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Failed to delete icon'),
        ], 500);
    }

    /**
     * Get icons list (AJAX)
     */
    public function list(Request $request)
    {
        $category = $request->get('category', 'system');
        $icons = IconHelper::list($category);

        return response()->json([
            'success' => true,
            'data' => $icons,
        ]);
    }
}
