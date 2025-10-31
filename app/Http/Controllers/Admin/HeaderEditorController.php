<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\PageTemplate;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class HeaderEditorController extends Controller
{
    /**
     * Display the header editor interface
     */
    public function index()
    {
        $headerSettings = [
            // Layout & Position
            'header_layout' => Setting::get('header_layout', 'default'), // default, floating, sticky
            'header_position' => Setting::get('header_position', 'relative'), // relative, fixed, absolute
            'header_transparent' => Setting::get('header_transparent', false),
            'header_transparent_on_scroll' => Setting::get('header_transparent_on_scroll', false),

            // Scroll Behavior
            'header_sticky_scroll' => Setting::get('header_sticky_scroll', false),
            'header_hide_on_scroll' => Setting::get('header_hide_on_scroll', false),
            'header_shrink_on_scroll' => Setting::get('header_shrink_on_scroll', false),

            // Background & Effects
            'header_bg_color' => Setting::get('header_bg_color', '#ffffff'),
            'header_bg_opacity' => Setting::get('header_bg_opacity', 100),
            'header_blur' => Setting::get('header_blur', false),
            'header_blur_amount' => Setting::get('header_blur_amount', 10),
            'header_shadow' => Setting::get('header_shadow', 'lg'),
            'header_shadow_on_scroll' => Setting::get('header_shadow_on_scroll', true),

            // Dimensions
            'header_height' => Setting::get('header_height', 64),
            'header_height_scrolled' => Setting::get('header_height_scrolled', 56),
            'header_padding_x' => Setting::get('header_padding_x', 16),

            // Colors
            'header_text_color' => Setting::get('header_text_color', '#374151'),
            'header_text_color_scroll' => Setting::get('header_text_color_scroll', '#374151'),
            'header_hover_color' => Setting::get('header_hover_color', '#111827'),

            // Border
            'header_border_bottom' => Setting::get('header_border_bottom', false),
            'header_border_color' => Setting::get('header_border_color', '#e5e7eb'),
            'header_border_width' => Setting::get('header_border_width', 1),

            // Animation
            'header_animation_duration' => Setting::get('header_animation_duration', 300),
            'header_animation_easing' => Setting::get('header_animation_easing', 'ease-in-out'),

            // Logo
            'header_logo_height' => Setting::get('header_logo_height', 40),
            'header_logo_height_scrolled' => Setting::get('header_logo_height_scrolled', 32),

            // Advanced Effects
            'header_gradient' => Setting::get('header_gradient', false),
            'header_gradient_from' => Setting::get('header_gradient_from', '#ffffff'),
            'header_gradient_to' => Setting::get('header_gradient_to', '#f3f4f6'),
            'header_gradient_direction' => Setting::get('header_gradient_direction', 'to-r'),
        ];

        // Get templates for selector
        $templates = PageTemplate::active()->orderBy('is_default', 'desc')->get();
        $currentTemplateId = Setting::get('home_template_id');

        // Get menu items
        $menuItems = MenuItem::getForLocation('header');

        return view('admin.header-editor.index', compact('headerSettings', 'templates', 'currentTemplateId', 'menuItems'));
    }

    /**
     * Update header settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Layout & Position
            'header_layout' => 'required|string|in:default,floating,sticky',
            'header_position' => 'required|string|in:relative,fixed,absolute',
            'header_transparent' => 'boolean',
            'header_transparent_on_scroll' => 'boolean',

            // Scroll Behavior
            'header_sticky_scroll' => 'boolean',
            'header_hide_on_scroll' => 'boolean',
            'header_shrink_on_scroll' => 'boolean',

            // Background & Effects
            'header_bg_color' => 'required|string',
            'header_bg_opacity' => 'required|integer|min:0|max:100',
            'header_blur' => 'boolean',
            'header_blur_amount' => 'required|integer|min:0|max:50',
            'header_shadow' => 'required|string',
            'header_shadow_on_scroll' => 'boolean',

            // Dimensions
            'header_height' => 'required|integer|min:40|max:200',
            'header_height_scrolled' => 'required|integer|min:40|max:200',
            'header_padding_x' => 'required|integer|min:0|max:100',

            // Colors
            'header_text_color' => 'required|string',
            'header_text_color_scroll' => 'required|string',
            'header_hover_color' => 'required|string',

            // Border
            'header_border_bottom' => 'boolean',
            'header_border_color' => 'required|string',
            'header_border_width' => 'required|integer|min:0|max:10',

            // Animation
            'header_animation_duration' => 'required|integer|min:100|max:1000',
            'header_animation_easing' => 'required|string',

            // Logo
            'header_logo_height' => 'required|integer|min:20|max:100',
            'header_logo_height_scrolled' => 'required|integer|min:20|max:100',

            // Advanced Effects
            'header_gradient' => 'boolean',
            'header_gradient_from' => 'required|string',
            'header_gradient_to' => 'required|string',
            'header_gradient_direction' => 'required|string',
        ]);

        // Save each setting
        foreach ($validated as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
            Setting::set($key, $value, $type, 'header');
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่า Header สำเร็จ!'
        ]);
    }

    /**
     * Reset header settings to default
     */
    public function reset()
    {
        $defaultSettings = [
            'header_layout' => 'default',
            'header_position' => 'relative',
            'header_transparent' => false,
            'header_transparent_on_scroll' => false,
            'header_sticky_scroll' => false,
            'header_hide_on_scroll' => false,
            'header_shrink_on_scroll' => false,
            'header_bg_color' => '#ffffff',
            'header_bg_opacity' => 100,
            'header_blur' => false,
            'header_blur_amount' => 10,
            'header_shadow' => 'lg',
            'header_shadow_on_scroll' => true,
            'header_height' => 64,
            'header_height_scrolled' => 56,
            'header_padding_x' => 16,
            'header_text_color' => '#374151',
            'header_text_color_scroll' => '#374151',
            'header_hover_color' => '#111827',
            'header_border_bottom' => false,
            'header_border_color' => '#e5e7eb',
            'header_border_width' => 1,
            'header_animation_duration' => 300,
            'header_animation_easing' => 'ease-in-out',
            'header_logo_height' => 40,
            'header_logo_height_scrolled' => 32,
            'header_gradient' => false,
            'header_gradient_from' => '#ffffff',
            'header_gradient_to' => '#f3f4f6',
            'header_gradient_direction' => 'to-r',
        ];

        foreach ($defaultSettings as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
            Setting::set($key, $value, $type, 'header');
        }

        return response()->json([
            'success' => true,
            'message' => 'รีเซ็ตการตั้งค่า Header เป็นค่าเริ่มต้นแล้ว!'
        ]);
    }

    /**
     * Update homepage template
     */
    public function updateTemplate(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|integer|exists:page_templates,id',
        ]);

        Setting::set('home_template_id', $validated['template_id'], 'integer', 'system');

        $template = PageTemplate::find($validated['template_id']);

        return response()->json([
            'success' => true,
            'message' => 'ตั้งค่าเทมเพลตหน้าแรกเป็น "' . $template->name . '" แล้ว!'
        ]);
    }

    /**
     * Get menu items
     */
    public function getMenuItems()
    {
        $menuItems = MenuItem::getForLocation('header');

        return response()->json([
            'success' => true,
            'items' => $menuItems
        ]);
    }

    /**
     * Store new menu item
     */
    public function storeMenuItem(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:500',
            'route' => 'nullable|string|max:255',
            'target' => 'required|string|in:_self,_blank',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|exists:menu_items,id',
            'is_active' => 'boolean',
            'conditions' => 'nullable|array',
        ]);

        $validated['menu_location'] = 'header';
        $validated['order'] = MenuItem::forLocation('header')->max('order') + 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $menuItem = MenuItem::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มเมนูสำเร็จ!',
            'item' => $menuItem->load('children')
        ]);
    }

    /**
     * Update menu item
     */
    public function updateMenuItem(Request $request, $id)
    {
        $menuItem = MenuItem::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'url' => 'nullable|string|max:500',
            'route' => 'nullable|string|max:255',
            'target' => 'sometimes|string|in:_self,_blank',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|exists:menu_items,id',
            'is_active' => 'sometimes|boolean',
            'conditions' => 'nullable|array',
        ]);

        $menuItem->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตเมนูสำเร็จ!',
            'item' => $menuItem->load('children')
        ]);
    }

    /**
     * Delete menu item
     */
    public function deleteMenuItem($id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $menuItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบเมนูสำเร็จ!'
        ]);
    }

    /**
     * Reorder menu items
     */
    public function reorderMenuItems(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
        ]);

        MenuItem::reorderForLocation('header', $validated['items']);

        return response()->json([
            'success' => true,
            'message' => 'จัดเรียงเมนูสำเร็จ!'
        ]);
    }
}
