<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashbackSetting;
use App\Models\Product;
use App\Services\CashbackService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CashbackSettingController extends Controller
{
    protected CashbackService $cashbackService;

    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,super_admin']);
        $this->cashbackService = new CashbackService(new WalletService());
    }

    /**
     * Display a listing of cashback settings
     */
    public function index(Request $request)
    {
        $query = CashbackSetting::with('product');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('product', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $settings = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = $this->cashbackService->getCashbackStatistics();

        return view('admin.cashback.index', compact('settings', 'stats'));
    }

    /**
     * Show the form for creating a new cashback setting
     */
    public function create()
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.cashback.create', compact('products'));
    }

    /**
     * Store a newly created cashback setting
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:global,product',
            'product_id' => 'required_if:type,product|nullable|exists:products,id',
            'value_type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_cashback_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validate percentage is not > 100
        if ($request->value_type === 'percentage' && $request->value > 100) {
            return redirect()->back()
                ->withErrors(['value' => 'เปอร์เซ็นต์ไม่สามารถเกิน 100%'])
                ->withInput();
        }

        // Check if global setting already exists
        if ($request->type === 'global') {
            $existingGlobal = CashbackSetting::where('type', 'global')
                ->where('is_active', true)
                ->first();

            if ($existingGlobal) {
                // Deactivate existing global setting
                $existingGlobal->update(['is_active' => false]);
            }
        }

        // Check if product setting already exists
        if ($request->type === 'product' && $request->product_id) {
            $existingProduct = CashbackSetting::where('type', 'product')
                ->where('product_id', $request->product_id)
                ->where('is_active', true)
                ->first();

            if ($existingProduct) {
                // Deactivate existing product setting
                $existingProduct->update(['is_active' => false]);
            }
        }

        $setting = CashbackSetting::create([
            'type' => $request->type,
            'product_id' => $request->type === 'product' ? $request->product_id : null,
            'value_type' => $request->value_type,
            'value' => $request->value,
            'is_active' => $request->has('is_active'),
            'min_order_amount' => $request->min_order_amount,
            'max_cashback_amount' => $request->max_cashback_amount,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.cashback.index')
            ->with('success', 'สร้างการตั้งค่า Cashback สำเร็จ');
    }

    /**
     * Show the form for editing the specified cashback setting
     */
    public function edit(CashbackSetting $cashback)
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.cashback.edit', compact('cashback', 'products'));
    }

    /**
     * Update the specified cashback setting
     */
    public function update(Request $request, CashbackSetting $cashback)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:global,product',
            'product_id' => 'required_if:type,product|nullable|exists:products,id',
            'value_type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_cashback_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validate percentage is not > 100
        if ($request->value_type === 'percentage' && $request->value > 100) {
            return redirect()->back()
                ->withErrors(['value' => 'เปอร์เซ็นต์ไม่สามารถเกิน 100%'])
                ->withInput();
        }

        $cashback->update([
            'type' => $request->type,
            'product_id' => $request->type === 'product' ? $request->product_id : null,
            'value_type' => $request->value_type,
            'value' => $request->value,
            'is_active' => $request->has('is_active'),
            'min_order_amount' => $request->min_order_amount,
            'max_cashback_amount' => $request->max_cashback_amount,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.cashback.index')
            ->with('success', 'อัปเดตการตั้งค่า Cashback สำเร็จ');
    }

    /**
     * Remove the specified cashback setting
     */
    public function destroy(CashbackSetting $cashback)
    {
        $cashback->delete();

        return redirect()->route('admin.cashback.index')
            ->with('success', 'ลบการตั้งค่า Cashback สำเร็จ');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(CashbackSetting $cashback)
    {
        $cashback->update([
            'is_active' => !$cashback->is_active,
        ]);

        $status = $cashback->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()->back()
            ->with('success', "{$status}การตั้งค่า Cashback สำเร็จ");
    }

    /**
     * Show cashback statistics
     */
    public function statistics(Request $request)
    {
        $startDate = $request->input('start_date') ? new \DateTime($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? new \DateTime($request->input('end_date')) : null;

        $stats = $this->cashbackService->getCashbackStatistics($startDate, $endDate);

        return view('admin.cashback.statistics', compact('stats', 'startDate', 'endDate'));
    }
}
