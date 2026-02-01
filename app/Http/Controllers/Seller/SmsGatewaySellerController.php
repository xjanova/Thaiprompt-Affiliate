<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SmsCheckerDevice;
use App\Models\SmsGatewayBankAccount;
use App\Models\SmsGatewayPricing;
use App\Models\VendorStore;
use App\Services\SmsGatewaySubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * SMS Gateway Seller Controller
 *
 * จัดการ SMS Payment Gateway สำหรับ Seller Dashboard
 * - ดูสถานะ subscription + pricing
 * - สมัคร/ยกเลิก subscription
 * - จัดการอุปกรณ์ SMS Checker (เพิ่ม/ลบ/ดู QR)
 * - จัดการบัญชีธนาคาร
 */
class SmsGatewaySellerController extends Controller
{
    public function __construct(
        private SmsGatewaySubscriptionService $subscriptionService
    ) {}

    /**
     * Helper: ดึง store ของ seller ที่ login อยู่
     */
    private function getStore(): ?VendorStore
    {
        return VendorStore::where('user_id', Auth::id())->first();
    }

    // ========================================
    // MAIN PAGE + SUBSCRIPTION
    // ========================================

    /**
     * หน้าหลัก SMS Gateway — สถานะ + pricing plans
     *
     * GET /seller/sms-gateway
     */
    public function index()
    {
        $store = $this->getStore();
        if (!$store) {
            return redirect()->route('seller.dashboard')->with('error', 'ไม่พบร้านค้า');
        }

        $status = $this->subscriptionService->getStoreDashboardStatus($store->id);
        $plans = $this->subscriptionService->getAvailablePlans();

        return view('seller.sms-gateway.index', compact('store', 'status', 'plans'));
    }

