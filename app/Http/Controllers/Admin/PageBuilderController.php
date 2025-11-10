<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageBuilder;
use App\Models\PageBuilderSection;
use App\Models\PageBuilderTemplate;
use App\Services\PageBuilderService;
use App\Services\PageSectionService;
use App\Services\PageTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PageBuilderController extends Controller
{
    protected PageBuilderService $pageBuilderService;
    protected PageSectionService $sectionService;
    protected PageTemplateService $templateService;

    public function __construct(
        PageBuilderService $pageBuilderService,
        PageSectionService $sectionService,
        PageTemplateService $templateService
    ) {
        $this->pageBuilderService = $pageBuilderService;
        $this->sectionService = $sectionService;
        $this->templateService = $templateService;
    }

    /**
     * Display a listing of pages
     */
    public function index()
    {
        $pages = PageBuilder::with('sections')->latest()->paginate(20);

        return view('admin.page-builder.index', compact('pages'));
    }

    /**
     * Show the editor for creating a new page
     */
    public function create()
    {
        $pageTypes = [
            'homepage' => 'Homepage',
            'wiki' => 'Wiki',
            'about' => 'About Page',
            'landing' => 'Landing Page',
            'custom' => 'Custom Page',
        ];

        $templates = $this->templateService->getAllTemplates();

        return view('admin.page-builder.create', compact('pageTypes', 'templates'));
    }

    /**
     * Store a newly created page
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_type' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:page_builders,slug',
            'is_active' => 'boolean',
            'meta_data' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $page = $this->pageBuilderService->createPage($request->all());

        return redirect()->route('admin.page-builder.edit', $page)
            ->with('success', 'Page created successfully!');
    }

    /**
     * Show the editor for editing a page
     */
    public function edit(PageBuilder $page)
    {
        $page->load('sections');

        $pageTypes = [
            'homepage' => 'Homepage',
            'wiki' => 'Wiki',
            'about' => 'About Page',
            'landing' => 'Landing Page',
            'custom' => 'Custom Page',
        ];

        $templates = $this->templateService->getAllTemplates();
        $sectionTypes = PageBuilderSection::SECTION_TYPES;

        return view('admin.page-builder.edit', compact('page', 'pageTypes', 'templates', 'sectionTypes'));
    }

    /**
     * Update the specified page
     */
    public function update(Request $request, PageBuilder $page)
    {
        $validator = Validator::make($request->all(), [
            'page_type' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:page_builders,slug,' . $page->id,
            'is_active' => 'boolean',
            'meta_data' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Handle checkbox for is_active (unchecked = false)
        $data = $request->all();
        $data['is_active'] = $request->has('is_active');

        $this->pageBuilderService->updatePage($page, $data);

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Page updated successfully!',
                'page' => $page->fresh()
            ]);
        }

        return redirect()->route('admin.page-builder.edit', $page)
            ->with('success', 'Page updated successfully!');
    }

    /**
     * Remove the specified page
     */
    public function destroy(PageBuilder $page)
    {
        $this->pageBuilderService->deletePage($page);

        return redirect()->route('admin.page-builder.index')
            ->with('success', 'Page deleted successfully!');
    }

    /**
     * Duplicate page
     */
    public function duplicate(PageBuilder $page)
    {
        $newPage = $this->pageBuilderService->duplicatePage($page);

        return redirect()->route('admin.page-builder.edit', $newPage)
            ->with('success', 'Page duplicated successfully!');
    }

    /**
     * Toggle page active status
     */
    public function toggleActive(PageBuilder $page)
    {
        $this->pageBuilderService->toggleActive($page);

        return redirect()->back()
            ->with('success', 'Page status updated!');
    }

    /**
     * Reorder sections
     */
    public function reorderSections(Request $request, PageBuilder $page)
    {
        $validator = Validator::make($request->all(), [
            'section_ids' => 'required|array',
            'section_ids.*' => 'exists:page_builder_sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $this->pageBuilderService->reorderSections($page, $request->section_ids);

        return response()->json(['success' => true, 'message' => 'Sections reordered successfully!']);
    }

    /**
     * Preview page
     */
    public function preview(PageBuilder $page)
    {
        $renderData = $this->pageBuilderService->getRenderData($page);

        return view('admin.page-builder.preview', compact('page', 'renderData'));
    }
}
