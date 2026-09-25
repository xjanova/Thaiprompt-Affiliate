<?php

namespace App\Http\Controllers\FreshMarket;

use App\Exceptions\FreshMarketException;
use App\Http\Controllers\Controller;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Setting;
use App\Services\FreshMarketCartService;
use App\Services\FreshMarketService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ตะกร้า + ชำระเงินตลาดสด (หน้าเว็บ — ใช้ session login)
 *
 * ตรรกะทั้งหมดอยู่ใน FreshMarketCartService / FreshMarketService (ชุดเดียวกับแอป)
 * - คำขอแบบ AJAX (Accept: application/json) → ตอบ JSON {success, message, code?, data}
 * - ฟอร์มปกติ → redirect พร้อม flash success/error
 *
 * หน้า taladsod.cart / taladsod.checkout ออกแบบใหม่โดยทีมหน้าเว็บ (V4) — ระหว่างที่ยังไม่มีไฟล์ view
 * จะตอบข้อมูลเป็น JSON แทน (ไม่ให้หน้า error)
 */
class CartController extends Controller
{
    protected function cart(): FreshMarketCartService
    {
        return app(FreshMarketCartService::class);
    }

    /**
     * GET /taladsod/cart — หน้าตะกร้า (ทุกร้าน)
     */
    public function index(Request $request)
    {
        $cart = $this->cart()->cartFor($request->user());
        $settings = FreshMarketSetting::getSettings();
        $paymentMethods = app(FreshMarketService::class)->availablePaymentMethods();
        $riderEnabled = (bool) $settings->rider_enabled;

        if (! view()->exists('taladsod.cart') || $request->expectsJson()) {
            return $this->json(true, 'ดึงตะกร้าสำเร็จ', $cart);
        }

        return view('taladsod.cart', compact('cart', 'settings', 'paymentMethods', 'riderEnabled'));
    }

    /**
     * GET /taladsod/cart/data — ตะกร้าแบบ JSON (ป้ายจำนวนบนหัวเว็บ / Alpine)
     */
    public function data(Request $request): JsonResponse
    {
        $data = $request->validate(['seller_id' => 'nullable|integer|min:1']);

        return $this->json(true, 'ดึงตะกร้าสำเร็จ', $this->cart()->cartFor(
            $request->user(),
            isset($data['seller_id']) ? (int) $data['seller_id'] : null
        ));
    }

    /**
     * POST /taladsod/cart/items — หยิบใส่ตะกร้า
     * ฟิลด์: listing_id, quantity (1-999), option_ids[] , note?, buy_now? (1 = ไปหน้าชำระเงินของร้านนี้ทันที)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'listing_id' => 'required|integer|min:1',
            'quantity' => 'nullable|integer|min:1|max:999',
            'option_ids' => 'nullable|array|max:300',
            'option_ids.*' => 'integer|min:1',
            'note' => 'nullable|string|max:255',
            'buy_now' => 'nullable|boolean',
        ], [
            'listing_id.required' => 'กรุณาเลือกสินค้า',
            'quantity.max' => 'จำนวนต่อรายการสูงสุด 999',
            'note.max' => 'โน้ตถึงร้านยาวได้ไม่เกิน 255 ตัวอักษร',
        ]);

        return $this->run($request, function () use ($request, $data) {
            $item = $this->cart()->addItem(
                $request->user(),
                (int) $data['listing_id'],
                (int) ($data['quantity'] ?? 1),
                $data['option_ids'] ?? [],
                $data['note'] ?? null
            );

            if ($request->expectsJson()) {
                return $this->json(true, 'เพิ่มลงตะกร้าแล้ว', $this->cart()->cartFor($request->user()), 201, ['item_id' => (int) $item->id]);
            }

            if ($request->boolean('buy_now')) {
                return redirect()->route('taladsod.checkout', ['seller' => $item->seller_id]);
            }

            return back()->with('success', 'เพิ่มลงตะกร้าแล้ว');
        });
    }

    /**
     * PUT /taladsod/cart/items/{item} — แก้จำนวน/ตัวเลือก/โน้ต (quantity 0 = ลบ)
     */
    public function update(Request $request, int $item)
    {
        $data = $request->validate([
            'quantity' => 'sometimes|nullable|integer|min:0|max:999',
            'option_ids' => 'sometimes|nullable|array|max:300',
            'option_ids.*' => 'integer|min:1',
            'note' => 'sometimes|nullable|string|max:255',
        ]);

        return $this->run($request, function () use ($request, $item, $data) {
            $this->cart()->updateItem($request->user(), $item, $data);

            if ($request->expectsJson()) {
                return $this->json(true, 'อัปเดตตะกร้าแล้ว', $this->cart()->cartFor($request->user()));
            }

            return back()->with('success', 'อัปเดตตะกร้าแล้ว');
        });
    }

