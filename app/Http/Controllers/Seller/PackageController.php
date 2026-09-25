<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use App\Models\VendorSubscription;
use App\Services\VendorSubscriptionService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * แพ็กเกจร้านค้า
 *
 * 🔒 (2026-09-25) audit SELLER-06: เดิมกดสมัครแล้วร้านได้แพ็กเกจเสียเงินทันที (ก่อนจ่าย)
 *    และหน้าชำระเงินแค่ตั้ง paid เอง (TODO) — ตอนนี้แพ็กเกจเปลี่ยนหลังหักเงินจากกระเป๋าจริงเท่านั้น
 *    แพ็กเกจราคาพิเศษ (Enterprise) ต้องให้ทีมงานกำหนด
 */
class PackageController extends Controller
{
    public function __construct(protected VendorSubscriptionService $subscriptions) {}

    /**
     * Display available packages
     */
    public function index()
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        $packages = VendorPackage::active()
            ->ordered()
            ->get();

        return view('seller.packages.index', compact('packages', 'store'));
    }

    /**
     * สมัคร/เปลี่ยนแพ็กเกจ
     */
    public function subscribe(Request $request, $packageId)
    {
        $request->validate([
            'subscription_type' => 'required|in:monthly,yearly',
        ], [
            'subscription_type.required' => 'กรุณาเลือกรอบการชำระเงิน',
            'subscription_type.in' => 'รอบการชำระเงินไม่ถูกต้อง',
        ]);

        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->firstOrFail();
        $package = VendorPackage::active()->findOrFail($packageId);

        if ($this->subscriptions->isCustomPricing($package)) {
            return back()->with('error', 'แพ็กเกจนี้เป็นแพ็กเกจราคาพิเศษ กรุณาติดต่อทีมงานเพื่อสมัคร');
        }

        try {
            if ($this->subscriptions->isFree($package)) {
                $this->subscriptions->activateFree($store, $package);

                return redirect()->route('seller.dashboard')
                    ->with('success', 'เปลี่ยนเป็นแพ็กเกจ '.$package->display_name.' เรียบร้อยแล้ว');
            }

            $subscription = $this->subscriptions->createPendingSubscription($store, $package, $request->input('subscription_type'));

            return redirect()
                ->route('seller.packages.payment', $subscription->id)
                ->with('success', 'กรุณาชำระเงินเพื่อเริ่มใช้งานแพ็กเกจ');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Seller subscribe package failed', ['store_id' => $store->id, 'package_id' => $package->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'สมัครแพ็กเกจไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * Show payment page
     */
    public function payment($subscriptionId)
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->firstOrFail();

        $subscription = VendorSubscription::where('store_id', $store->id)
            ->where('id', $subscriptionId)
            ->with('package')
            ->firstOrFail();

        $wallet = app(WalletService::class)->getOrCreateWallet($user);
        $walletBalance = (float) $wallet->balance;
        $walletHasPin = $wallet->hasPIN();
        $totalDue = $this->subscriptions->totalDue($subscription);

        return view('seller.packages.payment', compact('subscription', 'store', 'walletBalance', 'walletHasPin', 'totalDue'));
    }

    /**
     * ชำระค่าแพ็กเกจ — รองรับการชำระด้วยกระเป๋าเงิน (หักเงินจริงแล้วเปิดใช้แพ็กเกจ)
     *
     * เติมเงินเข้ากระเป๋าผ่าน PromptPay/โอน (ระบบตรวจสลิปอัตโนมัติ) แล้วกลับมาชำระที่หน้านี้
     */
    public function processPayment(Request $request, $subscriptionId)
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->firstOrFail();

        $subscription = VendorSubscription::where('store_id', $store->id)
            ->where('id', $subscriptionId)
            ->firstOrFail();

        $wallet = app(WalletService::class)->getOrCreateWallet($user);

        $request->validate([
            'payment_method' => 'required|in:wallet,promptpay_qr,bank_transfer,credit_card',
            'pin' => $wallet->hasPIN() ? 'required|string|max:20' : 'nullable|string|max:20',
        ], [
            'payment_method.required' => 'กรุณาเลือกช่องทางชำระเงิน',
            'pin.required' => 'กรุณากรอก PIN กระเป๋าเงิน',
        ]);

        if ($request->input('payment_method') !== 'wallet') {
            return back()->with('error', 'ตอนนี้ชำระค่าแพ็กเกจได้ผ่านกระเป๋าเงินเท่านั้น กรุณาเติมเงินเข้ากระเป๋า (PromptPay/โอน) แล้วเลือกชำระด้วยกระเป๋าเงิน');
        }

        try {
            $paid = $this->subscriptions->payWithWallet($subscription, $user, $request->input('pin'));

            return redirect()
                ->route('seller.dashboard')
                ->with('success', 'ชำระเงินสำเร็จ! เริ่มใช้งานแพ็กเกจ '.($paid->package?->display_name ?? '').' ได้เลย');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Seller package payment failed', ['subscription_id' => $subscription->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'ชำระค่าแพ็กเกจไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->firstOrFail();

        $subscription = VendorSubscription::where('store_id', $store->id)
            ->where('status', 'active')
            ->latest()
            ->firstOrFail();

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
            'auto_renew' => false,
        ]);

        $store->update([
            'subscription_status' => 'cancelled',
        ]);

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'ยกเลิกแพ็คเกจสำเร็จ');
    }
}
