<?php

namespace App\Http\Controllers;

use App\Models\TarotCartItem;
use App\Models\TarotReadingCategory;
use App\Models\TarotSpreadType;
use App\Models\TarotCard;
use App\Models\TarotReading;
use App\Models\TarotReadingCard;
use App\Models\TarotUserLimit;
use App\Models\TarotSetting;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $request->validate([
            'payment_method' => 'required|in:wallet,promptpay,credit_card,bank_transfer',
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
                // Create payment transaction
                $transaction = PaymentTransaction::create([
                    'user_id' => $userId,
                    'amount' => $cartTotal,
                    'payment_method' => $request->payment_method,
                    'status' => 'pending',
                    'description' => 'ค่าทำนายไพ่ทาโร่ต์ ' . count($readingIds) . ' รายการ',
                    'metadata' => [
                        'reading_ids' => $readingIds,
                        'type' => 'tarot_reading',
                    ],
                ]);

                // Update readings with transaction ID
                TarotReading::whereIn('id', $readingIds)->update([
                    'transaction_id' => $transaction->id,
                ]);

                // TODO: Integrate with actual payment gateway here
                // For now, mark as paid immediately (demo)
                $transaction->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);

                TarotReading::whereIn('id', $readingIds)->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            // Clear cart
            TarotCartItem::clearCart($userId, $sessionId, $ipAddress);

            DB::commit();

            // Redirect to first reading
            return redirect()->route('tarot.reading.show', $readingIds[0])
                ->with('success', 'ชำระเงินสำเร็จ! ดูผลการทำนายของคุณได้เลย');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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
            $interpretation .= "ไพ่: {$card->name_th}" . ($readingCard->is_reversed ? ' (กลับหัว)' : '') . "\n";
            $interpretation .= "ความหมาย: {$meaning}\n\n";
        }

        return $interpretation;
    }
}
