<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageTemplate;
use App\Models\PageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    /**
     * Display a listing of templates.
     */
    public function index()
    {
        $templates = PageTemplate::with('creator', 'sections')
            ->withCount('sections')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template.
     */
    public function create()
    {
        $componentLibrary = $this->getComponentLibrary();
        return view('admin.templates.create', compact('componentLibrary'));
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template = PageTemplate::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างเทมเพลตสำเร็จ!',
            'template' => $template,
        ]);
    }

    /**
     * Display the specified template (Builder View).
     */
    public function show($id)
    {
        $template = PageTemplate::with('sections')->findOrFail($id);
        $componentLibrary = $this->getComponentLibrary();

        return view('admin.templates.builder', compact('template', 'componentLibrary'));
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, $id)
    {
        $template = PageTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตเทมเพลตสำเร็จ!',
            'template' => $template,
        ]);
    }

    /**
     * Remove the specified template.
     */
    public function destroy($id)
    {
        $template = PageTemplate::findOrFail($id);

        // Prevent deletion if it's in use
        if ($template->isInUse()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบเทมเพลตที่กำลังใช้งานอยู่ได้',
            ], 400);
        }

        // Prevent deletion of default template
        if ($template->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบเทมเพลตดีฟอลต์ได้',
            ], 400);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบเทมเพลตสำเร็จ!',
        ]);
    }

    /**
     * Duplicate a template.
     */
    public function duplicate($id)
    {
        $template = PageTemplate::findOrFail($id);
        $newTemplate = $template->duplicate();

        return response()->json([
            'success' => true,
            'message' => 'คัดลอกเทมเพลตสำเร็จ!',
            'template' => $newTemplate->load('sections'),
        ]);
    }

    /**
     * Set template as default.
     */
    public function setDefault($id)
    {
        $template = PageTemplate::findOrFail($id);
        $template->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'ตั้งเป็นเทมเพลตดีฟอลต์สำเร็จ!',
        ]);
    }

    /**
     * Get sections for template.
     */
    public function getSections($id)
    {
        $sections = PageSection::getTemplateSections($id, false);

        return response()->json([
            'success' => true,
            'sections' => $sections,
        ]);
    }

    /**
     * Add section to template.
     */
    public function addSection(Request $request, $id)
    {
        $validated = $request->validate([
            'component_type' => 'required|string',
            'template_key' => 'required|string',
            'name' => 'nullable|string',
        ]);

        $template = PageTemplate::findOrFail($id);
        $componentLibrary = $this->getComponentLibrary();

        // Get template data from library
        $componentType = $validated['component_type'];
        $templateKey = $validated['template_key'];

        if (!isset($componentLibrary[$componentType]['templates'][$templateKey])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบเทมเพลตคอมโพเนนต์ที่เลือก',
            ], 404);
        }

        $componentTemplate = $componentLibrary[$componentType]['templates'][$templateKey];

        $section = PageSection::create([
            'template_id' => $template->id,
            'component_type' => $componentType,
            'name' => $validated['name'] ?? $componentTemplate['name'],
            'order' => PageSection::forTemplate($template->id)->max('order') + 1,
            'content' => $componentTemplate['content'],
            'styles' => $componentTemplate['styles'],
            'settings' => $componentTemplate['settings'] ?? [],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มส่วนสำเร็จ!',
            'section' => $section,
        ]);
    }

    /**
     * Update section in template.
     */
    public function updateSection(Request $request, $templateId, $sectionId)
    {
        $section = PageSection::where('template_id', $templateId)
            ->where('id', $sectionId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'content' => 'sometimes|array',
            'styles' => 'sometimes|array',
            'settings' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $section->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตส่วนสำเร็จ!',
            'section' => $section,
        ]);
    }

    /**
     * Delete section from template.
     */
    public function deleteSection($templateId, $sectionId)
    {
        $section = PageSection::where('template_id', $templateId)
            ->where('id', $sectionId)
            ->firstOrFail();

        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบส่วนสำเร็จ!',
        ]);
    }

    /**
     * Reorder sections in template.
     */
    public function reorderSections(Request $request, $id)
    {
        $validated = $request->validate([
            'section_ids' => 'required|array',
            'section_ids.*' => 'required|integer|exists:page_sections,id',
        ]);

        foreach ($validated['section_ids'] as $index => $sectionId) {
            PageSection::where('id', $sectionId)
                ->where('template_id', $id)
                ->update(['order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'จัดเรียงส่วนสำเร็จ!',
        ]);
    }

    /**
     * Duplicate section in template.
     */
    public function duplicateSection($templateId, $sectionId)
    {
        $section = PageSection::where('template_id', $templateId)
            ->where('id', $sectionId)
            ->firstOrFail();

        $newSection = $section->replicate();
        $newSection->name = $section->name . ' (Copy)';
        $newSection->order = PageSection::forTemplate($templateId)->max('order') + 1;
        $newSection->save();

        return response()->json([
            'success' => true,
            'message' => 'คัดลอกส่วนสำเร็จ!',
            'section' => $newSection,
        ]);
    }

    /**
     * Export template as JSON.
     */
    public function export($id)
    {
        $template = PageTemplate::with('sections')->findOrFail($id);

        $export = [
            'name' => $template->name,
            'description' => $template->description,
            'sections' => $template->sections->map(function ($section) {
                return [
                    'component_type' => $section->component_type,
                    'name' => $section->name,
                    'order' => $section->order,
                    'content' => $section->content,
                    'styles' => $section->styles,
                    'settings' => $section->settings,
                    'is_active' => $section->is_active,
                ];
            }),
        ];

        return response()->json($export);
    }

    /**
     * Import template from JSON.
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'template_data' => 'required|json',
            'name' => 'nullable|string',
        ]);

        $data = json_decode($validated['template_data'], true);

        DB::beginTransaction();
        try {
            // Create template
            $template = PageTemplate::create([
                'name' => $validated['name'] ?? $data['name'],
                'description' => $data['description'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create sections
            foreach ($data['sections'] as $sectionData) {
                PageSection::create([
                    'template_id' => $template->id,
                    ...$sectionData,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'นำเข้าเทมเพลตสำเร็จ!',
                'template' => $template->load('sections'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get component library for builder.
     */
    private function getComponentLibrary()
    {
        return [
            'hero' => [
                'name' => 'Hero Section',
                'icon' => '🎯',
                'category' => 'Header',
                'templates' => [
                    'default' => [
                        'name' => 'Default Hero',
                        'content' => [
                            'title' => 'Welcome to Our Website',
                            'subtitle' => 'Build something amazing today',
                            'cta_text' => 'Get Started',
                            'cta_link' => '#',
                        ],
                        'styles' => [
                            'background_color' => '#6366f1',
                            'text_color' => '#ffffff',
                            'padding_y' => '80',
                        ],
                        'settings' => ['animation' => 'fade-in'],
                    ],
                    'centered' => [
                        'name' => 'Centered Hero',
                        'content' => [
                            'title' => 'Start Your Journey',
                            'subtitle' => 'The best platform for your needs',
                            'cta_text' => 'Learn More',
                            'cta_link' => '#',
                        ],
                        'styles' => [
                            'background_color' => '#8b5cf6',
                            'text_color' => '#ffffff',
                            'padding_y' => '100',
                            'text_align' => 'center',
                        ],
                        'settings' => ['animation' => 'slide-up'],
                    ],
                ],
            ],
            'features' => [
                'name' => 'Features Section',
                'icon' => '⭐',
                'category' => 'Content',
                'templates' => [
                    'grid' => [
                        'name' => '3-Column Grid',
                        'content' => [
                            'title' => 'Amazing Features',
                            'features' => [
                                ['icon' => '🚀', 'title' => 'Fast', 'description' => 'Lightning fast performance'],
                                ['icon' => '🔒', 'title' => 'Secure', 'description' => 'Bank-level security'],
                                ['icon' => '💎', 'title' => 'Quality', 'description' => 'Premium quality'],
                            ],
                        ],
                        'styles' => [
                            'background_color' => '#ffffff',
                            'padding_y' => '60',
                        ],
                        'settings' => ['columns' => 3],
                    ],
                ],
            ],
            'cta' => [
                'name' => 'Call-to-Action',
                'icon' => '📢',
                'category' => 'Marketing',
                'templates' => [
                    'simple' => [
                        'name' => 'Simple CTA',
                        'content' => [
                            'title' => 'Ready to Get Started?',
                            'description' => 'Join thousands of satisfied customers',
                            'button_text' => 'Start Now',
                            'button_link' => '#',
                        ],
                        'styles' => [
                            'background_color' => '#3b82f6',
                            'text_color' => '#ffffff',
                            'padding_y' => '60',
                        ],
                        'settings' => [],
                    ],
                ],
            ],
            'testimonials' => [
                'name' => 'Testimonials',
                'icon' => '💬',
                'category' => 'Social Proof',
                'templates' => [
                    'cards' => [
                        'name' => 'Testimonial Cards',
                        'content' => [
                            'title' => 'What Our Customers Say',
                            'testimonials' => [
                                ['name' => 'John Doe', 'role' => 'CEO', 'text' => 'Excellent service!', 'avatar' => ''],
                                ['name' => 'Jane Smith', 'role' => 'Manager', 'text' => 'Highly recommend!', 'avatar' => ''],
                            ],
                        ],
                        'styles' => [
                            'background_color' => '#f3f4f6',
                            'padding_y' => '60',
                        ],
                        'settings' => [],
                    ],
                ],
            ],
        ];
    }
}
