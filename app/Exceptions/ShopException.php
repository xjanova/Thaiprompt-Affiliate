<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * ข้อผิดพลาดทางธุรกิจของระบบร้านค้า (ตะกร้า / checkout / ออเดอร์ / คูปอง) — ไม่ใช่บั๊ก
 *
 * ทุกตัวมี errorCode (UPPER_SNAKE ให้แอปแยกกรณี), ข้อความไทยที่แสดงผู้ใช้ได้ทันที และ HTTP status
 * 403 = ไม่มีสิทธิ์, 404 = ไม่พบ, 409 = สถานะชนกัน/ซ้ำ, 422 = ข้อมูลไม่ครบหรือใช้ไม่ได้
 */
class ShopException extends RuntimeException
{
    public const CART_EMPTY = 'CART_EMPTY';

    public const CART_ITEM_NOT_FOUND = 'CART_ITEM_NOT_FOUND';

    public const PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';

    public const STORE_NOT_FOUND = 'STORE_NOT_FOUND';

    public const PRODUCT_UNAVAILABLE = 'PRODUCT_UNAVAILABLE';

    public const AFFILIATE_PRODUCT = 'AFFILIATE_PRODUCT';

    public const OUT_OF_STOCK = 'OUT_OF_STOCK';

    public const ADDRESS_REQUIRED = 'ADDRESS_REQUIRED';

    public const ADDRESS_NOT_FOUND = 'ADDRESS_NOT_FOUND';

    public const ADDRESS_LOCATION_REQUIRED = 'ADDRESS_LOCATION_REQUIRED';

    public const RIDER_NOT_AVAILABLE = 'RIDER_NOT_AVAILABLE';

    public const COD_NOT_AVAILABLE = 'COD_NOT_AVAILABLE';

    public const PAYMENT_METHOD_UNAVAILABLE = 'PAYMENT_METHOD_UNAVAILABLE';

    public const INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';

    public const WALLET_INACTIVE = 'WALLET_INACTIVE';

    public const COUPON_INVALID = 'COUPON_INVALID';

    public const CHECKOUT_IN_PROGRESS = 'CHECKOUT_IN_PROGRESS';

    public const ORDER_NOT_FOUND = 'ORDER_NOT_FOUND';

    public const ACTION_NOT_ALLOWED = 'ACTION_NOT_ALLOWED';

    public const MULTI_SELLER_ORDER = 'MULTI_SELLER_ORDER';

    public const NOT_A_SELLER = 'NOT_A_SELLER';

    public const REFUND_FAILED = 'REFUND_FAILED';

    public const STORE_SUSPENDED = 'STORE_SUSPENDED';

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

    public static function make(string $code, string $message, int $status = 422, array $context = []): self
    {
        return new self($code, $message, $status, $context);
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
     * Laravel เรียกอัตโนมัติเมื่อไม่มีใคร catch — API ได้ JSON, เว็บได้ redirect back พร้อม flash error
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->toJsonResponse();
        }

        return back()->with('error', $this->getMessage());
    }
}
