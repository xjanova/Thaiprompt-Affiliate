<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageElement;
use App\Models\HomepageSection;
use App\Models\HomepageTemplate;
use App\Services\HomepageManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * HomepageManagerController
 *
 * จัดการระบบ Homepage Manager ในส่วน Admin
 * รองรับการจัดการ sections, elements และ templates
 * พร้อม Visual Editor แบบ Drag & Drop
 */
class HomepageManagerController extends Controller
{
    /**
     * @var HomepageManagerService
     */
    protected HomepageManagerService $service;

    /**
     * สร้าง controller instance
     *
     * @param HomepageManagerService $service
     */
    public function __construct(HomepageManagerService $service)
    {
        $this->service = $service;
    }

    /**
     * แสดงหน้า Visual Editor สำหรับจัดการหน้าแรก
     *
     * @return View
     */
    public function index(): View
    {
        // ดึง sections ทั้งหมดพร้อม elements
        $sections = HomepageSection::with('activeElements')
            ->active()
            ->ordered()
            ->get();

        // ดึง templates ที่ใช้ได้
        $templates = HomepageTemplate::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // ดึงประเภท section และ element
        $sectionTypes = HomepageSection::TYPES;
        $elementTypes = HomepageElement::TYPES;
        $backgroundTypes = HomepageSection::BACKGROUND_TYPES;
        $animations = HomepageSection::ANIMATIONS;
        $templateCategories = HomepageTemplate::CATEGORIES;

        return view('admin.homepage-manager.index', [
            'sections' => $sections,
            'templates' => $templates,
            'sectionTypes' => $sectionTypes,
            'elementTypes' => $elementTypes,
            'backgroundTypes' => $backgroundTypes,
            'animations' => $animations,
            'templateCategories' => $templateCategories,
            'pageTitle' => 'จัดการหน้าแรก',
        ]);
    }

