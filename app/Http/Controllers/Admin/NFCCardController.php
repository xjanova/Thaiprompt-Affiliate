<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NFCCard;
use App\Models\NFCTransaction;
use App\Models\User;
use App\Services\NFC\NFCCardService;
use App\Services\NFC\NFCCardEncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class NFCCardController extends Controller
{
    protected NFCCardService $nfcCardService;
    protected NFCCardEncryptionService $encryptionService;

    public function __construct(
        NFCCardService $nfcCardService,
        NFCCardEncryptionService $encryptionService
    ) {
        $this->nfcCardService = $nfcCardService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Display a listing of NFC cards
     */
    public function index(Request $request)
    {
        $query = NFCCard::with(['user', 'issuer'])->latest();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('card_number', 'like', "%{$search}%")
                    ->orWhere('card_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by card type
        if ($request->filled('card_type')) {
            $query->where('card_type', $request->card_type);
        }

        // Filter by paired status
        if ($request->filled('is_paired')) {
            $query->where('is_paired', $request->is_paired);
        }

        $cards = $query->paginate(20);

        // Statistics
        $statistics = [
            'total_cards' => NFCCard::count(),
            'active_cards' => NFCCard::active()->count(),
            'paired_cards' => NFCCard::paired()->count(),
            'blocked_cards' => NFCCard::where('status', NFCCard::STATUS_BLOCKED)->count(),
            'total_balance' => NFCCard::sum('balance'),
        ];

        return view('admin.nfc-cards.index', compact('cards', 'statistics'));
    }

    /**
     * Show the form for creating a new card
     */
    public function create()
    {
        $cardTypes = [
            NFCCard::TYPE_STANDARD => 'มาตรฐาน',
            NFCCard::TYPE_PREMIUM => 'พรีเมียม',
            NFCCard::TYPE_VIP => 'วีไอพี',
        ];

        return view('admin.nfc-cards.create', compact('cardTypes'));
    }

    /**
     * Store a newly created card
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|string|unique:nfc_cards,card_number',
            'card_name' => 'nullable|string|max:255',
            'card_type' => 'required|in:' . implode(',', [
                NFCCard::TYPE_STANDARD,
                NFCCard::TYPE_PREMIUM,
                NFCCard::TYPE_VIP,
            ]),
            'initial_balance' => 'nullable|numeric|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
        ]);

        try {
            $card = $this->nfcCardService->issueCard($validated, auth()->id());

            return redirect()
                ->route('admin.nfc-cards.show', $card)
                ->with('success', 'ออกบัตร NFC สำเร็จ');
        } catch (Exception $e) {
            Log::error('Failed to issue NFC card', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถออกบัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified card
     */
    public function show(NFCCard $nfcCard)
    {
        $nfcCard->load(['user', 'issuer', 'pairer']);

        // Get statistics
        $statistics = $this->nfcCardService->getCardStatistics($nfcCard);

        // Get recent transactions
        $recentTransactions = $nfcCard->transactions()
            ->with(['user', 'nfcReader'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.nfc-cards.show', compact('nfcCard', 'statistics', 'recentTransactions'));
    }

    /**
     * Show the form for editing the card
     */
    public function edit(NFCCard $nfcCard)
    {
        $cardTypes = [
            NFCCard::TYPE_STANDARD => 'มาตรฐาน',
            NFCCard::TYPE_PREMIUM => 'พรีเมียม',
            NFCCard::TYPE_VIP => 'วีไอพี',
        ];

        return view('admin.nfc-cards.edit', compact('nfcCard', 'cardTypes'));
    }

    /**
     * Update the card
     */
    public function update(Request $request, NFCCard $nfcCard)
    {
        $validated = $request->validate([
            'card_name' => 'nullable|string|max:255',
            'card_type' => 'required|in:' . implode(',', [
                NFCCard::TYPE_STANDARD,
                NFCCard::TYPE_PREMIUM,
                NFCCard::TYPE_VIP,
            ]),
            'credit_limit' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $nfcCard->update($validated);

            return redirect()
                ->route('admin.nfc-cards.show', $nfcCard)
                ->with('success', 'อัพเดทข้อมูลบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถอัพเดทข้อมูลได้: ' . $e->getMessage());
        }
    }

    /**
     * Delete the card
     */
    public function destroy(NFCCard $nfcCard)
    {
        try {
            $nfcCard->delete();

            return redirect()
                ->route('admin.nfc-cards.index')
                ->with('success', 'ลบบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถลบบัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * Show pair card form
     */
    public function pairForm(NFCCard $nfcCard)
    {
        if ($nfcCard->isPaired()) {
            return back()->with('error', 'บัตรนี้ถูกจับคู่กับผู้ใช้แล้ว');
        }

        $users = User::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.nfc-cards.pair', compact('nfcCard', 'users'));
    }

    /**
     * Pair card with user
     */
    public function pair(Request $request, NFCCard $nfcCard)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);

            $this->nfcCardService->pairCardWithUser($nfcCard, $user, auth()->id());

            return redirect()
                ->route('admin.nfc-cards.show', $nfcCard)
                ->with('success', 'จับคู่บัตรกับผู้ใช้สำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถจับคู่บัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * Unpair card from user
     */
    public function unpair(NFCCard $nfcCard)
    {
        try {
            $this->nfcCardService->unpairCard($nfcCard);

            return back()->with('success', 'ยกเลิกการจับคู่บัตรสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถยกเลิกการจับคู่ได้: ' . $e->getMessage());
        }
    }

    /**
     * Activate card
     */
    public function activate(NFCCard $nfcCard)
    {
        try {
            $nfcCard->activate();

            return back()->with('success', 'เปิดใช้งานบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถเปิดใช้งานบัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate card
     */
    public function deactivate(NFCCard $nfcCard)
    {
        try {
            $nfcCard->deactivate();

            return back()->with('success', 'ปิดการใช้งานบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถปิดการใช้งานบัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * Block card
     */
    public function block(Request $request, NFCCard $nfcCard)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'duration_minutes' => 'nullable|integer|min:1',
        ]);

        try {
            $this->nfcCardService->blockCard(
                $nfcCard,
                $validated['reason'],
                $validated['duration_minutes'] ?? null
            );

            return back()->with('success', 'บล็อกบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถบล็อกบัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * Unblock card
     */
    public function unblock(NFCCard $nfcCard)
    {
        try {
            $this->nfcCardService->unblockCard($nfcCard);

            return back()->with('success', 'ปลดบล็อกบัตรสำเร็จ');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถปลดบล็อกบัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * Show top-up form
     */
    public function topUpForm(NFCCard $nfcCard)
    {
        return view('admin.nfc-cards.topup', compact('nfcCard'));
    }

    /**
     * Top up card balance
     */
    public function topUp(Request $request, NFCCard $nfcCard)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            $transaction = $this->nfcCardService->topUpCard(
                $nfcCard,
                $validated['amount'],
                null,
                [
                    'notes' => $validated['notes'] ?? null,
                    'topped_up_by' => auth()->id(),
                ]
            );

            return redirect()
                ->route('admin.nfc-cards.show', $nfcCard)
                ->with('success', 'เติมเงินสำเร็จ จำนวน ' . number_format($validated['amount'], 2) . ' บาท');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถเติมเงินได้: ' . $e->getMessage());
        }
    }

    /**
     * Read card data (for verification)
     */
    public function read(Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|string',
            'encrypted_data' => 'required|string',
        ]);

        try {
            $result = $this->nfcCardService->readAndVerifyCard(
                $validated['card_number'],
                $validated['encrypted_data']
            );

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Export cards data
     */
    public function export(Request $request)
    {
        $query = NFCCard::with(['user', 'issuer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('card_type')) {
            $query->where('card_type', $request->card_type);
        }

        $cards = $query->get();

        $filename = 'nfc-cards-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($cards) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($file, [
                'Card Number',
                'Card Name',
                'Card Type',
                'User',
                'Status',
                'Balance',
                'Credit Limit',
                'Is Paired',
                'Issued Date',
                'Expires At',
            ]);

            // Data
            foreach ($cards as $card) {
                fputcsv($file, [
                    $card->card_number,
                    $card->card_name,
                    $card->card_type_label,
                    $card->user?->name,
                    $card->status_label,
                    $card->balance,
                    $card->credit_limit,
                    $card->is_paired ? 'Yes' : 'No',
                    $card->created_at->format('Y-m-d H:i:s'),
                    $card->expires_at?->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ==========================================
    // 🆕 V2: Enhanced NFC Features
    // ==========================================

    /**
     * แสดง Dashboard ภาพรวมระบบ NFC
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // สถิติรวม
        $totalCards = NFCCard::count();
        $activeCards = NFCCard::where('status', NFCCard::STATUS_ACTIVE)
            ->where('is_enabled', true)
            ->count();
        $blockedCards = NFCCard::where('status', NFCCard::STATUS_BLOCKED)->count();
        $totalBalance = NFCCard::sum('balance');

        // ธุรกรรมวันนี้
        $todayTransactions = NFCTransaction::whereDate('created_at', today())->count();
        $todayVolume = NFCTransaction::whereDate('created_at', today())->sum('amount');

        // ธุรกรรม 7 วันล่าสุด
        $weeklyStats = NFCTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as volume')
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // บัตรที่มียอดเงินต่ำ (น้อยกว่า 500)
        $lowBalanceCards = NFCCard::where('balance', '<', 500)
            ->where('status', NFCCard::STATUS_ACTIVE)
            ->with('user')
            ->take(10)
            ->get();

        // ธุรกรรมล่าสุด
        $recentTransactions = NFCTransaction::with(['card', 'user'])
            ->latest()
            ->take(15)
            ->get();

        return view('admin.nfc.dashboard', compact(
            'totalCards',
            'activeCards',
            'blockedCards',
            'totalBalance',
            'todayTransactions',
            'todayVolume',
            'weeklyStats',
            'lowBalanceCards',
            'recentTransactions'
        ));
    }

    /**
     * แสดงรายการธุรกรรมทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function transactions(Request $request)
    {
        $query = NFCTransaction::with(['card', 'user', 'nfcReader']);

        // Filter by card
        if ($request->filled('card_id')) {
            $query->where('nfc_card_id', $request->card_id);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest()->paginate(50)->appends($request->except('page'));

        return view('admin.nfc.transactions', compact('transactions'));
    }

    /**
     * พักการใช้งานบัตร
     *
     * @param Request $request
     * @param NFCCard $nfcCard
     * @return \Illuminate\Http\RedirectResponse
     */
    public function suspend(Request $request, NFCCard $nfcCard)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $nfcCard->suspend($validated['reason']);

            return back()->with('success', 'พักการใช้งานบัตรเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถพักการใช้งานบัตรได้: ' . $e->getMessage());
        }
    }

    /**
     * อัพเดทวงเงินการใช้จ่าย
     *
     * @param Request $request
     * @param NFCCard $nfcCard
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSpendingLimits(Request $request, NFCCard $nfcCard)
    {
        $validated = $request->validate([
            'daily_spending_limit' => 'required|numeric|min:0|max:1000000',
            'monthly_spending_limit' => 'required|numeric|min:0|max:10000000',
            'transaction_limit' => 'required|numeric|min:0|max:100000',
        ]);

        try {
            $this->nfcCardService->setSpendingLimits($nfcCard, [
                'daily_limit' => $validated['daily_spending_limit'],
                'monthly_limit' => $validated['monthly_spending_limit'],
                'transaction_limit' => $validated['transaction_limit'],
            ]);

            return back()->with('success', 'อัพเดทวงเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถอัพเดทวงเงินได้: ' . $e->getMessage());
        }
    }

    /**
     * ผูกบัตรกับ Wallet
     *
     * @param Request $request
     * @param NFCCard $nfcCard
     * @return \Illuminate\Http\RedirectResponse
     */
    public function linkWallet(Request $request, NFCCard $nfcCard)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
        ]);

        try {
            $this->nfcCardService->linkCardToWallet($nfcCard, $validated['wallet_id']);

            return back()->with('success', 'ผูกการ์ดกับ Wallet เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถผูกการ์ดกับ Wallet ได้: ' . $e->getMessage());
        }
    }

    /**
     * ยกเลิกการผูกบัตรกับ Wallet
     *
     * @param NFCCard $nfcCard
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unlinkWallet(NFCCard $nfcCard)
    {
        try {
            $this->nfcCardService->unlinkCardFromWallet($nfcCard);

            return back()->with('success', 'ยกเลิกการผูกการ์ดเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถยกเลิกการผูกได้: ' . $e->getMessage());
        }
    }

    /**
     * ตั้งค่า Auto Top-up
     *
     * @param Request $request
     * @param NFCCard $nfcCard
     * @return \Illuminate\\Http\RedirectResponse
     */
    public function configureAutoTopUp(Request $request, NFCCard $nfcCard)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'threshold' => 'required_if:enabled,true|nullable|numeric|min:0|max:10000',
            'amount' => 'required_if:enabled,true|nullable|numeric|min:100|max:100000',
        ]);

        try {
            if ($validated['enabled']) {
                $this->nfcCardService->configureAutoTopUp(
                    $nfcCard,
                    $validated['threshold'],
                    $validated['amount']
                );
                $message = 'เปิดใช้งาน Auto Top-up เรียบร้อยแล้ว';
            } else {
                $nfcCard->disableAutoTopup();
                $message = 'ปิดใช้งาน Auto Top-up เรียบร้อยแล้ว';
            }

            return back()->with('success', $message);
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถตั้งค่า Auto Top-up ได้: ' . $e->getMessage());
        }
    }

    /**
     * เปิดใช้งาน TPIX
     *
     * @param Request $request
     * @param NFCCard $nfcCard
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enableTPIX(Request $request, NFCCard $nfcCard)
    {
        $validated = $request->validate([
            'tpix_wallet_address' => 'required|string|size:42|regex:/^0x[a-fA-F0-9]{40}$/',
        ]);

        try {
            $this->nfcCardService->enableTPIXPayment($nfcCard, $validated['tpix_wallet_address']);

            return back()->with('success', 'เปิดใช้งาน TPIX เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถเปิดใช้งาน TPIX ได้: ' . $e->getMessage());
        }
    }

    /**
     * ปิดใช้งาน TPIX
     *
     * @param NFCCard $nfcCard
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disableTPIX(NFCCard $nfcCard)
    {
        try {
            $this->nfcCardService->disableTPIXPayment($nfcCard);

            return back()->with('success', 'ปิดใช้งาน TPIX เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return back()->with('error', 'ไม่สามารถปิดใช้งาน TPIX ได้: ' . $e->getMessage());
        }
    }

    // ==========================================
    // 🆕 V3: NFC Card Read/Write API
    // ==========================================

    /**
     * สร้างรหัสป้องกันปลอม (Anti-Counterfeit Code)
     *
     * ใช้สำหรับเขียนลงบัตร NFC เพื่อป้องกันการปลอมแปลง
     * - สร้าง Hash จาก Card Number + UID + Secret + Timestamp
     * - สร้าง Digital Signature
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateAntiCounterfeitCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'card_number' => 'required|string',
                'uid' => 'nullable|string', // NFC UID (Serial Number)
            ]);

            $cardNumber = $validated['card_number'];
            $uid = $validated['uid'] ?? null;

            // สร้างรหัสป้องกันปลอม
            $timestamp = now()->timestamp;
            $secret = config('app.key'); // ใช้ App Key เป็น Secret

            // สร้าง Auth Code: Hash(CardNumber + UID + Secret + Timestamp)
            $authData = implode('|', [
                $cardNumber,
                $uid ?? 'NO_UID',
                $secret,
                $timestamp
            ]);

            $antiCounterfeitCode = hash('sha256', $authData);

            // สร้าง Digital Signature: HMAC(AuthCode, Secret)
            $signature = hash_hmac('sha256', $antiCounterfeitCode, $secret);

            // เก็บข้อมูลการสร้างรหัสลง Database (สำหรับตรวจสอบภายหลัง)
            // บันทึกลงตาราง nfc_cards หรือ nfc_card_authentications
            // (ถ้ายังไม่มีการออกบัตร จะต้องทำหลังจากออกบัตรแล้ว)

            Log::info('Generated anti-counterfeit code', [
                'card_number' => $cardNumber,
                'uid' => $uid,
                'timestamp' => $timestamp,
                'auth_code_hash' => substr($antiCounterfeitCode, 0, 16) . '...',
            ]);

            return response()->json([
                'success' => true,
                'code' => $antiCounterfeitCode,
                'signature' => $signature,
                'timestamp' => $timestamp,
                'message' => 'สร้างรหัสป้องกันปลอมสำเร็จ'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to generate anti-counterfeit code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้างรหัสป้องกันปลอมได้: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ตรวจสอบรหัสป้องกันปลอม
     *
     * ใช้เมื่ออ่านบัตร NFC เพื่อตรวจสอบว่าเป็นบัตรแท้หรือไม่
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyAntiCounterfeitCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'card_number' => 'required|string',
                'anti_counterfeit_code' => 'required|string',
                'signature' => 'required|string',
                'uid' => 'nullable|string',
            ]);

            $cardNumber = $validated['card_number'];
            $receivedCode = $validated['anti_counterfeit_code'];
            $receivedSignature = $validated['signature'];
            $uid = $validated['uid'] ?? null;

            // ตรวจสอบว่ามีบัตรในระบบหรือไม่
            $card = NFCCard::where('card_number', $cardNumber)->first();

            if (!$card) {
                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'message' => 'ไม่พบบัตรในระบบ'
                ], 404);
            }

            // ตรวจสอบ Signature
            $secret = config('app.key');
            $expectedSignature = hash_hmac('sha256', $receivedCode, $secret);

            $signatureValid = hash_equals($expectedSignature, $receivedSignature);

            // ตรวจสอบ UID (ถ้ามี)
            $uidMatch = true;
            if ($uid && $card->nfc_uid) {
                $uidMatch = ($uid === $card->nfc_uid);
            }

            $verified = $signatureValid && $uidMatch;

            // บันทึก Log
            Log::info('Anti-counterfeit verification', [
                'card_number' => $cardNumber,
                'verified' => $verified,
                'signature_valid' => $signatureValid,
                'uid_match' => $uidMatch,
            ]);

            // บันทึกประวัติการตรวจสอบ
            DB::table('nfc_verification_logs')->insert([
                'nfc_card_id' => $card->id,
                'card_number' => $cardNumber,
                'verification_type' => 'anti_counterfeit',
                'verified' => $verified,
                'signature_valid' => $signatureValid,
                'uid_match' => $uidMatch,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'verified' => $verified,
                'card' => [
                    'card_number' => $card->card_number,
                    'card_type' => $card->card_type,
                    'status' => $card->status,
                    'is_active' => $card->isActive(),
                    'balance' => $card->balance,
                ],
                'checks' => [
                    'signature_valid' => $signatureValid,
                    'uid_match' => $uidMatch,
                ],
                'message' => $verified ? 'บัตรแท้ - ตรวจสอบผ่าน' : 'บัตรไม่ถูกต้อง - ตรวจสอบไม่ผ่าน'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to verify anti-counterfeit code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'ไม่สามารถตรวจสอบได้: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * บันทึกข้อมูล NFC UID ลงบัตร
     *
     * เรียกหลังจากเขียนข้อมูลลงบัตร NFC สำเร็จ
     *
     * @param Request $request
     * @param NFCCard $nfcCard
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveNFCUID(Request $request, NFCCard $nfcCard)
    {
        try {
            $validated = $request->validate([
                'nfc_uid' => 'required|string',
                'anti_counterfeit_code' => 'required|string',
                'signature' => 'required|string',
            ]);

            // อัพเดทข้อมูลบัตร
            $nfcCard->update([
                'nfc_uid' => $validated['nfc_uid'],
                'anti_counterfeit_code' => $validated['anti_counterfeit_code'],
                'nfc_signature' => $validated['signature'],
                'nfc_written_at' => now(),
                'nfc_written_by' => auth()->id(),
            ]);

            Log::info('NFC UID saved', [
                'card_id' => $nfcCard->id,
                'card_number' => $nfcCard->card_number,
                'nfc_uid' => $validated['nfc_uid'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'บันทึกข้อมูล NFC สำเร็จ'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Failed to save NFC UID', [
                'card_id' => $nfcCard->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage()
            ], 500);
        }
    }
}
