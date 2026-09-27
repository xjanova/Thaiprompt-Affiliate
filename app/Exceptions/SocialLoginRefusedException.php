<?php

namespace App\Exceptions;

use Exception;

/**
 * ปฏิเสธการเข้าสู่ระบบด้วย LINE / Facebook / Google ด้วยเหตุผลที่ "ตั้งใจให้ผู้ใช้เห็น"
 *
 * ข้อความเป็นภาษาไทยที่เขียนเอง แสดงบนหน้าเว็บ/แอปได้ตรงๆ
 *
 * ⚠️ สืบทอดจาก Exception ไม่ใช่ RuntimeException โดยตั้งใจ —
 *    QueryException (มี SQL + อีเมล/hash รหัสผ่านอยู่ในข้อความ) เป็นลูกของ RuntimeException
 *    ถ้า catch RuntimeException แล้วเอาข้อความไปแสดง = ข้อมูลในฐานข้อมูลหลุดถึงหน้าจอผู้ใช้
 *    ผู้เรียก catch คลาสนี้เท่านั้นเพื่อแสดงข้อความ ที่เหลือทั้งหมด log แล้วแสดงข้อความกลางๆ
 */
final class SocialLoginRefusedException extends Exception
{
    /**
     * @param  string  $userMessage  ข้อความภาษาไทยที่แสดงผู้ใช้
     * @param  string  $reason  รหัสเหตุผลสำหรับ log (ไม่แสดงผู้ใช้)
     */
    public function __construct(string $userMessage, private readonly string $reason = 'refused')
    {
        parent::__construct($userMessage);
    }

    /**
     * รหัสเหตุผล (email_taken / two_factor / suspended / identity_taken ...)
     */
    public function reason(): string
    {
        return $this->reason;
    }
}
