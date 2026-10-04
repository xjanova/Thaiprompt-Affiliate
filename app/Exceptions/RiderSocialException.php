<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * ข้อผิดพลาดทางธุรกิจของหัวใจไรเดอร์ / ไรเดอร์คนโปรด / ล็อกเรียก (ไรเดอร์รอบ 2, 2026-10-04)
 *
 * ข้อความเป็นภาษาไทยพร้อมแสดงผู้ใช้ได้ทันที · data = ข้อมูลประกอบที่ส่งกลับแอปได้ (ห้ามใส่ข้อมูลลับ)
 *
 * @example
 * throw RiderSocialException::make('HEART_NOT_ALLOWED', 'ให้หัวใจได้หลังไรเดอร์ส่งของสำเร็จ', 409);
 */
class RiderSocialException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus = 422,
        public readonly array $data = [],
    ) {
        parent::__construct($message);
    }

    /**
     * สร้าง exception แบบสั้น
     *
     * @param  array<string, mixed>  $data
     */
    public static function make(string $code, string $message, int $status = 422, array $data = []): self
    {
        return new self($code, $message, $status, $data);
    }
}
