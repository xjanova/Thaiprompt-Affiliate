<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * ข้อผิดพลาดทางธุรกิจของตลาดสด (ข้อความภาษาไทยพร้อมแสดงผู้ใช้ได้ทันที)
 *
 * ใช้แยก "ข้อผิดพลาดที่ผู้ใช้ต้องรู้" (สินค้าหมด, เงินไม่พอ, สถานะไม่ถูกต้อง ฯลฯ)
 * ออกจาก exception ของระบบ — controller แสดง getMessage() ได้ตรงๆ
 * ส่วน exception อื่นต้อง log แล้วตอบข้อความกลางเท่านั้น (ห้ามส่งข้อความดิบให้ client)
 *
 * @example
 * throw FreshMarketException::make('OUT_OF_STOCK', 'สินค้าไม่เพียงพอ', 409);
 */
class FreshMarketException extends RuntimeException
{
    /**
     * @param  string  $message  ข้อความภาษาไทยสำหรับผู้ใช้
     * @param  string  $errorCode  รหัสข้อผิดพลาดแบบ UPPER_SNAKE สำหรับแอป
     * @param  int  $httpStatus  HTTP status ที่ควรตอบกลับ
     */
    public function __construct(
        string $message,
        protected string $errorCode = 'FRESH_MARKET_ERROR',
        protected int $httpStatus = 422
    ) {
        parent::__construct($message);
    }

    /**
     * สร้าง exception แบบสั้น
     */
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
