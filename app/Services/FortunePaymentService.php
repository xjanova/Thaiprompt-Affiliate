<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\SmsPaymentNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Payment Service
 *
 * จัดการการชำระเงินดูดวงผ่านระบบ SMS Payment Checker
 *
 * Flow:
 * 1. SMS ตรวจจับยอดเงินพิเศษ (เช่น 29.99 = ดูดวง)
 * 2. สร้าง FortuneReading อัตโนมัติ
 * 3. ถ้าจับคู่ User ได้ → เชื่อมกับ user_id
 * 4. ถ้าจับคู่ไม่ได้ → สร้างเป็น "บิลลอย" (floating bill)
 */
class FortunePaymentService
{
    /**
     * สร้าง FortuneReading จาก SMS notification ที่ตรงกับยอดพิเศษ
     *
     * @param SmsPaymentNotification $notification SMS notification ที่ตรวจจับได้
     * @param array $specialAmountConfig config ของยอดพิเศษ (type, name, reading_type, ...)
     * @return FortuneReading
     */
    public function createFromSmsNotification(
        SmsPaymentNotification $notification,
        array $specialAmountConfig
    ): FortuneReading {
        // พยายามหา User จากข้อมูลผู้โอน
        $user = $this->findUserFromNotification($notification);
        $isFloating = $user === null;

        // สร้าง FortuneReading
        $reading = FortuneReading::create([
            'user_id' => $user?->id,
            'facebook_user_id' => $user?->facebook_id ?? 'sms_' . $notification->id,
            'facebook_user_name' => $notification->sender_or_receiver ?? 'ลูกค้า SMS',
            'questions' => ['รอลูกค้าถามคำถาม (ชำระเงินผ่าน SMS แล้ว)'],
            'ai_response' => '',
            'ai_provider' => 'pending',
            'reading_type' => $specialAmountConfig['reading_type'] ?? 'deep',
            'is_paid' => true,
            'amount_paid' => $notification->amount,
            'paid_at' => now(),
            'sms_notification_id' => $notification->id,
            'sender_info' => $this->buildSenderInfo($notification),
            'sender_bank' => $notification->bank,
            'is_floating' => $isFloating,
            'response_type' => 'private_message',
        ]);

        // อัพเดท notification สถานะเป็น matched
        $notification->update([
            'status' => 'matched',
        ]);

        Log::info('Fortune Payment: สร้างรายการดูดวงจาก SMS', [
            'reading_id' => $reading->id,
            'notification_id' => $notification->id,
            'amount' => $notification->amount,
            'user_id' => $user?->id,
            'is_floating' => $isFloating,
            'sender_info' => $notification->sender_or_receiver,
            'bank' => $notification->bank,
        ]);

        return $reading;
    }

    /**
     * พยายามหา User จากข้อมูล SMS notification
     *
     * ลำดับการค้นหา:
     * 1. ค้นจากชื่อผู้โอน (sender_or_receiver)
     * 2. ค้นจากเลขบัญชี (account_number)
     *
     * @param SmsPaymentNotification $notification
     * @return User|null
     */
    protected function findUserFromNotification(SmsPaymentNotification $notification): ?User
    {
        $sender = $notification->sender_or_receiver;
        $account = $notification->account_number;

        // ค้นจากชื่อผู้โอน (ตรงทั้งชื่อ)
        if (!empty($sender)) {
            $user = User::where('name', $sender)->first();
            if ($user) {
                return $user;
            }

            // ค้นจากส่วนหนึ่งของชื่อ (first_name / last_name)
            $user = User::where('first_name', $sender)
                ->orWhere('last_name', $sender)
                ->first();
            if ($user) {
                return $user;
            }
        }

        // ค้นจากเลขบัญชี (ถ้ามี)
        if (!empty($account)) {
            $user = User::where('bank_account_number', $account)->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * สร้างข้อมูลผู้โอน (sender info) สำหรับแสดงใน admin
     *
     * @param SmsPaymentNotification $notification
     * @return string
     */
    protected function buildSenderInfo(SmsPaymentNotification $notification): string
    {
        $parts = [];

        if (!empty($notification->sender_or_receiver)) {
            $parts[] = $notification->sender_or_receiver;
        }
        if (!empty($notification->account_number)) {
            $parts[] = 'บัญชี: ' . $notification->account_number;
        }
        if (!empty($notification->reference_number)) {
            $parts[] = 'Ref: ' . $notification->reference_number;
        }

        return implode(' | ', $parts) ?: 'ไม่ทราบข้อมูลผู้โอน';
    }

    /**
     * เชื่อมบิลลอยกับ User (admin assign)
     *
     * เมื่อ admin ระบุตัวตนลูกค้าได้แล้ว
     *
     * @param FortuneReading $reading
     * @param User $user
     * @return FortuneReading
     */
    public function assignFloatingBill(FortuneReading $reading, User $user): FortuneReading
    {
        $reading->update([
            'user_id' => $user->id,
            'is_floating' => false,
        ]);

        Log::info('Fortune Payment: เชื่อมบิลลอยกับผู้ใช้', [
            'reading_id' => $reading->id,
            'user_id' => $user->id,
        ]);

        return $reading;
    }

    /**
     * ตรวจสอบว่ายอดเงินเป็นยอดพิเศษสำหรับบริการใดหรือไม่
     *
     * @param float $amount ยอดเงินที่ตรวจจับได้
     * @return array|null config ของยอดพิเศษ หรือ null ถ้าไม่ใช่
     */
    public static function findSpecialAmount(float $amount): ?array
    {
        $specialAmounts = config('smschecker.special_amounts', []);

        foreach ($specialAmounts as $specialAmount => $config) {
            // เปรียบเทียบยอดเงิน (tolerance 0.001 สำหรับ floating point)
            if (abs((float) $specialAmount - $amount) < 0.001) {
                return $config;
            }
        }

        return null;
    }
}
