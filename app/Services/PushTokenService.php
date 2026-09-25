<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🔔 จัดการ push token ตอนผู้ใช้ออกจากระบบ
 *
 * ระบบเก็บ push token 2 ที่ (ประวัติศาสตร์):
 *   - mobile_devices.push_token  ← ExpoPushService::sendToUser() ส่งจริงจากตารางนี้
 *   - user_notification_tokens   ← POST /api/v1/push/token (endpoint เก่า)
 * ตอน logout ต้องถอดทั้งสองที่ ไม่งั้นเครื่องที่ออกจากระบบแล้วยังได้แจ้งเตือนของบัญชีเดิม
 */
class PushTokenService
{
    /**
     * ถอด push token/เครื่อง ออกจากผู้ใช้คนนี้ (แตะเฉพาะแถวที่เป็นของผู้ใช้คนนี้เท่านั้น)
     *
     * @return int จำนวนแถวที่ถูกถอด/ลบ
     */
    public static function detachForUser(int $userId, ?string $token, ?string $deviceId = null): int
    {
        $token = $token !== null ? trim($token) : null;
        $deviceId = $deviceId !== null ? trim($deviceId) : null;

        if (($token === null || $token === '') && ($deviceId === null || $deviceId === '')) {
            return 0;
        }

        $affected = 0;

        if (Schema::hasTable('mobile_devices')) {
            // ปลด user_id ออกจากเครื่อง (คงแถวไว้ — ใช้นับสถิติ/ส่ง broadcast ทั่วไปต่อได้)
            $affected += DB::table('mobile_devices')
                ->where('user_id', $userId)
                ->where(function ($q) use ($token, $deviceId) {
                    $q->whereRaw('1 = 0');
                    if ($token !== null && $token !== '') {
                        $q->orWhere('push_token', $token);
                    }
                    if ($deviceId !== null && $deviceId !== '') {
                        $q->orWhere('device_id', $deviceId);
                    }
                })
                ->update(['user_id' => null, 'updated_at' => now()]);
        }

        if (Schema::hasTable('user_notification_tokens')) {
            $affected += DB::table('user_notification_tokens')
                ->where('user_id', $userId)
                ->where(function ($q) use ($token, $deviceId) {
                    $q->whereRaw('1 = 0');
                    if ($token !== null && $token !== '') {
                        $q->orWhere('token', $token);
                    }
                    if ($deviceId !== null && $deviceId !== '') {
                        $q->orWhere('device_id', $deviceId);
                    }
                })
                ->delete();
        }

        return $affected;
    }
}
