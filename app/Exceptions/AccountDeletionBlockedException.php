<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * ลบบัญชีไม่ได้เพราะยังมีเงื่อนไขค้างอยู่ (ยอดเงินในกระเป๋า / ออเดอร์ที่ยังไม่จบ / งานไรเดอร์ ฯลฯ)
 *
 * ข้อความทุกข้อเป็นภาษาไทย แสดงให้ผู้ใช้เห็นได้ตรงๆ
 */
class AccountDeletionBlockedException extends RuntimeException
{
    /**
     * @param  array<int, array{code: string, message: string}>  $blockers
     */
    public function __construct(private readonly array $blockers)
    {
        parent::__construct($blockers[0]['message'] ?? 'ยังลบบัญชีไม่ได้ในขณะนี้');
    }

    /**
     * รายการเหตุผลที่ลบไม่ได้ทั้งหมด
     *
     * @return array<int, array{code: string, message: string}>
     */
    public function blockers(): array
    {
        return $this->blockers;
    }

    /**
     * โค้ดของเหตุผลแรก (ใช้เป็น code หลักของ API response)
     */
    public function primaryCode(): string
    {
        return $this->blockers[0]['code'] ?? 'ACCOUNT_DELETION_BLOCKED';
    }
}
