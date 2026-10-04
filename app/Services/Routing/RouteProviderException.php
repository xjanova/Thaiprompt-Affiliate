<?php

namespace App\Services\Routing;

/**
 * ผู้ให้บริการเส้นทางตอบไม่ได้ — ชนิดของปัญหาใช้ตัดสินว่าจะ "ตัดวงจร" (หยุดเรียกชั่วคราว) หรือไม่
 *
 * - unavailable  ต่อไม่ติด / หมดเวลา / 5xx → ตัดวงจรทันที (เช็คเอาต์ห้ามรอบริการที่ตาย)
 * - auth         คีย์ใช้ไม่ได้ (401/403) → ตัดวงจรนานกว่า (แก้เองไม่ได้จนแอดมินเปลี่ยนคีย์)
 * - no_route     หาเส้นทางคู่นี้ไม่เจอ (4xx อื่น / ไม่มี route) → ไม่ตัดวงจร ข้ามไปตัวถัดไปเฉพาะคู่นี้
 * - bad_response ตอบมาแต่อ่านไม่ได้ → นับเป็นความล้มเหลว (ครบ 3 ครั้งค่อยตัดวงจร)
 *
 * ข้อความ exception ห้ามมี URL เต็ม/คีย์ — ใส่เฉพาะชื่อผู้ให้บริการ + สถานะ
 */
class RouteProviderException extends \RuntimeException
{
    public const UNAVAILABLE = 'unavailable';

    public const AUTH = 'auth';

    public const NO_ROUTE = 'no_route';

    public const BAD_RESPONSE = 'bad_response';

    public function __construct(public readonly string $kind, string $message)
    {
        parent::__construct($message);
    }
}
