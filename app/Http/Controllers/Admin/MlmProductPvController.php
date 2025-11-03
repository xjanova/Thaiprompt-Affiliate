<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmProductPv;
use App\Models\Product;
use App\Models\MlmPlan;
use App\Services\MlmPvService;
use Illuminate\Http\Request;

class MlmProductPvController extends Controller
{
    protected $pvService;

    public function __construct()
    {
        $this->pvService = new MlmPvService();
    }

    public function index(Request $request)
    {
        $query = MlmProductPv::with(['product', 'plan']);

        // Filters
        if ($request->has('plan_id')) {
            $query->where('mlm_plan_id', $request->plan_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $productPvs = $query->paginate(50);
        $plans = MlmPlan::all();

        return view('admin.mlm.product-pv.index', compact('productPvs', 'plans'));
    }

    public function create()
    {
        $products = Product::whereDoesntHave('mlmProductPv')->get();
        $plans = MlmPlan::active()->get();

        return view('admin.mlm.product-pv.create', compact('products', 'plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'mlm_plan_id' => 'required|exists:mlm_plans,id',
            'pv_value' => 'required|numeric|min:0',
            'use_global_rate' => 'boolean',
            'custom_commission_per_pv' => 'nullable|numeric|min:0',
            'show_pv_on_product_page' => 'boolean',
            'show_commission_preview' => 'boolean',
            'pv_description' => 'nullable|string',
            'pv_description_th' => 'nullable|string',
        ]);

        // Check if already exists
        $exists = MlmProductPv::where('product_id', $validated['product_id'])
            ->where('mlm_plan_id', $validated['mlm_plan_id'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->with('error', 'PV configuration already exists for this product and plan');
        }

        MlmProductPv::create($validated);

        return redirect()
            ->route('admin.mlm.product-pv.index')
            ->with('success', 'Product PV configuration created successfully');
    }

    public function edit(MlmProductPv $productPv)
    {
        $productPv->load('product', 'plan');

        return view('admin.mlm.product-pv.edit', compact('productPv'));
    }

    public function update(Request $request, MlmProductPv $productPv)
    {
        $validated = $request->validate([
            'pv_value' => 'required|numeric|min:0',
            'use_global_rate' => 'boolean',
            'custom_commission_per_pv' => 'nullable|numeric|min:0',
            'show_pv_on_product_page' => 'boolean',
            'show_commission_preview' => 'boolean',
            'pv_description' => 'nullable|string',
            'pv_description_th' => 'nullable|string',
        ]);

        $productPv->update($validated);

        return redirect()
            ->route('admin.mlm.product-pv.index')
            ->with('success', 'Product PV configuration updated successfully');
    }

    public function destroy(MlmProductPv $productPv)
    {
        $productPv->delete();

        return redirect()
            ->route('admin.mlm.product-pv.index')
            ->with('success', 'Product PV configuration deleted successfully');
    }

    public function preview(Product $product, Request $request)
    {
        $planId = $request->get('plan_id');
        $quantity = $request->get('quantity', 1);

        $plan = MlmPlan::findOrFail($planId);

        $preview = $this->pvService->calculateProductCommissionPreview($product, $plan, $quantity);

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }

    public function bulkCreate(Request $request)
    {
        $validated = $request->validate([
            'mlm_plan_id' => 'required|exists:mlm_plans,id',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'use_price_as_pv' => 'boolean',
            'pv_multiplier' => 'nullable|numeric|min:0',
        ]);

        $plan = MlmPlan::findOrFail($validated['mlm_plan_id']);
        $count = 0;

        foreach ($validated['product_ids'] as $productId) {
            $product = Product::find($productId);

            // Skip if already configured
            if (MlmProductPv::where('product_id', $productId)->where('mlm_plan_id', $plan->id)->exists()) {
                continue;
            }

            $pvValue = $validated['use_price_as_pv']
                ? $product->price * ($validated['pv_multiplier'] ?? 1)
                : 0;

            MlmProductPv::create([
                'product_id' => $productId,
                'mlm_plan_id' => $plan->id,
                'pv_value' => $pvValue,
                'use_global_rate' => true,
                'show_pv_on_product_page' => true,
                'show_commission_preview' => true,
            ]);

            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} products configured",
            'count' => $count,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'product_pv_ids' => 'required|array',
            'product_pv_ids.*' => 'exists:mlm_product_pv,id',
            'action' => 'required|in:toggle_display,update_multiplier,delete',
            'multiplier' => 'nullable|numeric|min:0',
        ]);

        $productPvs = MlmProductPv::whereIn('id', $validated['product_pv_ids'])->get();

        foreach ($productPvs as $productPv) {
            switch ($validated['action']) {
                case 'toggle_display':
                    $productPv->update([
                        'show_pv_on_product_page' => !$productPv->show_pv_on_product_page,
                    ]);
                    break;

                case 'update_multiplier':
                    if (isset($validated['multiplier'])) {
                        $productPv->update([
                            'pv_value' => $productPv->product->price * $validated['multiplier'],
                        ]);
                    }
                    break;

                case 'delete':
                    $productPv->delete();
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk action completed',
        ]);
    }
}
