<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EarningsLedger;
use App\Models\PayoutSetting;
use Illuminate\Http\Request;

/**
 * PayoutController
 *
 * สองหน้าที่ยังใช้งานจริงของเมนู "รายได้แพลตฟอร์ม":
 *  1. ตั้งค่า payout_settings — `holding_days` ของผู้ขายถูกอ่านจริงโดย
 *     SellerPayoutService::holdingDays() จึงเป็นหน้าที่แก้ค่านี้ได้ที่เดียว
 *  2. Earnings Ledger — รายการรายได้ที่ SellerPayoutService บันทึกลง earnings_ledger
 *     (หน้ารายละเอียดรายตัว earnings.show ถอดออกด้วย เพราะไม่เคยมีไฟล์ view)
 *
 * ❌ ส่วนของ payout_requests ถูกถอดออกแล้ว (2026-11-06): ไม่มีโค้ดไหนสร้างแถวใน
 *    ตารางนั้นเลย ตัวสร้างตัวเดียวคือ PayoutService::createPayoutRequest() ที่เรียก
 *    PayoutSetting::getSettingByType() ซึ่งไม่เคยมีอยู่จริง (มาพังตั้งแต่ commit
 *    c6e918bba) หน้าจอและปุ่มทั้งหมดจึงทำงานบนตารางว่างมาตลอด
 *    คำขอถอนเงินของจริงอยู่ที่ WithdrawalService → /admin/withdrawals
 */
class PayoutController extends Controller
{
    /**
     * การตั้งค่า Payout (ที่ยังมีผลจริงคือ holding_days ของผู้ขาย)
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $settings = PayoutSetting::all();

        return view('admin.platform-revenue.payouts.settings', [
            'settings' => $settings,
            'pageTitle' => 'Payout Settings',
        ]);
    }

    /**
     * อัพเดทการตั้งค่า Payout
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSetting(Request $request, PayoutSetting $setting)
    {
        $validated = $request->validate([
            'payout_mode' => 'required|in:manual,auto_schedule,realtime,hybrid',
            'min_payout' => 'required|numeric|min:0',
            'max_payout' => 'nullable|numeric|min:0',
            'fee_fixed' => 'nullable|numeric|min:0',
            'fee_percentage' => 'nullable|numeric|min:0|max:100',
            'holding_days' => 'nullable|integer|min:0',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'schedule' => 'nullable|array',
        ]);

        $setting->update($validated);

        return redirect()
            ->route('admin.platform-revenue.payouts.settings')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * Earnings Ledger
     *
     * @return \Illuminate\View\View
     */
    public function earnings(Request $request)
    {
        $query = EarningsLedger::with('user');

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->earning_type) {
            $query->where('earning_type', $request->earning_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $earnings = $query->latest()->paginate(30);

        // สถิติ
        $stats = [
            'total_gross' => EarningsLedger::sum('gross_amount'),
            'total_net' => EarningsLedger::sum('net_amount'),
            'total_fees' => EarningsLedger::sum('platform_fee'),
            'pending_count' => EarningsLedger::where('status', EarningsLedger::STATUS_PENDING)->count(),
            'available_count' => EarningsLedger::where('status', EarningsLedger::STATUS_AVAILABLE)->count(),
            'paid_count' => EarningsLedger::where('status', EarningsLedger::STATUS_PAID)->count(),
        ];

        return view('admin.platform-revenue.earnings.index', [
            'earnings' => $earnings,
            'stats' => $stats,
            'filters' => $request->only(['user_id', 'earning_type', 'status', 'date_from', 'date_to']),
            'pageTitle' => 'Earnings Ledger',
        ]);
    }
}
