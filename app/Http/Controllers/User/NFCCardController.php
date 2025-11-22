<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\NFCCard;
use App\Services\NFC\NFCCardService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * NFC Card Controller สำหรับ User
 *
 * จัดการการ์ด NFC ของผู้ใช้ รวมถึง:
 * - ดูรายการการ์ด
 * - เปิด/ปิดการใช้งาน
 * - ตั้งค่าวงเงิน
 * - ผูกกับ Wallet
 * - ดูประวัติการทำธุรกรรม
 */
class NFCCardController extends Controller
{
    protected NFCCardService $nfcCardService;
    protected WalletService $walletService;

    public function __construct(
        NFCCardService $nfcCardService,
        WalletService $walletService
    ) {
        $this->nfcCardService = $nfcCardService;
        $this->walletService = $walletService;
        $this->middleware('auth');
    }

    /**
     * แสดงรายการการ์ด NFC ทั้งหมดของผู้ใช้
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // ดึงการ์ดทั้งหมดของ user พร้อม relationships
        $cards = NFCCard::where('user_id', $user->id)
            ->with(['wallet', 'transactions' => function ($query) {
                $query->latest()->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // ดึง wallet ของ user
        $wallet = $this->walletService->getOrCreateWallet($user);

        // สถิติรวม
        $totalBalance = $cards->sum('balance');
        $totalCards = $cards->count();
        $activeCards = $cards->where('status', NFCCard::STATUS_ACTIVE)
            ->where('is_enabled', true)
            ->count();

        return view('user.nfc.index', compact(
            'cards',
            'wallet',
            'totalBalance',
            'totalCards',
            'activeCards'
        ));
    }

    /**
     * แสดงรายละเอียดการ์ด
     *
     * @param NFCCard $card
     * @return \Illuminate\View\View
     */
    public function show(NFCCard $card)
    {
        // ตรวจสอบว่าการ์ดเป็นของ user หรือไม่
        $this->authorizeCardOwnership($card);

        // โหลด relationships
        $card->load(['wallet', 'transactions' => function ($query) {
            $query->latest()->limit(20);
        }]);

        // สถิติการใช้งาน
        $statistics = $this->nfcCardService->getCardStatistics($card);

        return view('user.nfc.show', compact('card', 'statistics'));
    }

