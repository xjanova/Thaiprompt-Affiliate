<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\TarotCard;
use App\Models\TarotCartItem;
use App\Models\TarotReading;
use App\Models\TarotReadingCard;
use App\Models\TarotReadingCategory;
use App\Models\TarotSpreadType;
use App\Models\TarotUserLimit;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TarotCartController extends Controller
{
    /**
     * Add item to cart
     */
    public function addToCart(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:tarot_reading_categories,id',
            'spread_type_id' => 'required|exists:tarot_spread_types,id',
            'question' => 'required|string|max:500',
        ]);

        $category = TarotReadingCategory::findOrFail($request->category_id);
        $spreadType = TarotSpreadType::findOrFail($request->spread_type_id);

        // Calculate price (category price + spread type base price)
        $price = $category->price + ($spreadType->base_price ?? 0);

        $userId = Auth::id();
        $sessionId = session()->getId();
        $ipAddress = $request->ip();

        // Add to cart
        $cartItem = TarotCartItem::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'category_id' => $request->category_id,
            'spread_type_id' => $request->spread_type_id,
            'question' => $request->question,
            'price' => $price,
        ]);

        return redirect()->route('tarot.cart.index')
            ->with('success', 'เพิ่มรายการเข้าตะกร้าแล้ว');
    }

    /**
     * View cart
     */
    public function index()
    {
        $userId = Auth::id();
        $sessionId = session()->getId();
        $ipAddress = request()->ip();

        $cartItems = TarotCartItem::getCartItems($userId, $sessionId, $ipAddress);
        $cartTotal = TarotCartItem::getCartTotal($userId, $sessionId, $ipAddress);

        return view('frontend.tarot.cart', compact('cartItems', 'cartTotal'));
    }

    /**
     * Remove item from cart
     */
    public function removeItem($id)
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        $item = TarotCartItem::where('id', $id);

        if ($userId) {
            $item->where('user_id', $userId);
        } else {
            $item->where('session_id', $sessionId);
        }

        $item->delete();

        return redirect()->route('tarot.cart.index')
            ->with('success', 'ลบรายการออกจากตะกร้าแล้ว');
    }

    /**
     * Clear cart
     */
    public function clearCart()
    {
        $userId = Auth::id();
        $sessionId = session()->getId();
        $ipAddress = request()->ip();

        TarotCartItem::clearCart($userId, $sessionId, $ipAddress);

        return redirect()->route('tarot.cart.index')
            ->with('success', 'ล้างตะกร้าเรียบร้อย');
    }

    /**
     * Checkout page
     */
    public function checkout()
    {
        $userId = Auth::id();
        $sessionId = session()->getId();
        $ipAddress = request()->ip();

        $cartItems = TarotCartItem::getCartItems($userId, $sessionId, $ipAddress);

        if ($cartItems->isEmpty()) {
            return redirect()->route('tarot.cart.index')
                ->with('error', 'ตะกร้าว่างเปล่า กรุณาเลือกรายการก่อน');
        }

        $cartTotal = $cartItems->sum('price');

        return view('frontend.tarot.checkout', compact('cartItems', 'cartTotal'));
    }

    /**
     * Process checkout
     */
    public function processCheckout(Request $request)
    {
        // 🔒 รับเฉพาะช่องทางที่ระบบยืนยันเงินเข้าได้จริง (SMS Checker)
        // wallet/credit_card เดิมเป็น "demo mode" mark paid ทันทีโดยไม่มีเงินเข้า → ถอดออก
        $request->validate([
            'payment_method' => 'required|in:promptpay,bank_transfer',
        ]);

        $userId = Auth::id();
        $sessionId = session()->getId();
        $ipAddress = $request->ip();

        $cartItems = TarotCartItem::getCartItems($userId, $sessionId, $ipAddress);

        if ($cartItems->isEmpty()) {
            return redirect()->route('tarot.cart.index')
                ->with('error', 'ตะกร้าว่างเปล่า');
        }

        $cartTotal = $cartItems->sum('price');

        DB::beginTransaction();
        try {
            // Create readings for each cart item
            $readingIds = [];

            foreach ($cartItems as $cartItem) {
                // Create reading
                $reading = TarotReading::create([
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'category_id' => $cartItem->category_id,
                    'spread_type_id' => $cartItem->spread_type_id,
                    'question' => $cartItem->question,
                    'price' => $cartItem->price,
                    'payment_status' => $cartTotal > 0 ? 'pending' : 'free',
                    'payment_method' => $request->payment_method,
                ]);

                // Pick random cards
                $cardCount = $cartItem->spreadType->card_count;
                $cards = TarotCard::active()->inRandomOrder()->limit($cardCount)->get();

                foreach ($cards as $index => $card) {
                    TarotReadingCard::create([
                        'reading_id' => $reading->id,
                        'card_id' => $card->id,
                        'position' => $index + 1,
                        'is_reversed' => rand(0, 1) == 1,
                    ]);
                }

                // Generate interpretation
                $reading->interpretation = $this->generateInterpretation($reading);
                $reading->save();

                $readingIds[] = $reading->id;

                // Increment usage count
                if ($cartItem->price == 0) {
                    TarotUserLimit::incrementFreeReading(
                        $cartItem->category_id,
                        $userId,
                        $sessionId,
                        $ipAddress
                    );
                } else {
                    TarotUserLimit::incrementPaidReading(
                        $cartItem->category_id,
                        $userId,
                        $sessionId,
                        $ipAddress
                    );
                }
            }

            // Process payment if total > 0
            if ($cartTotal > 0) {
                // Create payment transaction (ใช้ Platform Store ID สำหรับบริการ Tarot)
                $transaction = PaymentTransaction::create([
                    'user_id' => $userId,
                    'store_id' => VendorStore::getPlatformStoreId(),
                    'amount' => $cartTotal,
                    'payment_method' => $request->payment_method,
                    'status' => 'pending',
                    'type' => 'tarot_reading',
                    'notes' => 'ค่าทำนายไพ่ทาโร่ต์ '.count($readingIds).' รายการ',
                    'metadata' => [
                        'reading_ids' => $readingIds,
                        'type' => 'tarot_reading',
                    ],
                ]);

                // Note: unique amount ถูกสร้างอัตโนมัติใน PaymentTransaction::created event

                // ผูก readings เข้ากับ transaction
                // (payment_transaction_id ใช้โดย payment gate + TarotPaymentService)
                TarotReading::whereIn('id', $readingIds)->update([
                    'transaction_id' => $transaction->id,
                    'payment_transaction_id' => $transaction->id,
                ]);

                // Refresh transaction to get updated amount (with unique decimal suffix)
                $transaction->refresh();

                DB::commit();

                // แสดงหน้ารอชำระเงิน — SMS Checker ยืนยันเงินเข้าแล้ว
                // PaymentService::completePayment → TarotPaymentService จะ mark paid + จ่ายคอมให้เอง
                return redirect()->route('tarot.payment.waiting', $transaction->id)
                    ->with('info', 'กรุณาชำระเงินตามยอดที่แสดง');
            } else {
                // Free items - no payment needed
                DB::commit();

                // Clear cart
                TarotCartItem::clearCart($userId, $sessionId, $ipAddress);

                // Redirect to first reading
                return redirect()->route('tarot.reading.show', $readingIds[0])
                    ->with('success', 'ดูผลการทำนายของคุณได้เลย');
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * Generate interpretation for reading
     */
    private function generateInterpretation($reading)
    {
        $cards = $reading->cards()->with('card')->get();
        $interpretation = "คำทำนายสำหรับคำถาม: {$reading->question}\n\n";

        foreach ($cards as $readingCard) {
            $card = $readingCard->card;
            $position = $reading->spreadType->getPositionName($readingCard->position);
            $meaning = $readingCard->is_reversed ?
                $card->reversed_meaning_th :
                $card->upright_meaning_th;

            $interpretation .= "ตำแหน่ง: {$position}\n";
            $interpretation .= "ไพ่: {$card->name_th}".($readingCard->is_reversed ? ' (กลับหัว)' : '')."\n";
            $interpretation .= "ความหมาย: {$meaning}\n\n";
        }

        return $interpretation;
    }

    /**
     * Payment waiting page - แสดงยอดเงินที่ต้องชำระและรอ SMS Checker ตรวจจับ
     */
    public function paymentWaiting(PaymentTransaction $transaction)
    {
        // ตรวจสอบว่า transaction เป็นของ user ปัจจุบันหรือไม่ (ถ้า logged in)
        $userId = Auth::id();
        if ($userId && $transaction->user_id && $transaction->user_id !== $userId) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึงรายการนี้');
        }

        // ชำระเงินแล้ว → พาไปหน้าที่ถูกต้องตาม flow
        if ($transaction->status === 'completed') {
            $redirectUrl = $this->resolvePaidRedirectUrl($transaction);
            if ($redirectUrl) {
                return redirect($redirectUrl)->with('success', 'ชำระเงินสำเร็จแล้ว!');
            }

            return redirect()->route('tarot.cart.index')
                ->with('info', 'รายการนี้ชำระเงินแล้ว');
        }

        // ดึงข้อมูลที่ต้องแสดง
        $amount = (float) $transaction->amount;
        $expiredAt = $transaction->expired_at;

        return view('frontend.tarot.payment-waiting', compact('transaction', 'amount', 'expiredAt'));
    }

    /**
     * Check payment status via AJAX
     */
    public function paymentStatus(PaymentTransaction $transaction)
    {
        // ตรวจสอบว่า transaction เป็นของ user ปัจจุบันหรือไม่
        $userId = Auth::id();
        if ($userId && $transaction->user_id && $transaction->user_id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Refresh transaction from database
        $transaction->refresh();

        $completed = $transaction->status === 'completed';
        $redirectUrl = null;

        if ($completed) {
            // Self-heal: ถ้าเงินเข้าแล้วแต่ reading ยังค้าง pending
            // (finalize ตอน completePayment ล้มเหลว) → retry แบบ idempotent
            $this->retryFinalizeIfNeeded($transaction);

            $redirectUrl = $this->resolvePaidRedirectUrl($transaction);
        }

        return response()->json([
            'status' => $transaction->status,
            'completed' => $completed,
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * หา URL ปลายทางหลังชำระเงินสำเร็จ ตาม flow ของ transaction
     *
     * - Single flow (metadata.reading_id): ยังไม่มีไพ่ → พาไปหน้าเลือกไพ่
     * - Cart flow (metadata.reading_ids): ไพ่+คำทำนายสร้างแล้ว → หน้าผลทำนาย
     */
    private function resolvePaidRedirectUrl(PaymentTransaction $transaction): ?string
    {
        $metadata = $transaction->metadata ?? [];

        if (! empty($metadata['reading_id'])) {
            return route('tarot.select-cards', $metadata['reading_id']);
        }

        $readingIds = $metadata['reading_ids'] ?? [];
        if (! empty($readingIds)) {
            return route('tarot.reading.show', $readingIds[0]);
        }

        return null;
    }

    /**
     * Retry finalize เมื่อ transaction completed แต่ reading ยังค้าง pending
     * (idempotent — TarotPaymentService ข้าม reading ที่ paid แล้วเอง)
     */
    private function retryFinalizeIfNeeded(PaymentTransaction $transaction): void
    {
        try {
            $hasPendingReading = TarotReading::where('payment_transaction_id', $transaction->id)
                ->where('payment_status', 'pending')
                ->exists();

            if ($hasPendingReading) {
                app(\App\Services\TarotPaymentService::class)->finalizePaidTransaction($transaction);
            }
        } catch (\Throwable $e) {
            \Log::warning('TarotCart: retry finalize ล้มเหลว (ไม่ block polling)', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
