<?php

namespace App\Services\Juntra;

use RuntimeException;

/**
 * งานของจันทราทำไม่สำเร็จด้วยเหตุที่จันทราต้องรู้ — reason_code ให้โค้ดตัดสิน, message ให้คนอ่าน
 *
 * status 503 = ลองใหม่ได้ (จันทราเก็บงานไว้ส่งซ้ำ) · 4xx = ข้อมูลผิด ส่งซ้ำก็ไม่ผ่าน
 */
class JuntraAffiliateException extends RuntimeException
{
    public function __construct(
        public readonly string $reasonCode,
        string $message,
        public readonly int $status = 503,
    ) {
        parent::__construct($message);
    }
}
