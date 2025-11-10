<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComponentSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComponentSettingController extends Controller
{
    /**
     * Display a listing of component settings
     */
    public function index(Request $request)
    {
        $query = ComponentSetting::query();

        // Filter by component type
        if ($request->filled('component_type')) {
            $query->where('component_type', $request->component_type);
        }

        // Filter by platform
        if ($request->filled('platform')) {
            $query->forPlatform($request->platform);
        }

        $components = $query->orderBy('component_type')
            ->orderBy('component_name')
            ->paginate(20);

        $componentTypes = ComponentSetting::distinct('component_type')
            ->pluck('component_type');

        return view('admin.component-settings.index', compact('components', 'componentTypes'));
    }

    /**
     * Show the form for creating a new component setting
     */
    public function create()
    {
        return view('admin.component-settings.create');
    }

    /**
     * Store a newly created component setting
     */
    public function store(Request $request)
    {
        $validated = $this->validateComponentSetting($request);

        $component = ComponentSetting::create($validated);

        return redirect()
            ->route('admin.component-settings.edit', $component)
            ->with('success', 'Component Setting created successfully');
    }

    /**
     * Show the form for editing a component setting
     */
    public function edit(ComponentSetting $componentSetting)
    {
        return view('admin.component-settings.edit', compact('componentSetting'));
    }

    /**
     * Update the specified component setting
     */
    public function update(Request $request, ComponentSetting $componentSetting)
    {
        $validated = $this->validateComponentSetting($request, $componentSetting);

        $componentSetting->update($validated);

        return redirect()
            ->route('admin.component-settings.edit', $componentSetting)
            ->with('success', 'Component Setting updated successfully');
    }

    /**
     * Remove the specified component setting
     */
    public function destroy(ComponentSetting $componentSetting)
    {
        $componentSetting->delete();

        return redirect()
            ->route('admin.component-settings.index')
            ->with('success', 'Component Setting deleted successfully');
    }

    /**
     * Toggle enabled status of a component setting
     */
    public function toggleEnabled(ComponentSetting $componentSetting)
    {
        $componentSetting->update([
            'is_enabled' => !$componentSetting->is_enabled
        ]);

        return response()->json([
            'success' => true,
            'is_enabled' => $componentSetting->is_enabled,
        ]);
    }

    /**
     * Duplicate a component setting
     */
    public function duplicate(ComponentSetting $componentSetting)
    {
        $newComponent = $componentSetting->replicate();
        $newComponent->component_id = $componentSetting->component_id . '_copy_' . time();
        $newComponent->component_name = $componentSetting->component_name . ' (Copy)';
        $newComponent->save();

        return redirect()
            ->route('admin.component-settings.edit', $newComponent)
            ->with('success', 'Component Setting duplicated successfully');
    }

    /**
     * Validate component setting request
     */
    protected function validateComponentSetting(Request $request, ?ComponentSetting $componentSetting = null)
    {
        $uniqueRule = $componentSetting
            ? Rule::unique('component_settings', 'component_id')->ignore($componentSetting->id)
            : 'unique:component_settings,component_id';

        return $request->validate([
            'component_id' => ['required', 'string', 'max:100', $uniqueRule],
            'component_name' => ['required', 'string', 'max:255'],
            'component_name_en' => ['nullable', 'string', 'max:255'],
            'component_type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            // Size
            'min_height' => ['nullable', 'integer', 'min:0'],
            'max_height' => ['nullable', 'integer', 'min:0'],
            'min_width' => ['nullable', 'integer', 'min:0'],
            'max_width' => ['nullable', 'integer', 'min:0'],
            'fixed_height' => ['nullable', 'integer', 'min:0'],
            'fixed_width' => ['nullable', 'integer', 'min:0'],
            // Spacing
            'padding_top' => ['nullable', 'integer', 'min:0'],
            'padding_bottom' => ['nullable', 'integer', 'min:0'],
            'padding_left' => ['nullable', 'integer', 'min:0'],
            'padding_right' => ['nullable', 'integer', 'min:0'],
            'margin_top' => ['nullable', 'integer'],
            'margin_bottom' => ['nullable', 'integer'],
            'margin_left' => ['nullable', 'integer'],
            'margin_right' => ['nullable', 'integer'],
            // Typography
            'font_size' => ['nullable', 'integer', 'min:8', 'max:100'],
            'font_weight' => ['nullable', 'string', 'max:20'],
            'line_height' => ['nullable', 'numeric', 'min:0.5', 'max:5'],
            'text_align' => ['nullable', 'string', Rule::in(['left', 'center', 'right', 'justify'])],
            'text_decoration' => ['nullable', 'string', 'max:50'],
            'text_transform' => ['nullable', 'string', Rule::in(['none', 'uppercase', 'lowercase', 'capitalize'])],
            // Colors
            'background_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'background_dark_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'text_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'text_dark_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_dark_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            // Border & Shape
            'border_width' => ['nullable', 'integer', 'min:0', 'max:20'],
            'corner_radius' => ['nullable', 'integer', 'min:0', 'max:200'],
            'corner_radius_top_left' => ['nullable', 'integer', 'min:0', 'max:200'],
            'corner_radius_top_right' => ['nullable', 'integer', 'min:0', 'max:200'],
            'corner_radius_bottom_left' => ['nullable', 'integer', 'min:0', 'max:200'],
            'corner_radius_bottom_right' => ['nullable', 'integer', 'min:0', 'max:200'],
            // Shadow
            'shadow_elevation' => ['nullable', 'integer', 'min:0', 'max:24'],
            'shadow_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'shadow_offset_x' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'shadow_offset_y' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'shadow_blur_radius' => ['nullable', 'integer', 'min:0', 'max:100'],
            // Interaction States - Hover
            'hover_background_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'hover_text_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'hover_border_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            // Interaction States - Active
            'active_background_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'active_text_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'active_border_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            // Interaction States - Disabled
            'disabled_background_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'disabled_text_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'disabled_border_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'disabled_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            // Focus States
            'focus_border_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'focus_border_width' => ['nullable', 'integer', 'min:0', 'max:10'],
            'focus_shadow_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            // Icon
            'icon_size' => ['nullable', 'integer', 'min:8', 'max:100'],
            'icon_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'icon_dark_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'icon_position' => ['nullable', 'string', Rule::in(['left', 'right', 'top', 'bottom'])],
            // Animation
            'animation_enabled' => ['boolean'],
            'animation_type' => ['nullable', 'string', 'max:50'],
            'animation_duration' => ['nullable', 'integer', 'min:50', 'max:2000'],
            // Other
            'opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'custom_properties' => ['nullable', 'json'],
            'platform' => ['required', 'string', Rule::in(['android', 'ios', 'all'])],
            'min_app_version' => ['nullable', 'string', 'max:20'],
            'is_enabled' => ['boolean'],
        ]);
    }
}
