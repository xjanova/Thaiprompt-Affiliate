<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\PosSession;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\PosCategory;
use App\Models\Product;
use App\Models\PosSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosCashierController extends Controller
{
    public function index(Request $request)
    {
        $deviceCode = $request->get('device_code');
        $device = PosDevice::where('device_code', $deviceCode)
            ->with('store')
            ->firstOrFail();

        // Check if device can operate
        if (!$device->canOperate()) {
            abort(403, 'Device is not active or subscription has expired');
        }

        // Update device online status
        $device->updateOnlineStatus(true, $request->ip());

        // Get or check active session
        $activeSession = PosSession::where('pos_device_id', $device->id)
            ->where('user_id', auth()->id())
            ->open()
            ->first();

        // Get POS settings
        $settings = PosSetting::where('store_id', $device->store_id)->first();

        // If session required but none exists, redirect to open session
        if (!$activeSession && $settings?->require_cash_management) {
            return redirect()->route('pos.session.open', ['device_code' => $deviceCode]);
        }

        // Get categories and products
        $categories = PosCategory::where('store_id', $device->store_id)
            ->active()
            ->showInPos()
            ->rootCategories()
            ->ordered()
            ->with(['products' => function ($query) {
                $query->where('is_active', true)
                      ->where('stock_status', '!=', 'out_of_stock');
            }])
            ->get();

        return view('pos.cashier.index', compact(
            'device',
            'activeSession',
            'settings',
            'categories'
        ));
    }

    public function openSession(Request $request)
    {
        $deviceCode = $request->get('device_code');
        $device = PosDevice::where('device_code', $deviceCode)->firstOrFail();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'opening_cash' => 'required|numeric|min:0',
                'opening_notes' => 'nullable|string|max:500',
            ]);

            $session = PosSession::create([
                'pos_device_id' => $device->id,
                'user_id' => auth()->id(),
                'opening_cash' => $validated['opening_cash'],
                'opening_notes' => $validated['opening_notes'] ?? null,
                'opened_at' => now(),
            ]);

            return redirect()->route('pos.cashier', ['device_code' => $deviceCode])
                ->with('success', 'Session opened successfully!');
        }

        return view('pos.cashier.open-session', compact('device'));
    }

    public function closeSession(Request $request, PosSession $session)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'closing_cash' => 'required|numeric|min:0',
                'closing_notes' => 'nullable|string|max:500',
            ]);

            $session->closeSession(
                $validated['closing_cash'],
                $validated['closing_notes'] ?? null
            );

            return redirect()->route('pos.session.report', $session)
                ->with('success', 'Session closed successfully!');
        }

        // Calculate expected cash
        $expectedCash = $session->opening_cash + $session->total_cash_sales - $session->total_refund_amount;

        return view('pos.cashier.close-session', compact('session', 'expectedCash'));
    }

    public function sessionReport(PosSession $session)
    {
        $session->load(['user', 'posDevice', 'transactions']);

        return view('pos.cashier.session-report', compact('session'));
    }

    public function searchProducts(Request $request)
    {
        $search = $request->get('q');
        $storeId = $request->get('store_id');

        $products = Product::where('is_active', true)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity', 'image_urls']);

        return response()->json($products);
    }

    public function getProduct(Product $product)
    {
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'stock_quantity' => $product->stock_quantity,
            'image' => $product->image_urls[0] ?? null,
            'track_inventory' => $product->track_inventory,
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:pos_devices,id',
            'session_id' => 'required|exists:pos_sessions,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'required|in:cash,card,qr,e-wallet,credit',
            'amount_paid' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $device = PosDevice::findOrFail($validated['device_id']);
            $session = PosSession::findOrFail($validated['session_id']);
            $settings = PosSetting::where('store_id', $device->store_id)->first();

            // Verify stock availability
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->track_inventory && $product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }
            }

            // Create transaction
            $transaction = new PosTransaction();
            $transaction->pos_device_id = $device->id;
            $transaction->pos_session_id = $session->id;
            $transaction->store_id = $device->store_id;
            $transaction->user_id = auth()->id();
            $transaction->payment_method = $validated['payment_method'];
            $transaction->amount_paid = $validated['amount_paid'];
            $transaction->customer_name = $validated['customer_name'] ?? null;
            $transaction->customer_phone = $validated['customer_phone'] ?? null;
            $transaction->notes = $validated['notes'] ?? null;

            // Calculate totals
            $items = [];
            $subtotal = 0;

            foreach ($validated['items'] as $itemData) {
                $product = Product::find($itemData['product_id']);

                $itemSubtotal = $itemData['price'] * $itemData['quantity'];
                $itemDiscount = isset($itemData['discount_percentage'])
                    ? $itemSubtotal * ($itemData['discount_percentage'] / 100)
                    : 0;

                $items[] = [
                    'product' => $product,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['price'],
                    'subtotal' => $itemSubtotal,
                    'discount' => $itemDiscount,
                    'total' => $itemSubtotal - $itemDiscount,
                ];

                $subtotal += $itemSubtotal;
            }

            // Apply transaction-level discount
            $discountAmount = $validated['discount_amount'] ?? 0;
            if (isset($validated['discount_percentage']) && $validated['discount_percentage'] > 0) {
                $discountAmount = $subtotal * ($validated['discount_percentage'] / 100);
            }

            $afterDiscount = $subtotal - $discountAmount;

            // Calculate tax
            $taxPercentage = $settings?->tax_percentage ?? 7.00;
            $taxAmount = $settings?->tax_enabled
                ? ($settings->tax_inclusive
                    ? $afterDiscount - ($afterDiscount / (1 + $taxPercentage / 100))
                    : $afterDiscount * ($taxPercentage / 100))
                : 0;

            // Calculate service charge
            $serviceCharge = $settings?->service_charge_enabled
                ? $afterDiscount * (($settings->service_charge_percentage ?? 0) / 100)
                : 0;

            // Total
            $total = $settings?->tax_inclusive
                ? $afterDiscount + $serviceCharge
                : $afterDiscount + $taxAmount + $serviceCharge;

            $transaction->subtotal = $subtotal;
            $transaction->discount_amount = $discountAmount;
            $transaction->discount_percentage = $validated['discount_percentage'] ?? 0;
            $transaction->tax_amount = $taxAmount;
            $transaction->tax_percentage = $taxPercentage;
            $transaction->service_charge = $serviceCharge;
            $transaction->total_amount = $total;
            $transaction->change_amount = max(0, $validated['amount_paid'] - $total);
            $transaction->total_items = count($items);
            $transaction->total_quantity = array_sum(array_column($validated['items'], 'quantity'));
            $transaction->save();

            // Create transaction items
            foreach ($items as $item) {
                $transactionItem = new PosTransactionItem();
                $transactionItem->pos_transaction_id = $transaction->id;
                $transactionItem->product_id = $item['product']->id;
                $transactionItem->product_name = $item['product']->name;
                $transactionItem->product_sku = $item['product']->sku;
                $transactionItem->product_image = $item['product']->image_urls[0] ?? null;
                $transactionItem->unit_price = $item['unit_price'];
                $transactionItem->original_price = $item['product']->price;
                $transactionItem->quantity = $item['quantity'];
                $transactionItem->discount_amount = $item['discount'];
                $transactionItem->subtotal = $item['subtotal'];
                $transactionItem->total = $item['total'];
                $transactionItem->tax_percentage = $taxPercentage;
                $transactionItem->is_tax_inclusive = $settings?->tax_inclusive ?? true;
                $transactionItem->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'transaction' => $transaction->load('items'),
                'message' => 'Transaction completed successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function customerDisplay(Request $request)
    {
        $deviceCode = $request->get('device_code');
        $device = PosDevice::where('device_code', $deviceCode)
            ->with('store')
            ->firstOrFail();

        $settings = PosSetting::where('store_id', $device->store_id)->first();

        return view('pos.customer-display', compact('device', 'settings'));
    }

    public function receipt(PosTransaction $transaction)
    {
        $transaction->load(['items', 'store', 'user', 'posDevice']);
        $settings = PosSetting::where('store_id', $transaction->store_id)->first();

        return view('pos.cashier.receipt', compact('transaction', 'settings'));
    }
}
