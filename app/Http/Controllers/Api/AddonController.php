<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\AddonPurchase;
use App\Services\AddonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddonController extends Controller
{
    protected $addonService;

    public function __construct(AddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Get all available addons
     */
    public function index()
    {
        $addons = Addon::active()->orderBy('sort_order')->get();
        return response()->json($addons);
    }

    /**
     * Get addon details
     */
    public function show($id)
    {
        $addon = Addon::findOrFail($id);
        $stats = $this->addonService->getAddonStats($addon);

        return response()->json([
            'addon' => $addon,
            'stats' => $stats,
        ]);
    }

    /**
     * Get available addons for current vendor
     */
    public function available()
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $addons = $this->addonService->getAvailableAddons($vendor);

        return response()->json($addons);
    }

    /**
     * Get vendor's purchased addons
     */
    public function myAddons()
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $addons = $vendor->getActiveAddons();

        return response()->json($addons);
    }

    /**
     * Purchase an addon
     */
    public function purchase(Request $request)
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $validated = $request->validate([
            'addon_id' => 'required|exists:addons,id',
            'payment_method' => 'required|in:wallet,stripe,promptpay',
            'transaction_id' => 'nullable|string',
        ]);

        try {
            $addon = Addon::findOrFail($validated['addon_id']);

            $purchase = $this->addonService->purchaseAddon(
                $vendor,
                $addon,
                $validated['payment_method'],
                $validated['transaction_id'] ?? null
            );

            return response()->json([
                'message' => 'Addon purchased successfully',
                'purchase' => $purchase->load('addon'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Deactivate an addon
     */
    public function deactivate($purchaseId)
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Not a vendor'], 403);
        }

        $purchase = AddonPurchase::findOrFail($purchaseId);

        if ($purchase->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $this->addonService->deactivateAddon($purchase);

            return response()->json(['message' => 'Addon deactivated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Admin: Create addon
     */
    public function store(Request $request)
    {
        // Check if user is admin
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:addons,slug',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $addon = Addon::create($validated);

        return response()->json($addon, 201);
    }

    /**
     * Admin: Update addon
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $addon = Addon::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $addon->update($validated);

        return response()->json($addon);
    }

    /**
     * Admin: Get all addon purchases
     */
    public function purchases()
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $purchases = AddonPurchase::with(['vendor', 'addon'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($purchases);
    }
}