    /**
     * DELETE /taladsod/cart/items/{item}
     */
    public function destroy(Request $request, int $item)
    {
        return $this->run($request, function () use ($request, $item) {
            $this->cart()->removeItem($request->user(), $item);

            if ($request->expectsJson()) {
                return $this->json(true, 'ลบออกจากตะกร้าแล้ว', $this->cart()->cartFor($request->user()));
            }

            return back()->with('success', 'ลบออกจากตะกร้าแล้ว');
        });
    }

    /**
     * DELETE /taladsod/cart — ล้างตะกร้า (ส่ง seller_id = ล้างเฉพาะร้านนั้น)
     */
    public function clear(Request $request)
    {
        $data = $request->validate(['seller_id' => 'nullable|integer|min:1']);

        $this->cart()->clear($request->user(), isset($data['seller_id']) ? (int) $data['seller_id'] : null);

        if ($request->expectsJson()) {
            return $this->json(true, 'ล้างตะกร้าแล้ว', $this->cart()->cartFor($request->user()));
        }

        return back()->with('success', 'ล้างตะกร้าแล้ว');
    }

    /**
     * GET /taladsod/cart/quote?seller_id&latitude&longitude — ค่าส่งไรเดอร์ + ยอดรวมของตะกร้าร้านเดียว (JSON)
     */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seller_id' => 'required|integer|min:1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $quote = $this->cart()->quote($request->user(), (int) $data['seller_id'], (float) $data['latitude'], (float) $data['longitude']);
        } catch (FreshMarketException $e) {
            return $this->json(false, $e->getMessage(), null, $e->httpStatus(), ['code' => $e->errorCode()]);
        } catch (\Throwable $e) {
            Log::error('FreshMarket web: คำนวณค่าส่งตะกร้าล้มเหลว', ['user_id' => $request->user()?->id, 'error' => $e->getMessage()]);

            return $this->json(false, 'คำนวณค่าส่งไม่สำเร็จ กรุณาลองใหม่', null, 500, ['code' => 'SERVER_ERROR']);
        }

        return $this->json(
            (bool) $quote['available'],
            $quote['message'] ?? 'คำนวณค่าส่งสำเร็จ',
            $quote,
            200,
            ['code' => $quote['code']]
        );
    }

    /**
     * GET /taladsod/checkout/{seller} — หน้ายืนยันสั่งซื้อของร้านเดียว
     */
    public function checkout(Request $request, int $seller)
    {
        $shopCart = $this->cart()->shopCart($request->user(), $seller);

        if (! $shopCart) {
            return redirect()->route('taladsod.cart')->with('info', 'ยังไม่มีสินค้าของร้านนี้ในตะกร้า');
        }

        $shop = FreshMarketSeller::find($seller);
        $settings = FreshMarketSetting::getSettings();
        $paymentMethods = app(FreshMarketService::class)->availablePaymentMethods();
        $riderEnabled = (bool) $settings->rider_enabled;
        $pickupAvailable = $shop?->hasPickupLocation() ?? false;
        $deliveryBaseRate = (float) Setting::get('rider.base_fee', 30);
        $deliveryPerKm = (float) Setting::get('rider.per_km_fee', 10);
        $maxCodAmount = (float) Setting::get('rider.max_cod_amount', 2000);
        $walletBalance = 0.0;

        try {
            $walletBalance = round((float) app(WalletService::class)->getOrCreateWallet($request->user())->balance, 2);
        } catch (\Throwable $e) {
            Log::warning('FreshMarket web: อ่านยอด wallet ล้มเหลว', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);
        }

        if (! view()->exists('taladsod.checkout') || $request->expectsJson()) {
            return $this->json(true, 'ข้อมูลหน้าชำระเงิน', [
                'shop_cart' => $shopCart,
                'payment_methods' => $paymentMethods,
                'rider_enabled' => $riderEnabled,
                'wallet_balance' => $walletBalance,
            ]);
        }

        // ที่อยู่ที่บันทึกไว้ (เลือกแล้วเติมที่อยู่ + หมุดให้อัตโนมัติ) — เฉพาะที่อยู่ของผู้ใช้คนนี้
        $savedAddresses = [];
        try {
            $savedAddresses = \App\Models\ShippingAddress::where('user_id', $request->user()->id)
                ->orderByDesc('is_default')
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn ($a) => [
                    'id' => (int) $a->id,
                    'recipient_name' => $a->recipient_name,
                    'phone_number' => $a->phone_number,
                    'full_address' => $a->full_address,
                    'latitude' => $a->hasLocation() ? (float) $a->latitude : null,
                    'longitude' => $a->hasLocation() ? (float) $a->longitude : null,
                    'is_default' => (bool) $a->is_default,
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('FreshMarket web: อ่านที่อยู่จัดส่งล้มเหลว', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);
        }

        return view('taladsod.checkout', compact(
            'shopCart', 'shop', 'settings', 'paymentMethods', 'riderEnabled', 'pickupAvailable',
            'deliveryBaseRate', 'deliveryPerKm', 'maxCodAmount', 'walletBalance', 'savedAddresses'
        ));
    }

    /**
     * POST /taladsod/checkout — สั่งซื้อทุกรายการของร้านนี้ในตะกร้า (ได้ 1 ออเดอร์)
     * ฟิลด์: seller_id, delivery_type (pickup|rider), payment_method (wallet|cod),
     *        buyer_latitude + buyer_longitude + delivery_address (บังคับเมื่อ rider), delivery_notes?
     */
    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'seller_id' => 'required|integer|min:1',
            'delivery_type' => 'required|in:pickup,rider',
            'payment_method' => 'nullable|in:wallet,cod',
            'buyer_latitude' => 'required_if:delivery_type,rider|nullable|numeric|between:-90,90',
            'buyer_longitude' => 'required_if:delivery_type,rider|nullable|numeric|between:-180,180',
            'delivery_address' => 'required_if:delivery_type,rider|nullable|string|max:500',
            'delivery_notes' => 'nullable|string|max:500',
        ], [
            'buyer_latitude.required_if' => 'กรุณาปักหมุดตำแหน่งจัดส่ง',
            'buyer_longitude.required_if' => 'กรุณาปักหมุดตำแหน่งจัดส่ง',
            'delivery_address.required_if' => 'กรุณากรอกที่อยู่จัดส่ง',
            'delivery_type.required' => 'กรุณาเลือกวิธีรับสินค้า',
        ]);

        return $this->run($request, function () use ($request, $data) {
            $order = $this->cart()->checkout($request->user(), (int) $data['seller_id'], [
                'delivery_type' => $data['delivery_type'],
                'payment_method' => $data['payment_method'] ?? null,
                // เว็บที่ไม่ได้เลือกวิธีจ่าย → เก็บเงินปลายทางก่อน (ถ้าเปิดอยู่)
                'default_payment_method' => 'cod',
                'buyer_latitude' => $data['buyer_latitude'] ?? null,
                'buyer_longitude' => $data['buyer_longitude'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'channel' => 'web',
            ]);

            if ($request->expectsJson()) {
                return $this->json(true, 'สั่งซื้อสำเร็จ! หมายเลข: '.$order->order_number, $order->toApiArray('buyer'), 201, [
                    'redirect' => route('taladsod.orders.show', $order),
                ]);
            }

            return redirect()
                ->route('taladsod.orders.show', $order)
                ->with('success', 'สั่งซื้อสำเร็จ! หมายเลข: '.$order->order_number);
        }, true);
    }

    // ===== Helpers =====

    /**
     * รันงานที่อาจโยน FreshMarketException → JSON หรือ redirect back พร้อมข้อความไทย
     */
    protected function run(Request $request, callable $callback, bool $withInput = false)
    {
        try {
            return $callback();
        } catch (FreshMarketException $e) {
            if ($request->expectsJson()) {
                return $this->json(false, $e->getMessage(), null, $e->httpStatus(), ['code' => $e->errorCode()]);
            }

            $redirect = back()->with('error', $e->getMessage());

            return $withInput ? $redirect->withInput() : $redirect;
        } catch (\Throwable $e) {
            Log::error('FreshMarket web: ตะกร้า/สั่งซื้อล้มเหลว', [
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return $this->json(false, 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', null, 500, ['code' => 'SERVER_ERROR']);
            }

            $redirect = back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');

            return $withInput ? $redirect->withInput() : $redirect;
        }
    }

    protected function json(bool $success, ?string $message, mixed $data, int $status = 200, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $extra), $status);
    }
}