    /**
     * เปิดการใช้งานการ์ด
     *
     * @param Request $request
     * @param NFCCard $card
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enable(Request $request, NFCCard $card)
    {
        $this->authorizeCardOwnership($card);

        try {
            $card->enable();

            return redirect()->back()->with('success', 'เปิดใช้งานการ์ด ' . $card->card_name . ' เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'ไม่สามารถเปิดใช้งานการ์ดได้: ' . $e->getMessage());
        }
    }

    /**
     * ปิดการใช้งานการ์ด
     *
     * @param Request $request
     * @param NFCCard $card
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disable(Request $request, NFCCard $card)
    {
        $this->authorizeCardOwnership($card);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $card->disable($validated['reason'] ?? 'ปิดโดยผู้ใช้');

            return redirect()->back()->with('success', 'ปิดการใช้งานการ์ด ' . $card->card_name . ' เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'ไม่สามารถปิดการใช้งานการ์ดได้: ' . $e->getMessage());
        }
    }

    /**
     * ตั้งค่าวงเงิน
     *
     * @param Request $request
     * @param NFCCard $card
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateLimits(Request $request, NFCCard $card)
    {
        $this->authorizeCardOwnership($card);

        $validated = $request->validate([
            'daily_limit' => 'required|numeric|min:0|max:1000000',
            'monthly_limit' => 'required|numeric|min:0|max:10000000',
            'transaction_limit' => 'required|numeric|min:0|max:100000',
        ]);

        try {
            $this->nfcCardService->setSpendingLimits($card, [
                'daily_limit' => $validated['daily_limit'],
                'monthly_limit' => $validated['monthly_limit'],
                'transaction_limit' => $validated['transaction_limit'],
            ]);

            return redirect()->back()->with('success', 'อัพเดทวงเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'ไม่สามารถอัพเดทวงเงินได้: ' . $e->getMessage());
        }
    }

    /**
     * ผูกการ์ดกับ Wallet
     *
     * @param Request $request
     * @param NFCCard $card
     * @return \Illuminate\Http\RedirectResponse
     */
    public function linkWallet(Request $request, NFCCard $card)
    {
        $this->authorizeCardOwnership($card);

        try {
            $user = Auth::user();
            $wallet = $this->walletService->getOrCreateWallet($user);

            $this->nfcCardService->linkCardToWallet($card, $wallet->id);

            return redirect()->back()->with('success', 'ผูกการ์ดกับ Wallet เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'ไม่สามารถผูกการ์ดกับ Wallet ได้: ' . $e->getMessage());
        }
    }

    /**
     * ยกเลิกการผูกการ์ดกับ Wallet
     *
     * @param Request $request
     * @param NFCCard $card
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unlinkWallet(Request $request, NFCCard $card)
    {
        $this->authorizeCardOwnership($card);

        try {
            $this->nfcCardService->unlinkCardFromWallet($card);

            return redirect()->back()->with('success', 'ยกเลิกการผูกการ์ดกับ Wallet เรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'ไม่สามารถยกเลิกการผูกการ์ดได้: ' . $e->getMessage());
        }
    }

    /**
     * เติมเงินจาก Wallet
     *
     * @param Request $request
     * @param NFCCard $card
     * @return \Illuminate\Http\RedirectResponse
     */
    public function topUpFromWallet(Request $request, NFCCard $card)
    {
        $this->authorizeCardOwnership($card);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:100000',
            'pin' => 'required|string|size:6',
        ]);

        try {
            $this->nfcCardService->topUpFromWallet(
                $card,
                $validated['amount'],
                $validated['pin']
            );

            return redirect()->back()->with('success', 'เติมเงินเรียบร้อยแล้ว ฿' . number_format($validated['amount'], 2));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'ไม่สามารถเติมเงินได้: ' . $e->getMessage());
        }
    }

    /**
     * ตั้งค่า Auto Top-up
     *
     * @param Request $request
     * @param NFCCard $card
     * @return \Illuminate\Http\RedirectResponse
     */
    public function configureAutoTopUp(Request $request, NFCCard $card)
    {
        $this->authorizeCardOwnership($card);

        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'threshold' => 'required_if:enabled,true|nullable|numeric|min:0|max:10000',
            'amount' => 'required_if:enabled,true|nullable|numeric|min:100|max:100000',
        ]);

        try {
            if ($validated['enabled']) {
                $this->nfcCardService->configureAutoTopUp(
                    $card,
                    $validated['threshold'],
                    $validated['amount']
                );
                $message = 'เปิดใช้งาน Auto Top-up เรียบร้อยแล้ว';
            } else {
                $card->disableAutoTopup();
                $message = 'ปิดใช้งาน Auto Top-up เรียบร้อยแล้ว';
            }

            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'ไม่สามารถตั้งค่า Auto Top-up ได้: ' . $e->getMessage());
        }
    }

    /**
     * ดูประวัติการทำธุรกรรม
     *
     * @param NFCCard $card
     * @return \Illuminate\View\View
     */
    public function transactions(NFCCard $card)
    {
        $this->authorizeCardOwnership($card);

        $transactions = $card->transactions()
            ->with('nfcReader')
            ->latest()
            ->paginate(20);

        return view('user.nfc.transactions', compact('card', 'transactions'));
    }

    /**
     * ตรวจสอบสิทธิ์การเข้าถึงการ์ด
     *
     * @param NFCCard $card
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function authorizeCardOwnership(NFCCard $card)
    {
        if ($card->user_id !== Auth::id()) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงการ์ดนี้');
        }
    }
}
