<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\LabelPaperSize;
use App\Models\LabelTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * LabelDesignerController
 *
 * ควบคุมระบบออกแบบฉลาก บาร์โค้ด QR Code
 * สำหรับทำสติ๊กเกอร์ติดผลิตภัณฑ์
 *
 * ฟีเจอร์หลัก:
 * - ออกแบบฉลากแบบ drag & drop
 * - รองรับบาร์โค้ดหลายรูปแบบ (CODE128, EAN13, UPC-A, etc.)
 * - รองรับ QR Code
 * - รองรับกระดาษหลายขนาด
 * - Preview ก่อนพิมพ์
 * - Export เป็น PDF
 * - บันทึก template ไว้ใช้ซ้ำ
 *
 * @author Claude AI
 */
class LabelDesignerController extends Controller
{
    /**
     * แสดงหน้า Label Designer หลัก
     */
    public function index(): View
    {
        // ดึง templates ของ user และ public templates
        $myTemplates = [];
        $publicTemplates = LabelTemplate::where('is_public', true)
            ->where('is_active', true)
            ->orderBy('usage_count', 'desc')
            ->get();

        if (Auth::check()) {
            $myTemplates = LabelTemplate::where('user_id', Auth::id())
                ->where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        // ดึงขนาดกระดาษ
        $paperSizes = LabelPaperSize::active()
            ->ordered()
            ->get()
            ->groupBy('category');

        // ดึงหมวดหมู่กระดาษ
        $categories = LabelPaperSize::getCategories();

        // รายการเครื่องพิมพ์ยอดนิยม
        $printers = LabelPaperSize::getPopularPrinters();

        return view('label-designer.index', [
            'myTemplates' => $myTemplates,
            'publicTemplates' => $publicTemplates,
            'paperSizes' => $paperSizes,
            'categories' => $categories,
            'printers' => $printers,
            'pageTitle' => 'ออกแบบฉลาก & บาร์โค้ด',
        ]);
    }

    /**
     * แสดงหน้าออกแบบฉลากใหม่
     */
    public function create(Request $request): View
    {
        // ดึงขนาดกระดาษ
        $paperSizes = LabelPaperSize::active()->ordered()->get();

        // Template ที่จะใช้ (ถ้ามี)
        $template = null;
        if ($request->has('template')) {
            $template = LabelTemplate::find($request->template);
        }

        // ดึง templates สำหรับเลือก
        $templates = LabelTemplate::where(function ($q) {
            $q->where('is_public', true)
                ->orWhere('user_id', Auth::id());
        })
            ->where('is_active', true)
            ->get();

        return view('label-designer.create', [
            'paperSizes' => $paperSizes,
            'template' => $template,
            'templates' => $templates,
            'pageTitle' => 'สร้างฉลากใหม่',
        ]);
    }

    /**
     * บันทึกฉลากใหม่
     */
    public function store(Request $request): JsonResponse
    {
        // ตรวจสอบข้อมูล
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:50',
            'width' => 'required|numeric|min:10|max:500',
            'height' => 'required|numeric|min:10|max:500',
            'unit' => 'nullable|string|in:mm,cm,in',
            'elements' => 'required|array',
            'settings' => 'nullable|array',
            'print_settings' => 'nullable|array',
            'template_id' => 'nullable|exists:label_templates,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        // สร้างฉลาก
        $label = Label::create([
            'user_id' => Auth::id(),
            'template_id' => $request->template_id,
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'width' => $request->width,
            'height' => $request->height,
            'unit' => $request->unit ?? 'mm',
            'elements' => $request->elements,
            'data_fields' => $request->data_fields,
            'settings' => $request->settings,
            'print_settings' => $request->print_settings,
        ]);

        // เพิ่มจำนวนการใช้งาน template
        if ($request->template_id) {
            $template = LabelTemplate::find($request->template_id);
            if ($template) {
                $template->incrementUsage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกฉลากสำเร็จ',
            'data' => $label,
        ]);
    }

    /**
     * แสดงฉลากสำหรับแก้ไข
     */
    public function edit(int $id): View
    {
        $label = Label::where('user_id', Auth::id())
            ->findOrFail($id);

        $paperSizes = LabelPaperSize::active()->ordered()->get();

        $templates = LabelTemplate::where(function ($q) {
            $q->where('is_public', true)
                ->orWhere('user_id', Auth::id());
        })
            ->where('is_active', true)
            ->get();

        return view('label-designer.edit', [
            'label' => $label,
            'paperSizes' => $paperSizes,
            'templates' => $templates,
            'pageTitle' => 'แก้ไขฉลาก: '.$label->name,
        ]);
    }

    /**
     * อัปเดตฉลาก
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $label = Label::where('user_id', Auth::id())
            ->findOrFail($id);

        // ตรวจสอบข้อมูล
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:50',
            'width' => 'required|numeric|min:10|max:500',
            'height' => 'required|numeric|min:10|max:500',
            'elements' => 'required|array',
            'settings' => 'nullable|array',
            'print_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        // อัปเดตฉลาก
        $label->update([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'width' => $request->width,
            'height' => $request->height,
            'elements' => $request->elements,
            'data_fields' => $request->data_fields,
            'settings' => $request->settings,
            'print_settings' => $request->print_settings,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตฉลากสำเร็จ',
            'data' => $label->fresh(),
        ]);
    }

    /**
     * ลบฉลาก
     */
    public function destroy(int $id): JsonResponse
    {
        $label = Label::where('user_id', Auth::id())
            ->findOrFail($id);

        $label->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบฉลากสำเร็จ',
        ]);
    }

    /**
     * สลับสถานะรายการโปรด
     */
    public function toggleFavorite(int $id): JsonResponse
    {
        $label = Label::where('user_id', Auth::id())
            ->findOrFail($id);

        $isFavorite = $label->toggleFavorite();

        return response()->json([
            'success' => true,
            'message' => $isFavorite ? 'เพิ่มในรายการโปรด' : 'ลบออกจากรายการโปรด',
            'is_favorite' => $isFavorite,
        ]);
    }

    /**
     * โคลนฉลาก
     */
    public function duplicate(int $id): JsonResponse
    {
        $label = Label::where('user_id', Auth::id())
            ->findOrFail($id);

        $newLabel = $label->duplicate();

        return response()->json([
            'success' => true,
            'message' => 'คัดลอกฉลากสำเร็จ',
            'data' => $newLabel,
        ]);
    }

    /**
     * ดึงรายการฉลากของ user
     */
    public function myLabels(Request $request): JsonResponse
    {
        $query = Label::where('user_id', Auth::id());

        // กรองตามหมวดหมู่
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // กรองเฉพาะรายการโปรด
        if ($request->boolean('favorites')) {
            $query->favorites();
        }

        // ค้นหา
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort', 'updated_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $labels = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $labels,
        ]);
    }

    /**
     * ดึงรายการ templates
     */
    public function templates(Request $request): JsonResponse
    {
        $query = LabelTemplate::where('is_active', true);

        // กรองตาม type
        if ($request->get('type') === 'my') {
            $query->where('user_id', Auth::id());
        } elseif ($request->get('type') === 'public') {
            $query->where('is_public', true);
        } elseif ($request->get('type') === 'system') {
            $query->where('is_system', true);
        } else {
            // แสดงทั้ง public และของ user
            $query->where(function ($q) {
                $q->where('is_public', true)
                    ->orWhere('user_id', Auth::id());
            });
        }

        // กรองตามหมวดหมู่
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // ค้นหา
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort', 'usage_count');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $templates = $query->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * ดึงรายการขนาดกระดาษ
     */
    public function paperSizes(Request $request): JsonResponse
    {
        $query = LabelPaperSize::active();

        // กรองตามหมวดหมู่
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $paperSizes = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $paperSizes,
            'categories' => LabelPaperSize::getCategories(),
        ]);
    }

    /**
     * สร้างบาร์โค้ด
     */
    public function generateBarcode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:500',
            'format' => 'required|string|in:CODE128,CODE39,EAN13,EAN8,UPC-A,UPC-E,ITF,CODABAR,PHARMACODE',
            'width' => 'nullable|integer|min:1|max:10',
            'height' => 'nullable|integer|min:20|max:200',
            'color' => 'nullable|string',
            'background' => 'nullable|string',
            'show_text' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        // สร้าง barcode config
        $config = [
            'content' => $request->content,
            'format' => $request->format,
            'width' => $request->get('width', 2),
            'height' => $request->get('height', 50),
            'color' => $request->get('color', '#000000'),
            'background' => $request->get('background', '#ffffff'),
            'showText' => $request->boolean('show_text', true),
        ];

        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }

    /**
     * สร้าง QR Code
     */
    public function generateQrcode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:2048',
            'size' => 'nullable|integer|min:50|max:1000',
            'color' => 'nullable|string',
            'background' => 'nullable|string',
            'error_correction' => 'nullable|string|in:L,M,Q,H',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        // สร้าง QR config
        $config = [
            'content' => $request->content,
            'size' => $request->get('size', 200),
            'color' => $request->get('color', '#000000'),
            'background' => $request->get('background', '#ffffff'),
            'errorCorrectionLevel' => $request->get('error_correction', 'M'),
        ];

        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }

    /**
     * บันทึกการพิมพ์
     */
    public function recordPrint(Request $request, int $id): JsonResponse
    {
        $label = Label::where('user_id', Auth::id())
            ->findOrFail($id);

        $copies = $request->get('copies', 1);
        $label->recordPrint($copies);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการพิมพ์สำเร็จ',
            'print_count' => $label->print_count,
        ]);
    }

    /**
     * Export ฉลากเป็น PDF
     *
     * @return mixed
     */
    public function exportPdf(Request $request, int $id)
    {
        $label = Label::where('user_id', Auth::id())
            ->findOrFail($id);

        // TODO: Generate PDF using dompdf or similar library
        // สำหรับตอนนี้ ส่งคืน JSON config สำหรับ client-side PDF generation

        return response()->json([
            'success' => true,
            'label' => $label,
            'message' => 'กรุณาใช้ฟังก์ชัน Print ใน browser เพื่อ export เป็น PDF',
        ]);
    }

    /**
     * ดึงรายการรูปแบบบาร์โค้ดที่รองรับ
     */
    public function barcodeFormats(): JsonResponse
    {
        $formats = [
            [
                'code' => 'CODE128',
                'name' => 'Code 128',
                'description' => 'บาร์โค้ดมาตรฐาน รองรับตัวอักษรและตัวเลข',
                'characters' => 'ASCII 0-127',
                'length' => 'ไม่จำกัด',
            ],
            [
                'code' => 'CODE39',
                'name' => 'Code 39',
                'description' => 'รองรับตัวอักษร A-Z, ตัวเลข 0-9, และบางอักขระพิเศษ',
                'characters' => 'A-Z, 0-9, -.$/+% และ space',
                'length' => 'ไม่จำกัด',
            ],
            [
                'code' => 'EAN13',
                'name' => 'EAN-13',
                'description' => 'บาร์โค้ดสินค้ามาตรฐานยุโรป 13 หลัก',
                'characters' => '0-9',
                'length' => '12 หลัก (+ 1 check digit)',
            ],
            [
                'code' => 'EAN8',
                'name' => 'EAN-8',
                'description' => 'บาร์โค้ดสินค้าขนาดเล็ก 8 หลัก',
                'characters' => '0-9',
                'length' => '7 หลัก (+ 1 check digit)',
            ],
            [
                'code' => 'UPC-A',
                'name' => 'UPC-A',
                'description' => 'บาร์โค้ดสินค้ามาตรฐานอเมริกา 12 หลัก',
                'characters' => '0-9',
                'length' => '11 หลัก (+ 1 check digit)',
            ],
            [
                'code' => 'UPC-E',
                'name' => 'UPC-E',
                'description' => 'บาร์โค้ดสินค้าขนาดเล็ก 6 หลัก',
                'characters' => '0-9',
                'length' => '6 หลัก',
            ],
            [
                'code' => 'ITF',
                'name' => 'ITF-14',
                'description' => 'Interleaved 2 of 5 สำหรับกล่องบรรจุภัณฑ์',
                'characters' => '0-9',
                'length' => 'จำนวนคู่',
            ],
            [
                'code' => 'CODABAR',
                'name' => 'Codabar',
                'description' => 'ใช้ในห้องสมุดและธนาคารเลือด',
                'characters' => '0-9, -$:/.+',
                'length' => 'ไม่จำกัด',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $formats,
        ]);
    }

    /**
     * สร้าง template จากฉลากที่มีอยู่
     */
    public function createTemplateFromLabel(Request $request, int $labelId): JsonResponse
    {
        $label = Label::where('user_id', Auth::id())
            ->findOrFail($labelId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        // สร้าง template
        $template = LabelTemplate::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'category' => $label->category,
            'paper_width' => $label->width,
            'paper_height' => $label->height,
            'paper_unit' => $label->unit,
            'elements' => $label->elements,
            'settings' => $label->settings,
            'print_settings' => $label->print_settings,
            'is_public' => $request->boolean('is_public', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้าง template สำเร็จ',
            'data' => $template,
        ]);
    }
}
