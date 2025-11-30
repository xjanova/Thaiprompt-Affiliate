<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppControlSection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppControlSectionController extends Controller
{
    /**
     * Display a listing of control sections
     */
    public function index()
    {
        $sections = AppControlSection::orderBy('sort_order')->paginate(20);
        return view('admin.app-control-sections.index', compact('sections'));
    }

    /**
     * Show the form for creating a new control section
     */
    public function create()
    {
        return view('admin.app-control-sections.create');
    }

    /**
     * Store a newly created control section
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => ['required', 'string', 'max:100', 'unique:app_control_sections,section_id'],
            'section_name' => ['required', 'string', 'max:255'],
            'section_name_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'is_visible' => ['boolean'],
            'display_position' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'layout_type' => ['nullable', 'string', 'max:50'],
            'min_height' => ['nullable', 'integer', 'min:0'],
            'max_height' => ['nullable', 'integer', 'min:0'],
            'min_width' => ['nullable', 'integer', 'min:0'],
            'max_width' => ['nullable', 'integer', 'min:0'],
            'background_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'background_dark_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_dark_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_width' => ['nullable', 'integer', 'min:0', 'max:20'],
            'border_radius' => ['nullable', 'integer', 'min:0', 'max:100'],
            'elevation' => ['nullable', 'integer', 'min:0', 'max:24'],
            'opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'padding_top' => ['nullable', 'integer', 'min:0'],
            'padding_bottom' => ['nullable', 'integer', 'min:0'],
            'padding_left' => ['nullable', 'integer', 'min:0'],
            'padding_right' => ['nullable', 'integer', 'min:0'],
            'margin_top' => ['nullable', 'integer'],
            'margin_bottom' => ['nullable', 'integer'],
            'margin_left' => ['nullable', 'integer'],
            'margin_right' => ['nullable', 'integer'],
            'animation_enabled' => ['boolean'],
            'animation_type' => ['nullable', 'string', 'max:50'],
            'animation_duration' => ['nullable', 'integer', 'min:100', 'max:2000'],
            'custom_properties' => ['nullable', 'json'],
            'platform' => ['required', 'string', Rule::in(['android', 'ios', 'all'])],
            'min_app_version' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $section = AppControlSection::create($validated);

        return redirect()
            ->route('admin.app-control-sections.edit', $section)
            ->with('success', 'Control Section created successfully');
    }

    /**
     * Show the form for editing a control section
     */
    public function edit(AppControlSection $appControlSection)
    {
        return view('admin.app-control-sections.edit', compact('appControlSection'));
    }

    /**
     * Update the specified control section
     */
    public function update(Request $request, AppControlSection $appControlSection)
    {
        $validated = $request->validate([
            'section_id' => ['required', 'string', 'max:100', Rule::unique('app_control_sections', 'section_id')->ignore($appControlSection->id)],
            'section_name' => ['required', 'string', 'max:255'],
            'section_name_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'is_visible' => ['boolean'],
            'display_position' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'layout_type' => ['nullable', 'string', 'max:50'],
            'min_height' => ['nullable', 'integer', 'min:0'],
            'max_height' => ['nullable', 'integer', 'min:0'],
            'min_width' => ['nullable', 'integer', 'min:0'],
            'max_width' => ['nullable', 'integer', 'min:0'],
            'background_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'background_dark_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_dark_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'border_width' => ['nullable', 'integer', 'min:0', 'max:20'],
            'border_radius' => ['nullable', 'integer', 'min:0', 'max:100'],
            'elevation' => ['nullable', 'integer', 'min:0', 'max:24'],
            'opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'padding_top' => ['nullable', 'integer', 'min:0'],
            'padding_bottom' => ['nullable', 'integer', 'min:0'],
            'padding_left' => ['nullable', 'integer', 'min:0'],
            'padding_right' => ['nullable', 'integer', 'min:0'],
            'margin_top' => ['nullable', 'integer'],
            'margin_bottom' => ['nullable', 'integer'],
            'margin_left' => ['nullable', 'integer'],
            'margin_right' => ['nullable', 'integer'],
            'animation_enabled' => ['boolean'],
            'animation_type' => ['nullable', 'string', 'max:50'],
            'animation_duration' => ['nullable', 'integer', 'min:100', 'max:2000'],
            'custom_properties' => ['nullable', 'json'],
            'platform' => ['required', 'string', Rule::in(['android', 'ios', 'all'])],
            'min_app_version' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $appControlSection->update($validated);

        return redirect()
            ->route('admin.app-control-sections.edit', $appControlSection)
            ->with('success', 'Control Section updated successfully');
    }

    /**
     * Remove the specified control section
     */
    public function destroy(AppControlSection $appControlSection)
    {
        $appControlSection->delete();

        return redirect()
            ->route('admin.app-control-sections.index')
            ->with('success', 'Control Section deleted successfully');
    }

    /**
     * Toggle visibility of a control section
     */
    public function toggleVisibility(AppControlSection $appControlSection)
    {
        $appControlSection->update([
            'is_visible' => !$appControlSection->is_visible
        ]);

        return response()->json([
            'success' => true,
            'is_visible' => $appControlSection->is_visible,
        ]);
    }

    /**
     * Toggle active status of a control section
     */
    public function toggleActive(AppControlSection $appControlSection)
    {
        $appControlSection->update([
            'is_active' => !$appControlSection->is_active
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $appControlSection->is_active,
        ]);
    }

    /**
     * Update sort order of control sections
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:app_control_sections,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['items'] as $item) {
            AppControlSection::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sort order updated successfully',
        ]);
    }
}
