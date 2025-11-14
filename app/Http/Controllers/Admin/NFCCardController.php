<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NFCCard;
use App\Models\User;
use App\Services\NFC\NFCCardService;
use App\Services\NFC\NFCCardEncryptionService;
use Illuminate\Http\Request;
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
}
