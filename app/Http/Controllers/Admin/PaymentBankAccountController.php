<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentBankAccount;
use App\Models\SmsCheckerDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * จัดการบัญชีธนาคารสำหรับรับชำระเงิน (Admin)
 *
 * รองรับหลายธนาคาร, PromptPay, และ SMS Checker
 */
class PaymentBankAccountController extends Controller
{
    /**
     * แสดงรายการบัญชีธนาคาร + ตั้งค่า SMS Checker
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $accounts = PaymentBankAccount::ordered()->get();

        // สถิติ
        $stats = [
            'total' => $accounts->count(),
            'active' => $accounts->where('is_active', true)->count(),
            'sms_enabled' => $accounts->where('sms_checker_enabled', true)->count(),
            'promptpay' => $accounts->whereNotNull('promptpay_id')->count(),
        ];

        // อุปกรณ์ SMS Checker ที่ active
        $smsDevices = SmsCheckerDevice::where('status', 'active')->get();

        $banks = PaymentBankAccount::supportedBanks();

        return view('admin.payment-bank-accounts.index', compact(
            'accounts', 'stats', 'smsDevices', 'banks'
        ));
    }

    /**
     * แสดงฟอร์มเพิ่มบัญชีธนาคาร
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $banks = PaymentBankAccount::supportedBanks();

        return view('admin.payment-bank-accounts.create', compact('banks'));
    }

    /**
     * บันทึกบัญชีธนาคารใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_code' => 'required|string|max:20',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'promptpay_id' => 'nullable|string|max:50',
            'promptpay_type' => 'nullable|in:phone,citizen_id,ewallet',
            'qr_image' => 'nullable|image|max:2048',
            'sms_checker_enabled' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // อัพโหลด QR image
        if ($request->hasFile('qr_image')) {
            $validated['qr_image'] = $request->file('qr_image')
                ->store('payment-qr', 'public');
        }

        $validated['sms_checker_enabled'] = $request->boolean('sms_checker_enabled');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_default'] = $request->boolean('is_default');

        $account = PaymentBankAccount::create($validated);

        // ตั้งเป็นบัญชีหลักถ้าเลือก
        if ($account->is_default) {
            $account->setAsDefault();
        }

        return redirect()
            ->route('admin.payment-bank-accounts.index')
            ->with('success', 'เพิ่มบัญชีธนาคาร ' . $account->bank_name . ' สำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไขบัญชีธนาคาร
     *
     * @param PaymentBankAccount $account
     * @return \Illuminate\View\View
     */
    public function edit(PaymentBankAccount $account)
    {
        $banks = PaymentBankAccount::supportedBanks();

        return view('admin.payment-bank-accounts.edit', compact('account', 'banks'));
    }

    /**
     * อัพเดทบัญชีธนาคาร
     *
     * @param Request $request
     * @param PaymentBankAccount $account
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, PaymentBankAccount $account)
    {
        $validated = $request->validate([
            'bank_code' => 'required|string|max:20',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'promptpay_id' => 'nullable|string|max:50',
            'promptpay_type' => 'nullable|in:phone,citizen_id,ewallet',
            'qr_image' => 'nullable|image|max:2048',
            'sms_checker_enabled' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // อัพโหลด QR image ใหม่
        if ($request->hasFile('qr_image')) {
            // ลบรูปเก่า
            if ($account->qr_image) {
                Storage::disk('public')->delete($account->qr_image);
            }
            $validated['qr_image'] = $request->file('qr_image')
                ->store('payment-qr', 'public');
        }

        $validated['sms_checker_enabled'] = $request->boolean('sms_checker_enabled');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_default'] = $request->boolean('is_default');

        $account->update($validated);

        // ตั้งเป็นบัญชีหลักถ้าเลือก
        if ($account->is_default) {
            $account->setAsDefault();
        }

        return redirect()
            ->route('admin.payment-bank-accounts.index')
            ->with('success', 'อัพเดทบัญชี ' . $account->bank_name . ' สำเร็จ');
    }

    /**
     * ลบบัญชีธนาคาร (soft delete)
     *
     * @param PaymentBankAccount $account
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PaymentBankAccount $account)
    {
        $bankName = $account->bank_name;
        $account->delete();

        return redirect()
            ->route('admin.payment-bank-accounts.index')
            ->with('success', 'ลบบัญชี ' . $bankName . ' สำเร็จ');
    }

    /**
     * สลับสถานะ active/inactive
     *
     * @param PaymentBankAccount $account
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggle(PaymentBankAccount $account)
    {
        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->route('admin.payment-bank-accounts.index')
            ->with('success', "{$status}บัญชี {$account->bank_name} สำเร็จ");
    }

    /**
     * ตั้งเป็นบัญชีหลัก
     *
     * @param PaymentBankAccount $account
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setDefault(PaymentBankAccount $account)
    {
        $account->setAsDefault();

        return redirect()
            ->route('admin.payment-bank-accounts.index')
            ->with('success', 'ตั้ง ' . $account->bank_name . ' เป็นบัญชีหลักสำเร็จ');
    }
}