    /**
     * ดึงข้อมูล sections ทั้งหมดในรูปแบบ JSON
     *
     * @return JsonResponse
     */
    public function getSections(): JsonResponse
    {
        try {
            $sections = HomepageSection::with('elements')
                ->ordered()
                ->get()
                ->map->toApiArray();

            return response()->json([
                'success' => true,
                'data' => $sections,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * สร้าง section ใหม่
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeSection(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(HomepageSection::TYPES)),
            'is_active' => 'boolean',
            'is_fullwidth' => 'boolean',
            'min_height' => 'nullable|string|max:50',
            'max_height' => 'nullable|string|max:50',
            'padding' => 'nullable|string|max:100',
            'background_type' => 'nullable|string|in:color,gradient,image,video',
            'background_color' => 'nullable|string|max:50',
            'background_color_dark' => 'nullable|string|max:50',
            'background_gradient' => 'nullable|string|max:500',
            'background_image' => 'nullable|string|max:500',
            'background_video' => 'nullable|string|max:500',
            'background_overlay' => 'nullable|string|max:100',
            'background_position' => 'nullable|string|max:50',
            'background_size' => 'nullable|string|max:50',
            'background_fixed' => 'boolean',
            'animation' => 'nullable|string|max:50',
            'animation_delay' => 'nullable|integer|min:0',
            'settings' => 'nullable|array',
            'responsive_settings' => 'nullable|array',
            // ตำแหน่งแทรก section
            'insert_position' => 'nullable|string|in:first,last,after',
            'insert_after_id' => 'nullable|integer|exists:homepage_sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->except(['insert_position', 'insert_after_id']);
            $insertPosition = $request->input('insert_position', 'last');
            $insertAfterId = $request->input('insert_after_id');

            // คำนวณ order ตามตำแหน่งที่เลือก
            if ($insertPosition === 'first') {
                // แทรกด้านบนสุด - เลื่อน order ของ sections อื่นทั้งหมด
                HomepageSection::query()->increment('order');
                $data['order'] = 0;
            } elseif ($insertPosition === 'after' && $insertAfterId) {
                // แทรกหลัง section ที่ระบุ
                $afterSection = HomepageSection::find($insertAfterId);
                if ($afterSection) {
                    $newOrder = $afterSection->order + 1;
                    // เลื่อน order ของ sections ที่มี order >= newOrder
                    HomepageSection::where('order', '>=', $newOrder)->increment('order');
                    $data['order'] = $newOrder;
                } else {
                    $data['order'] = (HomepageSection::max('order') ?? -1) + 1;
                }
            } else {
                // แทรกท้ายสุด (default)
                $data['order'] = (HomepageSection::max('order') ?? -1) + 1;
            }

            $section = HomepageSection::create($data);

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'สร้าง Section สำเร็จ',
                'data' => $section->toApiArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดท section
     *
     * @param Request $request
     * @param HomepageSection $section
     * @return JsonResponse
     */
    public function updateSection(Request $request, HomepageSection $section): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:' . implode(',', array_keys(HomepageSection::TYPES)),
            'is_active' => 'boolean',
            'is_fullwidth' => 'boolean',
            'min_height' => 'nullable|string|max:50',
            'max_height' => 'nullable|string|max:50',
            'padding' => 'nullable|string|max:100',
            'background_type' => 'nullable|string|in:color,gradient,image,video',
            'background_color' => 'nullable|string|max:50',
            'background_color_dark' => 'nullable|string|max:50',
            'background_gradient' => 'nullable|string|max:500',
            'background_image' => 'nullable|string|max:500',
            'background_video' => 'nullable|string|max:500',
            'background_overlay' => 'nullable|string|max:100',
            'background_position' => 'nullable|string|max:50',
            'background_size' => 'nullable|string|max:50',
            'background_fixed' => 'boolean',
            'animation' => 'nullable|string|max:50',
            'animation_delay' => 'nullable|integer|min:0',
            'settings' => 'nullable|array',
            'responsive_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $section->update($request->all());

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'อัพเดท Section สำเร็จ',
                'data' => $section->fresh()->toApiArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบ section
     *
     * @param HomepageSection $section
     * @return JsonResponse
     */
    public function destroySection(HomepageSection $section): JsonResponse
    {
        try {
            // ลบ elements ใน section ก่อน
            $section->elements()->delete();

            // ลบ section
            $section->delete();

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'ลบ Section สำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * จัดเรียงลำดับ sections
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reorderSections(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sections' => 'required|array',
            'sections.*' => 'integer|exists:homepage_sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->sections as $order => $sectionId) {
                    HomepageSection::where('id', $sectionId)->update(['order' => $order]);
                }
            });

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'จัดเรียงลำดับสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle สถานะ active ของ section
     *
     * @param HomepageSection $section
     * @return JsonResponse
     */
    public function toggleSection(HomepageSection $section): JsonResponse
    {
        try {
            $section->update(['is_active' => !$section->is_active]);

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => $section->is_active ? 'เปิดใช้งาน Section' : 'ปิดใช้งาน Section',
                'is_active' => $section->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate section พร้อม elements
     *
     * @param HomepageSection $section
     * @return JsonResponse
     */
    public function duplicateSection(HomepageSection $section): JsonResponse
    {
        try {
            $newSection = DB::transaction(function () use ($section) {
                // สร้าง section ใหม่
                $newSection = $section->replicate();
                $newSection->name = $section->name . ' (สำเนา)';
                $newSection->order = HomepageSection::max('order') + 1;
                $newSection->save();

                // สำเนา elements
                foreach ($section->elements as $element) {
                    $newElement = $element->replicate();
                    $newElement->homepage_section_id = $newSection->id;
                    $newElement->save();
                }

                return $newSection;
            });

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'ทำสำเนา Section สำเร็จ',
                'data' => $newSection->fresh()->load('elements')->toApiArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===============================
    // Element Management
    // ===============================

    /**
     * สร้าง element ใหม่
     *
     * @param Request $request
     * @param HomepageSection $section
     * @return JsonResponse
     */
    public function storeElement(Request $request, HomepageSection $section): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(HomepageElement::TYPES)),
            'is_active' => 'boolean',
            'position_type' => 'nullable|string|in:relative,absolute',
            'position_x' => 'nullable|numeric',
            'position_y' => 'nullable|numeric',
            'position_x_unit' => 'nullable|string|in:%,px',
            'position_y_unit' => 'nullable|string|in:%,px',
            'anchor_x' => 'nullable|string|in:left,center,right',
            'anchor_y' => 'nullable|string|in:top,center,bottom',
            'width' => 'nullable|string|max:50',
            'height' => 'nullable|string|max:50',
            'max_width' => 'nullable|string|max:50',
            'content' => 'nullable|string',
            'content_en' => 'nullable|string',
            'image_url' => 'nullable|string|max:500',
            'image_url_dark' => 'nullable|string|max:500',
            'video_url' => 'nullable|string|max:500',
            'link_url' => 'nullable|string|max:500',
            'link_target' => 'nullable|string|in:_self,_blank',
            'icon_class' => 'nullable|string|max:100',
            'font_family' => 'nullable|string|max:100',
            'font_size' => 'nullable|string|max:50',
            'font_weight' => 'nullable|string|max:50',
            'line_height' => 'nullable|string|max:50',
            'letter_spacing' => 'nullable|string|max:50',
            'text_align' => 'nullable|string|in:left,center,right,justify',
            'text_transform' => 'nullable|string|in:none,uppercase,lowercase,capitalize',
            'color' => 'nullable|string|max:50',
            'color_dark' => 'nullable|string|max:50',
            'background_color' => 'nullable|string|max:50',
            'background_color_dark' => 'nullable|string|max:50',
            'border_color' => 'nullable|string|max:50',
            'border_color_dark' => 'nullable|string|max:50',
            'border_width' => 'nullable|string|max:50',
            'border_style' => 'nullable|string|max:50',
            'border_radius' => 'nullable|string|max:50',
            'padding' => 'nullable|string|max:100',
            'margin' => 'nullable|string|max:100',
            'box_shadow' => 'nullable|string|max:200',
            'text_shadow' => 'nullable|string|max:200',
            'animation' => 'nullable|string|max:50',
            'animation_delay' => 'nullable|integer|min:0',
            'animation_duration' => 'nullable|integer|min:0',
            'hover_effects' => 'nullable|array',
            'responsive_settings' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // หาลำดับสูงสุดแล้ว +1
            $maxOrder = $section->elements()->max('order') ?? -1;
            $data = $request->all();
            $data['order'] = $maxOrder + 1;

            $element = $section->elements()->create($data);

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'สร้าง Element สำเร็จ',
                'data' => $element->toApiArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดท element
     *
     * @param Request $request
     * @param HomepageElement $element
     * @return JsonResponse
     */
    public function updateElement(Request $request, HomepageElement $element): JsonResponse
    {
        try {
            $element->update($request->all());

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'อัพเดท Element สำเร็จ',
                'data' => $element->fresh()->toApiArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบ element
     *
     * @param HomepageElement $element
     * @return JsonResponse
     */
    public function destroyElement(HomepageElement $element): JsonResponse
    {
        try {
            $element->delete();

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'ลบ Element สำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate element
     *
     * @param HomepageElement $element
     * @return JsonResponse
     */
    public function duplicateElement(HomepageElement $element): JsonResponse
    {
        try {
            $newElement = $element->replicate();
            $newElement->name = ($element->name ?? 'Element') . ' (สำเนา)';
            $newElement->order = $element->section->elements()->max('order') + 1;

            // Offset position slightly
            if ($newElement->position_type === 'absolute') {
                $newElement->position_x += 2;
                $newElement->position_y += 2;
            }

            $newElement->save();

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'ทำสำเนา Element สำเร็จ',
                'data' => $newElement->toApiArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * จัดเรียงลำดับ elements (z-index)
     *
     * @param Request $request
     * @param HomepageSection $section
     * @return JsonResponse
     */
    public function reorderElements(Request $request, HomepageSection $section): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'elements' => 'required|array',
            'elements.*' => 'integer|exists:homepage_elements,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $section) {
                foreach ($request->elements as $order => $elementId) {
                    HomepageElement::where('id', $elementId)
                        ->where('homepage_section_id', $section->id)
                        ->update(['order' => $order]);
                }
            });

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'จัดเรียงลำดับสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===============================
    // Template Management
    // ===============================

    /**
     * ดึง templates ทั้งหมด
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTemplates(Request $request): JsonResponse
    {
        try {
            $query = HomepageTemplate::active();

            // Filter by category
            if ($request->has('category') && $request->category !== 'all') {
                $query->ofCategory($request->category);
            }

            $templates = $query->orderBy('name')->get()->map->toApiArray();

            return response()->json([
                'success' => true,
                'data' => $templates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * นำเข้าเทมเพลตไปยังหน้าแรก
     *
     * @param HomepageTemplate $template
     * @return JsonResponse
     */
    public function importTemplate(HomepageTemplate $template): JsonResponse
    {
        try {
            $sections = $template->importToHomepage();

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'นำเข้าเทมเพลตสำเร็จ',
                'sections_count' => $sections->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * บันทึกหน้าแรกปัจจุบันเป็นเทมเพลต
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveAsTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(HomepageTemplate::CATEGORIES)),
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // ดึงข้อมูล sections และ elements ปัจจุบัน
            $sections = HomepageSection::with('elements')
                ->ordered()
                ->get()
                ->map(function ($section) {
                    $sectionData = $section->toArray();
                    $sectionData['elements'] = $section->elements->map(function ($element) {
                        return $element->toArray();
                    })->toArray();
                    return $sectionData;
                })
                ->toArray();

            // สร้างเทมเพลต
            $template = HomepageTemplate::create([
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
                'sections_data' => $sections,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'บันทึกเทมเพลตสำเร็จ',
                'data' => $template->toApiArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===============================
    // Media Management
    // ===============================

    /**
     * อัพโหลดรูปภาพ
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ไฟล์ไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('image');
            $path = $file->store('homepage-manager', 'public');

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดรูปภาพสำเร็จ',
                'url' => Storage::url($path),
                'path' => $path,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===============================
    // Export/Import
    // ===============================

    /**
     * Export หน้าแรกเป็น JSON
     *
     * @return JsonResponse
     */
    public function export(): JsonResponse
    {
        try {
            $data = $this->service->exportHomepage();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import หน้าแรกจาก JSON
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'replace' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->service->importHomepage(
                $request->data,
                $request->boolean('replace', false)
            );

            return response()->json([
                'success' => true,
                'message' => 'นำเข้าข้อมูลสำเร็จ',
                'sections_count' => $result['sections_count'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview หน้าแรก
     *
     * @return View
     */
    public function preview(): View
    {
        $sections = HomepageSection::with('activeElements')
            ->active()
            ->ordered()
            ->get();

        return view('admin.homepage-manager.preview', [
            'sections' => $sections,
        ]);
    }

    /**
     * แสดงหน้า Templates Gallery
     *
     * แสดง templates ทั้งหมดในรูปแบบ Gallery พร้อม filter และ search
     *
     * @return View
     */
    public function templatesIndex(): View
    {
        // ดึง templates ทั้งหมดที่ active
        $templates = HomepageTemplate::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // ดึงหมวดหมู่สำหรับ filter
        $categories = HomepageTemplate::CATEGORIES;

        return view('admin.homepage-manager.templates', [
            'templates' => $templates,
            'categories' => $categories,
            'pageTitle' => 'คลัง Templates หน้าแรก',
        ]);
    }

    /**
     * แสดงหน้าจัดการ Sections
     *
     * แสดง sections ทั้งหมดในรูปแบบตาราง พร้อม filter, search และจัดเรียงลำดับ
     *
     * @return View
     */
    public function sectionsIndex(): View
    {
        // ดึงประเภท section
        $sectionTypes = HomepageSection::TYPES;

        // ดึงประเภท animations
        $animations = HomepageSection::ANIMATIONS;

        return view('admin.homepage-manager.sections', [
            'sectionTypes' => $sectionTypes,
            'animations' => $animations,
            'pageTitle' => 'จัดการ Sections หน้าแรก',
        ]);
    }

    /**
     * ดึงข้อมูล sections ของ template สำหรับ preview
     *
     * @param HomepageTemplate $template
     * @return JsonResponse
     */
    public function previewTemplate(HomepageTemplate $template): JsonResponse
    {
        try {
            $sectionsData = $template->getSectionsData();

            return response()->json([
                'success' => true,
                'sections' => $sectionsData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * บันทึก sections ทั้งหมดพร้อมกัน
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveAll(Request $request): JsonResponse
    {
        try {
            $sectionsData = $request->input('sections', []);

            DB::transaction(function () use ($sectionsData) {
                foreach ($sectionsData as $index => $sectionData) {
                    $section = HomepageSection::find($sectionData['id'] ?? null);

                    if ($section) {
                        // อัพเดท section ที่มีอยู่
                        $section->update([
                            'title' => $sectionData['title'] ?? $section->title,
                            'type' => $sectionData['type'] ?? $section->type,
                            'settings' => $sectionData['settings'] ?? $section->settings,
                            'is_active' => $sectionData['is_active'] ?? $section->is_active,
                            'order' => $index,
                        ]);

                        // อัพเดท elements ของ section
                        if (isset($sectionData['elements']) && is_array($sectionData['elements'])) {
                            foreach ($sectionData['elements'] as $elementIndex => $elementData) {
                                $element = HomepageElement::find($elementData['id'] ?? null);

                                if ($element) {
                                    $element->update([
                                        'type' => $elementData['type'] ?? $element->type,
                                        'content' => $elementData['content'] ?? $element->content,
                                        'settings' => $elementData['settings'] ?? $element->settings,
                                        'order' => $elementIndex,
                                    ]);
                                }
                            }
                        }
                    }
                }
            });

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'บันทึกข้อมูลหน้าแรกสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ล้างหน้าแรกทั้งหมด
     *
     * @return JsonResponse
     */
    public function clearAll(): JsonResponse
    {
        try {
            DB::transaction(function () {
                HomepageElement::query()->delete();
                HomepageSection::query()->delete();
            });

            // Clear cache
            $this->service->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'ล้างข้อมูลหน้าแรกสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }
}
