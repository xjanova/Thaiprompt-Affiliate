<?php

namespace App\Http\Controllers\Admin\Pos;

use App\Http\Controllers\Controller;
use App\Models\LabelTemplate;
use App\Models\LabelPaperSize;
use App\Models\PosLabelPrint;
use App\Models\PosTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
     *
     * @return View
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
     *
     * @return View
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
     *
     * @param PosTransaction|null $transaction
     * @return View
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
     * ค้นหาสินค้าสำหรับพิมพ์ฉลาก
     *
     * @param Request $request
     * @return JsonResponse
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
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Preview ก่อนพิมพ์
     *
     * @param Request $request
     * @return JsonResponse
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
        if (!$template->is_continuous_roll) {
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
     *
     * @param Request $request
     * @return JsonResponse
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
            if (!$template->is_continuous_roll) {
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
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * พิมพ์ Shipping Label จาก Transaction
     *
     * @param Request $request
     * @param PosTransaction $transaction
     * @return JsonResponse
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
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงรายการ Templates
     *
     * @param Request $request
     * @return JsonResponse
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
     *
     * @param Request $request
     * @return JsonResponse
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
     * ดึงประวัติการพิมพ์
     *
     * @param Request $request
     * @return View
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
     *
     * @param PosLabelPrint $print
     * @return View
     */
    public function show(PosLabelPrint $print): View
    {
        $print->load(['user', 'template', 'transaction', 'device']);

        return view('admin.pos.labels.show', compact('print'));
    }

    /**
     * ลบรายการพิมพ์
     *
     * @param PosLabelPrint $print
     * @return RedirectResponse
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createBatchSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $sessionId = 'BATCH-' . Str::upper(Str::random(10));

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $sessionId,
                'total_items' => count($validated['items']),
            ],
        ]);
    }
}
