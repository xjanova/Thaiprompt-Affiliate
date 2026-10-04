<?php

namespace App\Services\Media;

use RuntimeException;

/**
 * ข้อผิดพลาดของรูปโปรไฟล์ถ่ายสด — ข้อความภาษาไทยส่งให้ผู้ใช้ได้ตรงๆ (ไม่มีรายละเอียดระบบ)
 */
class ProfilePhotoException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    /** ไฟล์ไม่ใช่รูป / ถอดรหัสรูปไม่ได้ */
    public static function invalid(): self
    {
        return new self('PHOTO_INVALID', 'ไฟล์นี้ไม่ใช่รูปภาพหรือรูปเสียหาย กรุณาถ่ายรูปใหม่');
    }

    /** ด้านสั้นเล็กเกินไปจนเห็นหน้าไม่ชัด */
    public static function tooSmall(): self
    {
        return new self('PHOTO_TOO_SMALL', 'รูปเล็กเกินไป กรุณาถ่ายใหม่ให้เห็นหน้าชัดเจน');
    }

    /** จำนวนพิกเซลเกินที่เครื่องแม่ข่ายถอดรหัสได้อย่างปลอดภัย */
    public static function tooLarge(): self
    {
        return new self('PHOTO_TOO_LARGE', 'รูปมีความละเอียดสูงเกินไป กรุณาถ่ายใหม่อีกครั้ง');
    }

    /** เขียนไฟล์/บันทึกข้อมูลไม่สำเร็จ */
    public static function saveFailed(): self
    {
        return new self('PHOTO_SAVE_FAILED', 'บันทึกรูปไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
    }
}
