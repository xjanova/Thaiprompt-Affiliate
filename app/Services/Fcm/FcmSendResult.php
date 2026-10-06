<?php

namespace App\Services\Fcm;

/**
 * ผลการส่ง FCM หนึ่งเครื่อง
 */
final class FcmSendResult
{
    /**
     * @param  bool  $ok  ส่งสำเร็จ
     * @param  bool  $invalidToken  FCM บอกว่า token ใช้ไม่ได้แล้ว (ควรลบ)
     * @param  string|null  $error  รหัส error จาก FCM (ไม่มี token ปน)
     * @param  int  $status  HTTP status (0 = ไม่ได้ยิง / exception)
     */
    public function __construct(
        public readonly bool $ok,
        public readonly bool $invalidToken,
        public readonly ?string $error,
        public readonly int $status,
    ) {}
}