    /**
     * สมัคร subscription
     *
     * POST /seller/sms-gateway/subscribe
     */
    public function subscribe(Request $request)
    {
        $store = $this->getStore();
        if (!$store) {
            return back()->with('error', 'ไม่พบร้านค้า');
        }

        $validator = Validator::make($request->all(), [
            'pricing_id' => 'required|exists:sms_gateway_pricing,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            // เช็คว่าร้านได้รับยกเว้นหรือไม่
            if ($this->subscriptionService->isExemptFromPayment($store->id)) {
                return back()->with('info', 'ร้านค้าของคุณได้รับสิทธิ์ใช้ SMS Gateway ฟรีแล้ว');
            }

            $subscription = $this->subscriptionService->subscribe(
                $store->id,
                Auth::id(),
                $request->input('pricing_id')
            );

            return back()->with('success', 'สมัครใช้งาน SMS Payment Gateway สำเร็จ!');
        } catch (\Exception $e) {
            Log::error('SMS Gateway subscribe failed: ' . $e->getMessage());
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * เริ่มทดลองใช้ฟรี
     *
     * POST /seller/sms-gateway/trial
     */
    public function startTrial(Request $request)
    {
        $store = $this->getStore();
        if (!$store) {
            return back()->with('error', 'ไม่พบร้านค้า');
        }

        $validator = Validator::make($request->all(), [
            'pricing_id' => 'required|exists:sms_gateway_pricing,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $subscription = $this->subscriptionService->startTrial(
                $store->id,
                Auth::id(),
                $request->input('pricing_id')
            );

            return back()->with('success', 'เริ่มทดลองใช้ฟรีสำเร็จ!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * ยกเลิก subscription
     *
     * POST /seller/sms-gateway/cancel
     */
    public function cancel(Request $request)
    {
        $store = $this->getStore();
        if (!$store) {
            return back()->with('error', 'ไม่พบร้านค้า');
        }

        $subscription = $this->subscriptionService->getActiveSubscription($store->id);
        if (!$subscription) {
            return back()->with('error', 'ไม่พบ subscription ที่ active');
        }

        $reason = $request->input('reason', 'Cancelled by seller');
        $this->subscriptionService->cancel($subscription->id, $reason);

        return back()->with('success', 'ยกเลิก subscription สำเร็จ ยังใช้งานได้จนกว่าจะหมดอายุ');
    }

    // ========================================
    // DEVICES MANAGEMENT
    // ========================================

    /**
     * รายการอุปกรณ์ SMS Checker ของร้าน
     *
     * GET /seller/sms-gateway/devices
     */
    public function devices()
    {
        $store = $this->getStore();
        if (!$store) {
            return redirect()->route('seller.dashboard')->with('error', 'ไม่พบร้านค้า');
        }

        $devices = SmsCheckerDevice::forStore($store->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $status = $this->subscriptionService->getStoreDashboardStatus($store->id);

        return view('seller.sms-gateway.devices', compact('store', 'devices', 'status'));
    }

    /**
     * สร้างอุปกรณ์ใหม่ + QR Code สำหรับเชื่อมต่อ
     *
     * POST /seller/sms-gateway/devices/create
     */
    public function createDevice(Request $request)
    {
        $store = $this->getStore();
        if (!$store) {
            return back()->with('error', 'ไม่พบร้านค้า');
        }

        // เช็คสิทธิ์
        if (!$this->subscriptionService->hasAccess($store->id)) {
            return back()->with('error', 'กรุณาสมัคร SMS Gateway ก่อน');
        }

        // เช็คจำนวนอุปกรณ์
        if (!$this->subscriptionService->canAddDevice($store->id)) {
            return back()->with('error', 'จำนวนอุปกรณ์เต็มตามแพ็กเกจแล้ว กรุณาอัพเกรดแพ็กเกจ');
        }

        $validator = Validator::make($request->all(), [
            'device_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $deviceId = 'SMSCHK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $device = SmsCheckerDevice::create([
            'device_id' => $deviceId,
            'device_name' => $request->input('device_name'),
            'api_key' => SmsCheckerDevice::generateApiKey(),
            'secret_key' => SmsCheckerDevice::generateSecretKey(),
            'platform' => 'android',
            'status' => 'active',
            'user_id' => Auth::id(),
            'store_id' => $store->id,
        ]);

        // สร้าง QR data สำหรับเชื่อมต่อ
        $qrData = json_encode([
            'device_id' => $device->device_id,
            'api_key' => $device->api_key,
            'secret_key' => $device->secret_key,
            'server_url' => config('app.url') . '/api/v1/sms-payment',
        ]);

        return back()->with([
            'success' => "สร้างอุปกรณ์ {$device->device_name} สำเร็จ",
            'new_device' => $device,
            'qr_data' => base64_encode($qrData),
        ]);
    }

    /**
     * ลบอุปกรณ์
     *
     * DELETE /seller/sms-gateway/devices/{id}
     */
    public function deleteDevice(int $id)
    {
        $store = $this->getStore();
        if (!$store) {
            return back()->with('error', 'ไม่พบร้านค้า');
        }

        $device = SmsCheckerDevice::where('id', $id)
            ->where('store_id', $store->id)
            ->first();

        if (!$device) {
            return back()->with('error', 'ไม่พบอุปกรณ์');
        }

        $device->update(['status' => 'inactive']);

        return back()->with('success', "ลบอุปกรณ์ {$device->device_name} แล้ว");
    }

    // ========================================
    // BANK ACCOUNTS MANAGEMENT
    // ========================================

    /**
     * รายการบัญชีธนาคาร
     *
     * GET /seller/sms-gateway/bank-accounts
     */
    public function bankAccounts()
    {
        $store = $this->getStore();
        if (!$store) {
            return redirect()->route('seller.dashboard')->with('error', 'ไม่พบร้านค้า');
        }

        $accounts = SmsGatewayBankAccount::forStore($store->id)
            ->ordered()
            ->get();

        return view('seller.sms-gateway.bank-accounts', compact('store', 'accounts'));
    }

    /**
     * สร้างบัญชีธนาคาร
     *
     * POST /seller/sms-gateway/bank-accounts/create
     */
    public function createBankAccount(Request $request)
    {
        $store = $this->getStore();
        if (!$store) {
            return back()->with('error', 'ไม่พบร้านค้า');
        }

        $validator = Validator::make($request->all(), [
            'bank_code' => 'required|string|max:20',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'branch' => 'nullable|string|max:255',
            'promptpay_id' => 'nullable|string|max:20',
            'promptpay_type' => 'nullable|in:phone,citizen_id,ewallet',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // ถ้าตั้งเป็น primary → ยกเลิก primary เดิม
        if ($request->boolean('is_primary')) {
            SmsGatewayBankAccount::forStore($store->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $supportedBanks = config('smschecker.supported_banks', []);

        SmsGatewayBankAccount::create([
            'store_id' => $store->id,
            'bank_code' => $request->input('bank_code'),
            'bank_name' => $supportedBanks[$request->input('bank_code')] ?? $request->input('bank_code'),
            'account_name' => $request->input('account_name'),
            'account_number' => $request->input('account_number'),
            'branch' => $request->input('branch'),
            'promptpay_id' => $request->input('promptpay_id'),
            'promptpay_type' => $request->input('promptpay_type'),
            'is_primary' => $request->boolean('is_primary'),
            'is_active' => true,
        ]);

        return back()->with('success', 'เพิ่มบัญชีธนาคารสำเร็จ');
    }

    /**
     * แก้ไขบัญชีธนาคาร
     *
     * PUT /seller/sms-gateway/bank-accounts/{id}
     */
    public function updateBankAccount(Request $request, int $id)
    {
        $store = $this->getStore();
        if (!$store) {
            return back()->with('error', 'ไม่พบร้านค้า');
        }

        $account = SmsGatewayBankAccount::where('id', $id)
            ->where('store_id', $store->id)
            ->first();

        if (!$account) {
            return back()->with('error', 'ไม่พบบัญชี');
        }

        $validator = Validator::make($request->all(), [
            'account_name' => 'sometimes|string|max:255',
            'account_number' => 'sometimes|string|max:50',
            'branch' => 'nullable|string|max:255',
            'promptpay_id' => 'nullable|string|max:20',
            'promptpay_type' => 'nullable|in:phone,citizen_id,ewallet',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // ถ้าตั้งเป็น primary → ยกเลิก primary เดิม
        if ($request->boolean('is_primary') && !$account->is_primary) {
            SmsGatewayBankAccount::forStore($store->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $account->update($request->only([
            'account_name', 'account_number', 'branch',
            'promptpay_id', 'promptpay_type', 'is_active', 'is_primary',
        ]));

        return back()->with('success', 'อัพเดทบัญชีธนาคารสำเร็จ');
    }

    /**
     * ลบบัญชีธนาคาร
     *
     * DELETE /seller/sms-gateway/bank-accounts/{id}
     */
    public function deleteBankAccount(int $id)
    {
        $store = $this->getStore();
        if (!$store) {
            return back()->with('error', 'ไม่พบร้านค้า');
        }

        $account = SmsGatewayBankAccount::where('id', $id)
            ->where('store_id', $store->id)
            ->first();

        if (!$account) {
            return back()->with('error', 'ไม่พบบัญชี');
        }

        $account->delete();

        return back()->with('success', 'ลบบัญชีธนาคารแล้ว');
    }
}
