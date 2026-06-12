<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\TarotCard;
use App\Models\TarotCardBackImage;
use App\Models\TarotReading;
use App\Models\TarotReadingCard;
use App\Models\TarotReadingCategory;
use App\Models\TarotSpreadType;
use App\Models\TarotUserLimit;
use App\Models\VendorStore;
use App\Services\TarotCommissionService;
use App\Services\TarotInterpretationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TarotReadingController extends Controller
{
    /**
     * บริการสร้างคำทำนายไพ่ทาโร่ต์
     */
    protected TarotInterpretationService $interpretationService;

    /**
     * บริการจัดการคอมมิชชั่นไพ่ทาโร่ต์
     */
    protected TarotCommissionService $commissionService;

    /**
     * Constructor
     */
    public function __construct(
        TarotInterpretationService $interpretationService,
        TarotCommissionService $commissionService
    ) {
        $this->interpretationService = $interpretationService;
        $this->commissionService = $commissionService;
    }

    /**
     * Show the tarot reading homepage
     */
    public function index()
    {
        $categories = TarotReadingCategory::active()->ordered()->get();
        $cardBackImage = TarotCardBackImage::getDefault();

        return view('frontend.tarot.index', compact('categories', 'cardBackImage'));
    }

    /**
     * Show category selection and spread types
     */
    public function showCategory($slug)
    {
        $category = TarotReadingCategory::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $spreadTypes = TarotSpreadType::active()->ordered()->get();
        $cardBackImage = TarotCardBackImage::getDefault();

        // Check if user can use free reading
        $userId = Auth::id();
        $sessionId = session()->getId();
        $canUseFree = TarotUserLimit::canUseFreeReading($category->id, $userId, $sessionId);

        return view('frontend.tarot.category', compact('category', 'spreadTypes', 'cardBackImage', 'canUseFree'));
    }

    /**
     * Start a new reading
     */
    public function startReading(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:tarot_reading_categories,id',
            'spread_type_id' => 'required|exists:tarot_spread_types,id',
            'question' => 'nullable|string|max:500',
            'use_free' => 'boolean',
        ]);

        $category = TarotReadingCategory::findOrFail($request->category_id);
        $spreadType = TarotSpreadType::findOrFail($request->spread_type_id);

        $userId = Auth::id();
        $sessionId = session()->getId();

        // Check if free reading is available
        $isFree = false;
        if ($request->use_free && $category->is_free_first) {
            $canUseFree = TarotUserLimit::canUseFreeReading($category->id, $userId, $sessionId);
            if ($canUseFree) {
                $isFree = true;
            }
        }

        // If not free and category has price, check payment
        if (! $isFree && $category->price > 0) {
            // Return payment required response
            return response()->json([
                'requires_payment' => true,
                'amount' => $category->price,
                'category' => $category->name_th,
                'payment_url' => route('tarot.payment', [
                    'category' => $category->id,
                    'spread_type' => $spreadType->id,
                    'question' => $request->question,
                ]),
            ]);
        }

        // Create the reading (without cards yet - user will select them)
        $reading = $this->createReading($category, $spreadType, $request->question, $isFree);

        // Store whether this is a free reading in session for card selection page
        session(['tarot_reading_'.$reading->id.'_is_free' => $isFree]);

        return response()->json([
            'success' => true,
            'reading_id' => $reading->id,
            'redirect_url' => route('tarot.select-cards', $reading->id),
        ]);
    }

    /**
     * Show reading result
     */
    public function showReading($id)
    {
        $reading = TarotReading::with(['category', 'spreadType', 'cards.card'])->findOrFail($id);

        // Check if user has access to this reading
        $userId = Auth::id();
        $sessionId = session()->getId();

        if (! $reading->belongsToUser($userId, $sessionId) && ! Auth::check()) {
            abort(403, 'Unauthorized access to this reading');
        }

        // 🔒 Payment gate: ยังไม่ชำระเงิน → ห้ามดูคำทำนาย พากลับไปหน้ารอโอน
        if ($reading->isAwaitingPayment()) {
            return $this->redirectToPaymentWaiting($reading);
        }

        $cardBackImage = TarotCardBackImage::getDefault();

        return view('frontend.tarot.reading', compact('reading', 'cardBackImage'));
    }

    /**
     * พา reading ที่ยังไม่ชำระเงินกลับไปหน้ารอโอน (หรือหน้าแรกถ้าไม่มี transaction)
     */
    private function redirectToPaymentWaiting(TarotReading $reading)
    {
        if ($reading->payment_transaction_id) {
            return redirect()
                ->route('tarot.payment.waiting', $reading->payment_transaction_id)
                ->with('info', 'กรุณาชำระเงินก่อนเปิดคำทำนาย');
        }

        return redirect()->route('tarot.index')
            ->with('error', 'รายการนี้ยังไม่ได้ชำระเงิน');
    }

    /**
     * Save reading for logged-in users
     */
    public function saveReading($id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'You must be logged in to save readings'], 401);
        }

        $reading = TarotReading::findOrFail($id);

        // Check ownership
        if ($reading->user_id != Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reading->is_saved = true;
        $reading->save();

        return response()->json(['success' => true, 'message' => 'Reading saved successfully']);
    }

    /**
     * Show user's reading history
     */
    public function history()
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view your reading history');
        }

        $readings = TarotReading::with(['category', 'spreadType'])
            ->forUser(Auth::id())
            ->latest()
            ->paginate(20);

        return view('frontend.tarot.history', compact('readings'));
    }

    /**
     * Show saved readings
     */
    public function savedReadings()
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view saved readings');
        }

        $readings = TarotReading::with(['category', 'spreadType'])
            ->forUser(Auth::id())
            ->saved()
            ->latest()
            ->paginate(20);

        return view('frontend.tarot.saved', compact('readings'));
    }

    /**
     * Payment page for paid readings
     */
    public function payment(Request $request)
    {
        $category = TarotReadingCategory::findOrFail($request->category);
        $spreadType = TarotSpreadType::findOrFail($request->spread_type);

        if ($category->price == 0) {
            return redirect()->route('tarot.category', $category->slug);
        }

        return view('frontend.tarot.payment', compact('category', 'spreadType'));
    }

    /**
     * Process payment and create reading
     *
     * 🔒 กฎเหล็ก: ห้ามจ่ายคอมมิชชั่น/เปิดไพ่ก่อนเงินเข้าจริง
     *
     * แยก flow ตามวิธีชำระเงิน:
     * - wallet: หักเงินทันที (เงินเข้าระบบแล้ว) → จ่ายคอม + สุ่มไพ่ + คำทำนายเลย
     * - promptpay/bank_transfer: สร้าง reading สถานะ pending_payment
     *   → พาไปหน้ารอโอน → SMS Checker ยืนยันเงินเข้า → PaymentService::completePayment
     *   → TarotPaymentService::finalizePaidTransaction ค่อยจ่ายคอม + เปิดให้เลือกไพ่
     * - credit_card: ยังไม่มี gateway → ไม่รับ (เดิมรับแล้ว mark completed ทันที = ดูฟรี)
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:tarot_reading_categories,id',
            'spread_type_id' => 'required|exists:tarot_spread_types,id',
            'payment_method' => 'required|in:wallet,promptpay,bank_transfer',
            'question' => 'nullable|string|max:500',
        ]);

        $category = TarotReadingCategory::findOrFail($request->category_id);
        $spreadType = TarotSpreadType::findOrFail($request->spread_type_id);

        $userId = Auth::id();
        $paymentMethod = $request->payment_method;

        // ===== Wallet: เงินอยู่ในระบบแล้ว หักได้ทันที =====
        if ($paymentMethod === 'wallet') {
            // ชำระผ่าน wallet ต้อง login เท่านั้น
            if (! $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาเข้าสู่ระบบก่อนชำระผ่าน Wallet',
                ], 401);
            }

            $wallet = Auth::user()->wallet;
            if (! $wallet || $wallet->balance < $category->price) {
                return response()->json([
                    'success' => false,
                    'message' => 'ยอดเงินใน Wallet ไม่เพียงพอ',
                    'required_amount' => $category->price,
                    'current_balance' => $wallet ? $wallet->balance : 0,
                ], 400);
            }

            return $this->processWalletPayment($request, $category, $spreadType);
        }

        // ===== PromptPay / Bank Transfer: รอเงินเข้าก่อน =====
        return $this->createPendingPaymentReading($request, $category, $spreadType, $paymentMethod);
    }

    /**
     * ชำระผ่าน Wallet — หักเงิน + จ่ายคอม + สุ่มไพ่ + คำทำนายทันที
     * (เงินเข้าระบบ ณ วินาทีที่หักสำเร็จ จึงเปิดผลได้เลย)
     */
    private function processWalletPayment(Request $request, TarotReadingCategory $category, TarotSpreadType $spreadType)
    {
        $userId = Auth::id();

        // สร้าง transaction + reading
        $transaction = $this->createTarotTransaction($category, $spreadType, 'wallet', $request->question);
        $reading = $this->createReading($category, $spreadType, $request->question, false, $category->price);

        // ผูก reading เข้ากับ transaction (TarotPaymentService/paymentStatus ใช้ตามหา)
        $transaction->update([
            'metadata' => array_merge($transaction->metadata ?? [], ['reading_id' => $reading->id]),
        ]);
        $reading->update([
            'payment_transaction_id' => $transaction->id,
            'payment_status' => 'pending',
        ]);

        // หักเงินจาก wallet + แบ่งคอมมิชชั่น (TarotCommissionService หักผ่าน WalletService)
        $commissionResult = $this->commissionService->processPayment($reading, 'wallet', $transaction->id);

        if (! $commissionResult['success']) {
            // หักเงินไม่สำเร็จ → ยกเลิกทั้งคู่
            $reading->delete();
            $transaction->update([
                'status' => 'failed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'error' => $commissionResult['message'],
                ]),
            ]);

            return response()->json([
                'success' => false,
                'message' => $commissionResult['message'],
            ], 400);
        }

        // เงินเข้าแล้วจริง → ปิด transaction + mark reading paid
        $transaction->update([
            'status' => 'completed',
            'paid_at' => now(),
            'metadata' => array_merge($transaction->metadata ?? [], [
                'platform_fee' => $commissionResult['data']['platform_fee'] ?? 0,
                'pv_amount' => $commissionResult['data']['pv_amount'] ?? 0,
                'total_commission' => $commissionResult['data']['total_commission'] ?? 0,
            ]),
        ]);
        $reading->update(['payment_status' => 'paid', 'paid_at' => now()]);

        // สุ่มไพ่ + สร้างคำทำนาย
        $cards = $this->selectRandomCards($spreadType->card_count, $spreadType);
        foreach ($cards as $index => $cardData) {
            TarotReadingCard::create([
                'reading_id' => $reading->id,
                'card_id' => $cardData['card']->id,
                'position' => $index + 1,
                'position_name' => $spreadType->getPositionName($index + 1),
                'is_reversed' => $cardData['is_reversed'],
            ]);
        }

        // นับ paid reading
        TarotUserLimit::incrementPaidReading($category->id, $userId, session()->getId(), $request->ip());

        // สร้างคำทำนายละเอียดสำหรับไพ่ทั้งหมด
        $this->interpretationService->generateInterpretations($reading);

        Log::info('Tarot: ชำระผ่าน wallet สำเร็จ (หักเงิน+คอม+เปิดไพ่)', [
            'reading_id' => $reading->id,
            'user_id' => $userId,
            'amount' => $category->price,
            'total_commission' => $reading->total_commission,
        ]);

        return response()->json([
            'success' => true,
            'pending' => false,
            'reading_id' => $reading->id,
            'redirect_url' => route('tarot.reading.show', $reading->id),
        ]);
    }

    /**
     * PromptPay / Bank Transfer — สร้างรายการรอชำระ (ยังไม่เปิดไพ่ ไม่จ่ายคอม)
     *
     * Flow ต่อจากนี้:
     * 1. ลูกค้าโอนตามยอด unique amount (สร้างอัตโนมัติใน PaymentTransaction::created)
     * 2. SMS Checker จับยอด → PaymentService::completePayment()
     * 3. TarotPaymentService::finalizePaidTransaction → mark paid + คอม + นับ limit
     * 4. หน้า waiting polling เจอ paid → พาไปเลือกไพ่ (select-cards)
     */
    private function createPendingPaymentReading(Request $request, TarotReadingCategory $category, TarotSpreadType $spreadType, string $paymentMethod)
    {
        // สร้าง reading สถานะรอชำระ (ยังไม่มีไพ่/คำทำนาย — เปิดหลังเงินเข้า)
        $reading = $this->createReading($category, $spreadType, $request->question, false, $category->price);

        // สร้าง transaction pending — unique amount ถูก generate อัตโนมัติ
        // ใน PaymentTransaction::created event (เฉพาะ promptpay/bank_transfer)
        $transaction = $this->createTarotTransaction($category, $spreadType, $paymentMethod, $request->question, $reading->id);

        $reading->update([
            'payment_status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_transaction_id' => $transaction->id,
        ]);

        // refresh เพื่อให้ได้ amount ที่ติดทศนิยม unique แล้ว
        $transaction->refresh();

        Log::info('Tarot: สร้างรายการรอชำระ (pending payment)', [
            'reading_id' => $reading->id,
            'transaction_id' => $transaction->id,
            'payment_method' => $paymentMethod,
            'amount' => $transaction->amount,
        ]);

        return response()->json([
            'success' => true,
            'pending' => true,
            'reading_id' => $reading->id,
            'amount' => (float) $transaction->amount,
            'redirect_url' => route('tarot.payment.waiting', $transaction->id),
        ]);
    }

    /**
     * สร้าง PaymentTransaction สำหรับบิลทำนายไพ่ (single flow)
     *
     * @param  int|null  $readingId  ใส่เมื่อสร้าง reading ก่อน transaction
     */
    private function createTarotTransaction(
        TarotReadingCategory $category,
        TarotSpreadType $spreadType,
        string $paymentMethod,
        ?string $question,
        ?int $readingId = null
    ): PaymentTransaction {
        $metadata = [
            'type' => 'tarot_reading',
            'category_id' => $category->id,
            'spread_type_id' => $spreadType->id,
            'question' => $question,
        ];
        if ($readingId) {
            $metadata['reading_id'] = $readingId;
        }

        return PaymentTransaction::create([
            'transaction_id' => 'TAROT-'.strtoupper(Str::random(12)),
            'user_id' => Auth::id(),
            'store_id' => VendorStore::getPlatformStoreId(),
            'type' => 'order_payment',
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'amount' => $category->price,
            'currency' => 'THB',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Show interactive card selection page
     */
    public function showCardSelection($readingId)
    {
        $reading = TarotReading::with(['category', 'spreadType'])->findOrFail($readingId);

        // Check if user has access to this reading
        $userId = Auth::id();
        $sessionId = session()->getId();

        if (! $reading->belongsToUser($userId, $sessionId) && ! Auth::check()) {
            abort(403, 'Unauthorized access to this reading');
        }

        // 🔒 Payment gate: ยังไม่ชำระเงิน → ห้ามเลือกไพ่ พากลับไปหน้ารอโอน
        if ($reading->isAwaitingPayment()) {
            return $this->redirectToPaymentWaiting($reading);
        }

        // Check if cards are already selected
        if ($reading->cards()->count() > 0) {
            return redirect()->route('tarot.reading.show', $reading->id);
        }

        $spreadType = $reading->spreadType;
        $cardBackImage = TarotCardBackImage::getDefault();

        return view('frontend.tarot.select-cards', compact('reading', 'spreadType', 'cardBackImage', 'readingId'));
    }

    /**
     * Save user's card selection
     */
    public function saveCardSelection(Request $request)
    {
        $request->validate([
            'reading_id' => 'required|exists:tarot_readings,id',
            'selected_card_indices' => 'required|array',
        ]);

        $reading = TarotReading::with('spreadType')->findOrFail($request->reading_id);

        // Check if user has access
        $userId = Auth::id();
        $sessionId = session()->getId();

        if (! $reading->belongsToUser($userId, $sessionId) && ! Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // 🔒 Payment gate: ยังไม่ชำระเงิน → ห้ามบันทึกไพ่ (กันยิง API ตรงข้าม gate หน้าเว็บ)
        if ($reading->isAwaitingPayment()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาชำระเงินก่อนเปิดคำทำนาย',
            ], 402);
        }

        // Check if cards are already selected
        if ($reading->cards()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cards already selected']);
        }

        // Validate correct number of cards
        if (count($request->selected_card_indices) != $reading->spreadType->card_count) {
            return response()->json(['success' => false, 'message' => 'Invalid number of cards selected']);
        }

        // Get all active tarot cards
        $allCards = TarotCard::active()->get();

        // Map selected indices to actual cards
        $selectedCardIndices = $request->selected_card_indices;

        foreach ($selectedCardIndices as $position => $cardIndex) {
            // Use the card index to select from the deck (simulate random selection based on user's choice)
            $card = $allCards->get($cardIndex % $allCards->count());
            $isReversed = rand(0, 1) === 1; // 50% chance of reversed

            TarotReadingCard::create([
                'reading_id' => $reading->id,
                'card_id' => $card->id,
                'position' => $position + 1,
                'position_name' => $reading->spreadType->getPositionName($position + 1),
                'is_reversed' => $isReversed,
            ]);
        }

        // Update user limits if free
        $isFree = session('tarot_reading_'.$reading->id.'_is_free', false);
        if ($isFree) {
            TarotUserLimit::incrementFreeReading(
                $reading->category_id,
                $userId,
                $sessionId,
                request()->ip()
            );

            // Clear session
            session()->forget('tarot_reading_'.$reading->id.'_is_free');
        }

        // สร้างคำทำนายละเอียดสำหรับไพ่ทั้งหมด
        $this->interpretationService->generateInterpretations($reading);

        return response()->json([
            'success' => true,
            'reading_id' => $reading->id,
            'redirect_url' => route('tarot.reading.show', $reading->id),
        ]);
    }

    /**
     * Create a reading record
     */
    private function createReading($category, $spreadType, $question, $isFree, $amountPaid = 0)
    {
        return TarotReading::create([
            'user_id' => Auth::id(),
            'category_id' => $category->id,
            'spread_type_id' => $spreadType->id,
            'question' => $question,
            'is_free' => $isFree,
            'amount_paid' => $amountPaid,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Select random cards for reading
     */
    private function selectRandomCards($count, $spreadType)
    {
        $allCards = TarotCard::active()->get();
        $selectedCards = $allCards->random($count);

        $cards = [];
        foreach ($selectedCards as $index => $card) {
            $isReversed = rand(0, 1) === 1; // 50% chance of reversed

            $cards[] = [
                'card' => $card,
                'is_reversed' => $isReversed,
                'position' => $index + 1,
                'position_name' => $spreadType->getPositionName($index + 1),
            ];
        }

        return $cards;
    }

    /**
     * Get available card back images
     */
    public function getCardBackImages()
    {
        $images = TarotCardBackImage::active()->orderBy('sort_order')->get();

        return response()->json($images);
    }

    // หมายเหตุ: unique amount สำหรับ SMS Checker ถูกสร้างอัตโนมัติใน
    // PaymentTransaction::created event (generateUniqueAmountIfNeeded)
    // — method generate ในไฟล์นี้ถูกถอดออกเพราะซ้ำซ้อน (เคยสร้าง UPA 2 แถวต่อบิล)
}
