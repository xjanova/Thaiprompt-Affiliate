<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Exception;

class WalletSettingsController extends Controller
{
    /**
     * Display wallet settings
     */
    public function index()
    {
        if (!auth()->user()->hasPermission('manage_wallet_settings')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        $settings = WalletSetting::active()
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        $groups = [
            'limits' => 'ข้อจำกัดและขีดจำกัด',
            'fees' => 'ค่าธรรมเนียม',
            'tax' => 'ภาษี',
            'payment_methods' => 'ช่องทางการชำระเงิน',
            'general' => 'ทั่วไป',
        ];

        return view('admin.wallet.wallet-settings', compact('settings', 'groups'));
    }

    /**
     * Update a specific setting
     */
    public function update(Request $request, $key)
    {
        if (!auth()->user()->hasPermission('manage_wallet_settings')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        $request->validate([
            'value' => 'required',
        ]);

        try {
            $success = WalletSetting::set($key, $request->value);

            if ($success) {
                Cache::forget('wallet_settings');
                return redirect()->back()->with('success', 'อัพเดทการตั้งค่าเรียบร้อยแล้ว');
            } else {
                return redirect()->back()->with('error', 'ไม่พบการตั้งค่านี้');
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_wallet_settings')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        try {
            $settings = $request->input('settings', []);
            $count = 0;

            foreach ($settings as $key => $value) {
                if (WalletSetting::set($key, $value)) {
                    $count++;
                }
            }

            Cache::forget('wallet_settings');

            return redirect()->back()->with('success', "อัพเดทการตั้งค่า {$count} รายการเรียบร้อยแล้ว");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Toggle setting active status
     */
    public function toggle($id)
    {
        if (!auth()->user()->hasPermission('manage_wallet_settings')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $setting = WalletSetting::findOrFail($id);
            $setting->is_active = !$setting->is_active;
            $setting->save();

            Cache::forget('wallet_settings');

            return response()->json([
                'success' => true,
                'is_active' => $setting->is_active,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get current fee calculation
     */
    public function calculateFee(Request $request)
    {
        $request->validate([
            'type' => 'required|in:withdrawal,transfer',
            'amount' => 'required|numeric|min:0',
        ]);

        $amount = $request->amount;
        $type = $request->type;

        if ($type === 'withdrawal') {
            $fee = WalletSetting::calculateWithdrawalFee($amount);
            $tax = WalletSetting::calculateWithdrawalTax($amount);
            $netAmount = $amount - $fee - $tax;

            return response()->json([
                'amount' => $amount,
                'fee' => $fee,
                'tax' => $tax,
                'net_amount' => $netAmount,
                'formatted' => [
                    'amount' => number_format($amount, 2),
                    'fee' => number_format($fee, 2),
                    'tax' => number_format($tax, 2),
                    'net_amount' => number_format($netAmount, 2),
                ],
            ]);
        } elseif ($type === 'transfer') {
            $fee = WalletSetting::calculateTransferFee($amount);
            $totalAmount = $amount + $fee;

            return response()->json([
                'amount' => $amount,
                'fee' => $fee,
                'total_amount' => $totalAmount,
                'formatted' => [
                    'amount' => number_format($amount, 2),
                    'fee' => number_format($fee, 2),
                    'total_amount' => number_format($totalAmount, 2),
                ],
            ]);
        }
    }

    /**
     * Reset to default settings
     */
    public function resetToDefaults()
    {
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'เฉพาะ Super Admin เท่านั้นที่สามารถรีเซ็ตการตั้งค่าได้');
        }

        try {
            // Reset to default values
            WalletSetting::set('withdrawal_min_amount', 100);
            WalletSetting::set('withdrawal_max_amount', 100000);
            WalletSetting::set('withdrawal_fee_type', 'percentage');
            WalletSetting::set('withdrawal_fee_amount', 2.5);
            WalletSetting::set('withdrawal_fee_min', 10);
            WalletSetting::set('withdrawal_fee_max', 500);
            WalletSetting::set('tax_enabled', 1);
            WalletSetting::set('tax_percentage', 3);
            WalletSetting::set('tax_threshold', 1000);
            WalletSetting::set('transfer_fee_amount', 5);
            WalletSetting::set('transfer_min_amount', 10);
            WalletSetting::set('deposit_min_amount', 1);
            WalletSetting::set('withdrawal_requires_approval', 1);
            WalletSetting::set('auto_approve_threshold', 0);

            Cache::forget('wallet_settings');

            return redirect()->back()->with('success', 'รีเซ็ตการตั้งค่าเป็นค่าเริ่มต้นเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
