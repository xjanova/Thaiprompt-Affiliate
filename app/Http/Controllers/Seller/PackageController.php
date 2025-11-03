<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use App\Models\VendorSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
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
     * Subscribe to a package
     */
    public function subscribe(Request $request, $packageId)
    {
        $request->validate([
            'subscription_type' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->firstOrFail();
        $package = VendorPackage::active()->findOrFail($packageId);

        // Calculate amount based on subscription type
        $amount = $request->subscription_type === 'yearly'
            ? $package->yearly_price
            : $package->price;

        // Create subscription
        $subscription = VendorSubscription::create([
            'store_id' => $store->id,
            'package_id' => $package->id,
            'subscription_type' => $request->subscription_type,
            'amount' => $amount,
            'currency' => $package->currency,
            'started_at' => now(),
            'expires_at' => $request->subscription_type === 'yearly'
                ? now()->addYear()
                : now()->addMonth(),
            'payment_status' => 'pending',
            'status' => 'active',
            'auto_renew' => true,
            'next_billing_date' => $request->subscription_type === 'yearly'
                ? now()->addYear()
                : now()->addMonth(),
        ]);

        // Update store
        $store->update([
            'package_id' => $package->id,
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
            'subscription_expires_at' => $subscription->expires_at,
        ]);

        return redirect()
            ->route('seller.packages.payment', $subscription->id)
            ->with('success', 'กรุณาชำระเงินเพื่อเริ่มใช้งานแพ็คเกจ');
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

        return view('seller.packages.payment', compact('subscription', 'store'));
    }

    /**
     * Process payment (placeholder - integrate with actual payment gateway)
     */
    public function processPayment(Request $request, $subscriptionId)
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->firstOrFail();

        $subscription = VendorSubscription::where('store_id', $store->id)
            ->where('id', $subscriptionId)
            ->firstOrFail();

        // TODO: Integrate with actual payment gateway
        // For now, we'll mark it as paid
        $subscription->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $request->payment_method ?? 'manual',
        ]);

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'ชำระเงินสำเร็จ! เริ่มใช้งานแพ็คเกจของคุณได้เลย');
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
