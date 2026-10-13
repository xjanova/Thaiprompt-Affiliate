<?php

namespace App\Services\TpixPay;

use RuntimeException;

/**
 * ข้อผิดพลาดของระบบชำระด้วย TPIX ที่แสดงให้ผู้ใช้เห็นได้ (ข้อความภาษาไทย ไม่มีรายละเอียดภายใน)
 */
class TpixPayException extends RuntimeException
{
    public const DISABLED = 'TPIX_DISABLED';

    public const PRICE_UNAVAILABLE = 'TPIX_PRICE_UNAVAILABLE';

    public const LIMIT_EXCEEDED = 'TPIX_LIMIT_EXCEEDED';

    public const INVALID_AMOUNT = 'TPIX_INVALID_AMOUNT';

    public const QUOTE_FAILED = 'TPIX_QUOTE_FAILED';

    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
