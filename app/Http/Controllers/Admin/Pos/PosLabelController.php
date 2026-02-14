<?php

namespace App\Http\Controllers\Admin\Pos;

use App\Http\Controllers\Controller;
use App\Models\LabelPaperSize;
use App\Models\LabelTemplate;
use App\Models\PosLabelPrint;
use App\Models\PosTransaction;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * PosLabelController
 *
 * จัดการระบบพิมพ์ฉลากสำหรับ POS
 * รองรับทั้ง Product Labels และ Shipping Labels
 *
 * @author Claude AI
 */
class PosLabelController extends Controller
{
    /**
     * แสดงหน้า Dashboard ของ Label Printing
     */
    public function index(): View
    {
        // สรุปสถิติการพิมพ์
        $stats = [
            'total_prints' => PosLabelPrint::count(),
            'today_prints' => PosLabelPrint::whereDate('created_at', today())->count(),
            'this_month_prints' => PosLabelPrint::whereMonth('created_at', now()->month)->count(),
            'total_labels' => PosLabelPrint::sum('total_labels'),
        ];

        // ประวัติการพิมพ์ล่าสุด
        $recentPrints = PosLabelPrint::with(['user', 'template', 'transaction'])
            ->latest()
            ->limit(10)
            ->get();

        // Template ยอดนิยม
        $popularTemplates = LabelTemplate::posTemplates()
            ->active()
            ->popular(5)
            ->get();

        return view('admin.pos.labels.index', compact(
            'stats',
            'recentPrints',
            'popularTemplates'
        ));
    }

