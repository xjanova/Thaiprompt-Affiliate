<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * ข้อผิดพลาดของการติดตามไรเดอร์ฝั่งผู้ซื้อ (ข้อความไทยพร้อมแสดงผู้ใช้ + code สำหรับแอป)
 *
 * @example
 * throw DeliveryTrackingException::make('JOB_NOT_ACTIVE', 'การจัดส่งจบแล้ว', 409);
 */
class DeliveryTrackingException extends RuntimeException
{
    public function __construct(
        string $message,
        protected string $errorCode = 'DELIVERY_TRACKING_ERROR',
        protected int $httpStatus = 422
    ) {
        parent::__construct($message);
    }

    public static function make(string $errorCode, string $message, int $httpStatus = 422): self
    {
        return new self($message, $errorCode, $httpStatus);
    }

    /**
     * รหัสข้อผิดพลาด (UPPER_SNAKE)
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * HTTP status ที่ควรตอบ
     */
    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
