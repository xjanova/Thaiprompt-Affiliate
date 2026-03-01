<?php

namespace App\Observers;

use App\Models\FortuneReferral;
use App\Models\MlmMember;
use Illuminate\Support\Facades\Log;

/**
 * MlmMember Observer
 *
 * เมื่อสร้าง MlmMember ใหม่จากช่องทางใดก็ตาม (MLM ทั่วไป, LINE signup, เว็บ, มือถือ)
 * → สร้าง FortuneReferral อัตโนมัติเพื่อเชื่อมสายงานดูดวง
 *
 * ทำให้ระบบ MLM ทั่วไปกับระบบดูดวงเชื่อมกันสมบูรณ์:
 * - คนที่มาจากลิงก์ MLM ทั่วไป → มี FortuneReferral → เห็นในคำสั่ง "สายงาน"
 * - ค่าคอมมิชชั่นดูดวง ทำงานผ่าน MlmMember chain อยู่แล้ว
 */
class MlmMemberObserver
{
    /**
     * เมื่อสร้าง MlmMember ใหม่
     *
     * ตรวจสอบว่ามี sponsor หรือไม่ → ถ้ามีและยังไม่มี FortuneReferral → สร้างให้
     */
    public function created(MlmMember $member): void
    {
        try {
            // ข้ามถ้าไม่มี sponsor (root member / super admin)
            $sponsorId = $member->unilevel_sponsor_id;
            if (empty($sponsorId)) {
                return;
            }

            // ข้ามถ้าไม่มี user_id (ไม่ควรเกิด แต่ป้องกันไว้)
            if (empty($member->user_id)) {
                return;
            }

            // หา sponsor member → ดึง user_id ของ sponsor
            $sponsorMember = MlmMember::find($sponsorId);
            if (! $sponsorMember || empty($sponsorMember->user_id)) {
                return;
            }

            $referrerUserId = $sponsorMember->user_id;
            $referredUserId = $member->user_id;

            // ข้ามถ้า sponsor กับ member เป็นคนเดียวกัน
            if ($referrerUserId === $referredUserId) {
                return;
            }

            // ✅ เช็คว่ามี FortuneReferral อยู่แล้วหรือไม่ (ไม่สร้างซ้ำ)
            $exists = FortuneReferral::where('referrer_user_id', $referrerUserId)
                ->where('referred_user_id', $referredUserId)
                ->exists();

            if ($exists) {
                return;
            }

            // ✅ สร้าง FortuneReferral เชื่อมสายงานดูดวง
            FortuneReferral::create([
                'referral_token' => FortuneReferral::generateToken(),
                'referrer_user_id' => $referrerUserId,
                'referrer_mlm_member_id' => $sponsorMember->id,
                'referred_user_id' => $referredUserId,
                'status' => FortuneReferral::STATUS_CONVERTED,
                'converted_at' => now(),
            ]);

            Log::info('MlmMemberObserver: สร้าง FortuneReferral อัตโนมัติ', [
                'mlm_member_id' => $member->id,
                'referrer_user_id' => $referrerUserId,
                'referred_user_id' => $referredUserId,
                'source' => 'mlm_member_created',
            ]);
        } catch (\Exception $e) {
            // ไม่ block การสร้าง MlmMember — แค่ log warning
            Log::warning('MlmMemberObserver: สร้าง FortuneReferral ไม่สำเร็จ', [
                'mlm_member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
