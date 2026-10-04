<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * ข้อผิดพลาดทางธุรกิจของคำขอส่งไรเดอร์จาก POS (ไม่ใช่บั๊ก)
 *
 * รูปแบบตอบกลับเดียวกับ ShopException: {success:false, code, message, data?}
 * ข้อความเป็นภาษาไทยที่แสดงผู้ใช้ได้ทันที · data ห้ามมีข้อมูลลับ (เบอร์โทร / ยอดเงินคงเหลือ / token)
 */
class PosDeliveryException extends RuntimeException
{
    public const VALIDATION = 'VALIDATION';

    public const ITEMS_NOT_IN_STORE = 'ITEMS_NOT_IN_STORE';

    public const RIDER_UNAVAILABLE = 'RIDER_UNAVAILABLE';

    public const NOT_CANCELLABLE = 'NOT_CANCELLABLE';

    public const REQUEST_NOT_FOUND = 'REQUEST_NOT_FOUND';

    public const REQUEST_EXPIRED = 'REQUEST_EXPIRED';

    public const REQUEST_ALREADY_PAID = 'REQUEST_ALREADY_PAID';

    public const REQUEST_CANCELLED = 'REQUEST_CANCELLED';

    public const IDEMPOTENCY_KEY_REQUIRED = 'IDEMPOTENCY_KEY_REQUIRED';

    public const PIN_NOT_SET = 'PIN_NOT_SET';

    public const INVALID_PIN = 'INVALID_PIN';

    public const WALLET_LOCKED = 'WALLET_LOCKED';

    /**
     * @param  array<string, mixed>  $context  ข้อมูลประกอบที่ส่งกลับใน data ได้ (ห้ามใส่ข้อมูลลับ)
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus = 422,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function make(string $code, string $message, int $status = 422, array $context = []): self
    {
        return new self($code, $message, $status, $context);
    }

    public static function notFound(): self
    {
        return self::make(self::REQUEST_NOT_FOUND, 'ไม่พบคำขอชำระเงินนี้ กรุณาสแกน QR ใหม่จากร้าน', 404);
    }

    public static function expired(): self
    {
        return self::make(self::REQUEST_EXPIRED, 'QR นี้หมดอายุแล้ว กรุณาให้ร้านสร้าง QR ใหม่', 410);
    }

    public static function alreadyPaid(): self
    {
        return self::make(self::REQUEST_ALREADY_PAID, 'คำขอนี้ชำระเงินไปแล้ว', 409);
    }

    public static function cancelled(): self
    {
        return self::make(self::REQUEST_CANCELLED, 'ร้านยกเลิกคำขอนี้แล้ว', 409);
    }

    /**
     * JSON ตามรูปแบบ API กลาง {success:false, code, message, data?}
     */
    public function toJsonResponse(): JsonResponse
    {
        $payload = [
            'success' => false,
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
        ];

        if ($this->context !== []) {
            $payload['data'] = $this->context;
        }

        return response()->json($payload, $this->httpStatus);
    }

    /**
     * Laravel เรียกอัตโนมัติเมื่อไม่มีใคร catch
     */
    public function render(Request $request): JsonResponse
    {
        return $this->toJsonResponse();
    }
}