    /**
     * แสดงหน้าพิมพ์ Product Label
     */
    public function printProductLabels(): View
    {
        // ดึง templates สำหรับ product label
        $templates = LabelTemplate::posTemplates()
            ->productLabels()
            ->active()
            ->get();

        // ดึง paper sizes
        $paperSizes = LabelPaperSize::where('is_active', true)
            ->whereIn('category', ['label', 'standard', 'thermal'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.pos.labels.print-product', compact('templates', 'paperSizes'));
    }

    /**
     * แสดงหน้าพิมพ์ Shipping Label
     */
    public function printShippingLabel(?PosTransaction $transaction = null): View
    {
        // ดึง templates สำหรับ shipping label
        $templates = LabelTemplate::posTemplates()
            ->shippingLabels()
            ->active()
            ->get();

        // ดึง paper sizes
        $paperSizes = LabelPaperSize::where('is_active', true)
            ->whereIn('category', ['label', 'standard'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.pos.labels.print-shipping', compact(
            'templates',
            'paperSizes',
            'transaction'
        ));
    }

    /**
     * ดึงรายการสินค้าทั้งหมดพร้อม pagination (Admin)
     */
    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::where('is_active', true)
            ->with('store');

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->input('per_page', 20);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode_or_sku,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'image' => $product->image_url ?? $product->main_image_url,
                    'store_name' => $product->store->store_name ?? null,
                ];
            }),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * ค้นหาสินค้าสำหรับพิมพ์ฉลาก (Admin เห็นทั้งหมด)
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        $products = Product::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('barcode', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%");
        })
            ->where('is_active', true)
            ->with('store') // โหลด relationship store
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode_or_sku, // Fallback to SKU if barcode is null
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'image' => $product->image_url ?? $product->main_image_url,
                    'store_name' => $product->store->store_name ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Preview ก่อนพิมพ์
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:label_templates,id',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $template = LabelTemplate::find($validated['template_id']);

        // คำนวณจำนวนฉลากทั้งหมด
        $totalLabels = collect($validated['products'])->sum('quantity');

        // คำนวณจำนวนแผ่น (ถ้าไม่ใช่ continuous roll)
        $sheetsCount = 1;
        if (! $template->is_continuous_roll) {
            $labelsPerSheet = $template->labels_per_sheet;
            $sheetsCount = ceil($totalLabels / $labelsPerSheet);
        }

        // สร้าง preview data
        $previewData = [
            'template' => [
                'name' => $template->name,
                'paper_width' => $template->paper_width,
                'paper_height' => $template->paper_height,
                'labels_per_sheet' => $template->labels_per_sheet,
                'is_continuous_roll' => $template->is_continuous_roll,
            ],
            'total_labels' => $totalLabels,
            'sheets_count' => $sheetsCount,
            'products' => $validated['products'],
        ];

        return response()->json([
            'success' => true,
            'data' => $previewData,
        ]);
    }

    /**
     * บันทึกและพิมพ์ฉลาก
     */
    public function print(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:label_templates,id',
            'print_type' => 'required|in:product_label,shipping_label,price_tag,barcode_only,custom',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'paper_size' => 'nullable|string',
            'print_settings' => 'nullable|array',
            'printer_name' => 'nullable|string',
            'printer_type' => 'nullable|in:thermal_roll,thermal_label,inkjet,laser',
        ]);

        DB::beginTransaction();
        try {
            $template = LabelTemplate::find($validated['template_id']);

            // เตรียมข้อมูลสินค้า
            $productsData = [];
            $totalLabels = 0;

            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $quantity = $item['quantity'];
                $totalLabels += $quantity;

                $productsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'barcode' => $product->barcode_or_sku, // Fallback to SKU
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'quantity' => $quantity,
                ];
            }

            // คำนวณจำนวนแผ่น
            $sheetsCount = 1;
            if (! $template->is_continuous_roll) {
                $sheetsCount = ceil($totalLabels / $template->labels_per_sheet);
            }

            // สร้าง print record
            $print = PosLabelPrint::create([
                'user_id' => auth()->id(),
                'template_id' => $template->id,
                'print_type' => $validated['print_type'],
                'products' => $productsData,
                'total_labels' => $totalLabels,
                'sheets_count' => $sheetsCount,
                'paper_size' => $validated['paper_size'] ?? null,
                'paper_width' => $template->paper_width,
                'paper_height' => $template->paper_height,
                'print_settings' => $validated['print_settings'] ?? null,
                'printer_name' => $validated['printer_name'] ?? null,
                'printer_type' => $validated['printer_type'] ?? $template->printer_type,
                'status' => 'completed',
                'printed_at' => now(),
            ]);

            // เพิ่มจำนวนการใช้งาน template
            $template->incrementUsage();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการพิมพ์สำเร็จ',
                'data' => [
                    'print_id' => $print->id,
                    'print_session_id' => $print->print_session_id,
                    'total_labels' => $totalLabels,
                    'sheets_count' => $sheetsCount,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * พิมพ์ Shipping Label จาก Transaction
     */
    public function printShippingFromTransaction(Request $request, PosTransaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:label_templates,id',
            'paper_size' => 'nullable|string',
            'print_settings' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $template = LabelTemplate::find($validated['template_id']);

            // ดึงข้อมูลจาก transaction
            $transactionItems = $transaction->items()
                ->with('product')
                ->get()
                ->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'barcode' => $item->product_barcode ?? ($item->product->barcode_or_sku ?? ''),
                        'sku' => $item->product->sku ?? '',
                        'price' => $item->unit_price,
                        'quantity' => $item->quantity,
                    ];
                })
                ->toArray();

            // สร้าง print record
            $print = PosLabelPrint::create([
                'user_id' => auth()->id(),
                'pos_transaction_id' => $transaction->id,
                'template_id' => $template->id,
                'print_type' => 'shipping_label',
                'products' => $transactionItems,
                'total_labels' => 1, // shipping label ปกติพิมพ์ 1 ใบ
                'sheets_count' => 1,
                'paper_size' => $validated['paper_size'] ?? null,
                'paper_width' => $template->paper_width,
                'paper_height' => $template->paper_height,
                'print_settings' => $validated['print_settings'] ?? null,
                'status' => 'completed',
                'printed_at' => now(),
            ]);

            // เพิ่มจำนวนการใช้งาน template
            $template->incrementUsage();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'พิมพ์ใบปะสินค้าสำเร็จ',
                'data' => [
                    'print_id' => $print->id,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงรายการ Templates
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $posCategory = $request->input('pos_category');
        $printerType = $request->input('printer_type');

        $query = LabelTemplate::posTemplates()->active();

        if ($posCategory) {
            $query->forPosCategory($posCategory);
        }

        if ($printerType) {
            $query->forPrinterType($printerType);
        }

        $templates = $query->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * ดึงรายการ Paper Sizes
     */
    public function getPaperSizes(Request $request): JsonResponse
    {
        $category = $request->input('category');

        $query = LabelPaperSize::where('is_active', true);

        if ($category) {
            $query->where('category', $category);
        }

        $paperSizes = $query->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $paperSizes,
        ]);
    }

    /**
     * แสดงหน้า Preview สำหรับพิมพ์ฉลาก
     */
    public function showPreview(Request $request): View
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:label_templates,id',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $template = LabelTemplate::find($validated['template_id']);

        // เตรียมข้อมูลสินค้า
        $labelsData = [];
        $totalLabels = 0;

        foreach ($validated['products'] as $item) {
            $product = Product::find($item['product_id']);
            $quantity = $item['quantity'];
            $totalLabels += $quantity;

            // สร้างฉลากตามจำนวนที่ต้องการ
            for ($i = 0; $i < $quantity; $i++) {
                $labelsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'barcode' => $product->barcode ?? $product->sku,
                    'sku' => $product->sku,
                    'price' => number_format($product->price, 2),
                    'price_raw' => $product->price,
                    'image' => $product->image_url ?? $product->main_image_url,
                ];
            }
        }

        // คำนวณจำนวนแผ่น
        $sheetsCount = 1;
        if (! $template->is_continuous_roll) {
            $labelsPerSheet = $template->labels_per_sheet;
            $sheetsCount = ceil($totalLabels / $labelsPerSheet);
        }

        return view('admin.pos.labels.preview', compact(
            'template',
            'labelsData',
            'totalLabels',
            'sheetsCount'
        ));
    }

    /**
     * ดึงประวัติการพิมพ์
     */
    public function history(Request $request): View
    {
        $query = PosLabelPrint::with(['user', 'template', 'transaction'])
            ->latest();

        // Filter by type
        if ($request->filled('print_type')) {
            $query->ofType($request->print_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->ofStatus($request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $prints = $query->paginate(20);

        return view('admin.pos.labels.history', compact('prints'));
    }

    /**
     * ดูรายละเอียดการพิมพ์
     */
    public function show(PosLabelPrint $print): View
    {
        $print->load(['user', 'template', 'transaction', 'device']);

        return view('admin.pos.labels.show', compact('print'));
    }

    /**
     * ลบรายการพิมพ์
     */
    public function destroy(PosLabelPrint $print): RedirectResponse
    {
        $print->delete();

        return redirect()
            ->route('admin.pos.labels.history')
            ->with('success', 'ลบรายการสำเร็จ');
    }

    /**
     * สร้าง Batch Print Session
     */
    public function createBatchSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $sessionId = 'BATCH-'.Str::upper(Str::random(10));

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $sessionId,
                'total_items' => count($validated['items']),
            ],
        ]);
    }

    // ========================================
    // TEMPLATE MANAGEMENT METHODS
    // ========================================

    /**
     * แสดงรายการ Templates ทั้งหมด
     */
    public function listTemplates(): View
    {
        $templates = LabelTemplate::posTemplates()
            ->with('user')
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => LabelTemplate::posTemplates()->count(),
            'product_labels' => LabelTemplate::posTemplates()->productLabels()->count(),
            'shipping_labels' => LabelTemplate::posTemplates()->shippingLabels()->count(),
            'my_templates' => LabelTemplate::posTemplates()->where('user_id', auth()->id())->count(),
        ];

        return view('admin.pos.labels.templates.index', compact('templates', 'stats'));
    }

    /**
     * แสดงฟอร์มสร้าง Template ใหม่
     */
    public function createTemplate(): View
    {
        $paperSizes = LabelPaperSize::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.pos.labels.templates.create', compact('paperSizes'));
    }

    /**
     * แสดงหน้า Label Designer (Drag & Drop Editor)
     */
    public function designerTemplate(LabelTemplate $template): View
    {
        // โหลด paper sizes สำหรับเปลี่ยนขนาด
        $paperSizes = LabelPaperSize::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.pos.labels.templates.designer', compact('template', 'paperSizes'));
    }

    /**
     * แสดงฟอร์มแก้ไขข้อมูล Template
     */
    public function editTemplate(LabelTemplate $template): View
    {
        $paperSizes = LabelPaperSize::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.pos.labels.templates.edit', compact('template', 'paperSizes'));
    }

    /**
     * บันทึก Template ใหม่
     */
    public function storeTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pos_category' => 'required|in:product_label,shipping_label,price_tag,barcode_only',
            'paper_width' => 'required|numeric|min:10',
            'paper_height' => 'required|numeric|min:10',
            'paper_unit' => 'required|in:mm,cm,in',
            'printer_type' => 'required|in:thermal_roll,thermal_label,inkjet,laser,any',
            'is_continuous_roll' => 'nullable|boolean',
            'columns' => 'required|integer|min:1|max:20',
            'rows' => 'required|integer|min:1|max:20',
            'gap_horizontal' => 'nullable|numeric|min:0',
            'gap_vertical' => 'nullable|numeric|min:0',
            'margin_top' => 'nullable|numeric|min:0',
            'margin_left' => 'nullable|numeric|min:0',
            'margin_right' => 'nullable|numeric|min:0',
            'margin_bottom' => 'nullable|numeric|min:0',
        ]);

        $template = LabelTemplate::create(array_merge($validated, [
            'user_id' => auth()->id(),
            'is_pos_template' => true,
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'elements' => LabelTemplate::getDefaultElements(),
            'settings' => LabelTemplate::getDefaultSettings(),
            'print_settings' => LabelTemplate::getDefaultPrintSettings(),
        ]));

        return redirect()
            ->route('admin.pos.labels.templates.designer', $template)
            ->with('success', 'สร้าง Template สำเร็จ! ตอนนี้คุณสามารถออกแบบฉลากได้');
    }

    /**
     * อัพเดท Template
     */
    public function updateTemplate(Request $request, LabelTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'elements' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update($validated);

        return redirect()
            ->back()
            ->with('success', 'อัพเดท Template สำเร็จ');
    }

    /**
     * ลบ Template
     */
    public function destroyTemplate(LabelTemplate $template): RedirectResponse
    {
        // ห้ามลบ system templates
        if ($template->is_system) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถลบ Template ของระบบได้');
        }

        $template->delete();

        return redirect()
            ->route('admin.pos.labels.templates.index')
            ->with('success', 'ลบ Template สำเร็จ');
    }

    /**
     * ทำสำเนา Template
     */
    public function duplicateTemplate(LabelTemplate $template): RedirectResponse
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name.' (สำเนา)';
        $newTemplate->user_id = auth()->id();
        $newTemplate->is_system = false;
        $newTemplate->is_public = false;
        $newTemplate->usage_count = 0;
        $newTemplate->save();

        return redirect()
            ->route('admin.pos.labels.templates.designer', $newTemplate)
            ->with('success', 'ทำสำเนา Template สำเร็จ');
    }
}
