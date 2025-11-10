<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageBuilder;
use App\Models\PageBuilderSection;
use App\Models\PageBuilderTemplate;
use App\Services\PageSectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PageBuilderSectionController extends Controller
{
    protected PageSectionService $sectionService;

    public function __construct(PageSectionService $sectionService)
    {
        $this->sectionService = $sectionService;
    }

    /**
     * Store a newly created section
     */
    public function store(Request $request, PageBuilder $page)
    {
        $validator = Validator::make($request->all(), [
            'section_type' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'settings' => 'nullable|array',
            'content' => 'nullable|array',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $section = $this->sectionService->createSection($page, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully!',
            'section' => $section,
        ]);
    }

    /**
     * Create section from template
     */
    public function createFromTemplate(Request $request, PageBuilder $page, PageBuilderTemplate $template)
    {
        $section = $this->sectionService->createFromTemplate($page, $template, $request->input('order'));

        return response()->json([
            'success' => true,
            'message' => 'Section created from template!',
            'section' => $section,
        ]);
    }

    /**
     * Get section data for editing
     */
    public function edit(PageBuilderSection $section)
    {
        return response()->json([
            'success' => true,
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'section_type' => $section->section_type,
                'content' => $section->content ?? [],
                'settings' => $section->settings ?? [],
                'is_active' => $section->is_active,
                'is_visible' => $section->is_visible,
                'order' => $section->order,
            ],
        ]);
    }

    /**
     * Update the specified section
     */
    public function update(Request $request, PageBuilderSection $section)
    {
        $validator = Validator::make($request->all(), [
            'section_type' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'settings' => 'nullable|array',
            'content' => 'nullable|array',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $section = $this->sectionService->updateSection($section, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully!',
            'section' => $section,
        ]);
    }

    /**
     * Remove the specified section
     */
    public function destroy(PageBuilderSection $section)
    {
        $this->sectionService->deleteSection($section);

        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully!',
        ]);
    }

    /**
     * Duplicate section
     */
    public function duplicate(PageBuilderSection $section)
    {
        $newSection = $this->sectionService->duplicateSection($section);

        return response()->json([
            'success' => true,
            'message' => 'Section duplicated successfully!',
            'section' => $newSection,
        ]);
    }

    /**
     * Move section up
     */
    public function moveUp(PageBuilderSection $section)
    {
        $result = $this->sectionService->moveUp($section);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Section moved up!' : 'Cannot move section up',
        ]);
    }

    /**
     * Move section down
     */
    public function moveDown(PageBuilderSection $section)
    {
        $result = $this->sectionService->moveDown($section);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Section moved down!' : 'Cannot move section down',
        ]);
    }

    /**
     * Toggle section visibility
     */
    public function toggleVisibility(PageBuilderSection $section)
    {
        $section = $this->sectionService->toggleVisibility($section);

        return response()->json([
            'success' => true,
            'message' => 'Section visibility toggled!',
            'section' => $section,
        ]);
    }

    /**
     * Toggle section active status
     */
    public function toggleActive(PageBuilderSection $section)
    {
        $section = $this->sectionService->toggleActive($section);

        return response()->json([
            'success' => true,
            'message' => 'Section status toggled!',
            'section' => $section,
        ]);
    }
}
