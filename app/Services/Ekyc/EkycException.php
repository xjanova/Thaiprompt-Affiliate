<?php

namespace App\Services\Ekyc;

use RuntimeException;

/**
 * ข้อผิดพลาดของขั้นตอน eKYC ที่ส่งกลับแอปได้ตรงๆ (รหัส + ข้อความไทย + HTTP status)
 *
 * รหัสตามสัญญา: EKYC_CONSENT_REQUIRED · EKYC_SESSION_EXPIRED · EKYC_ALREADY_VERIFIED
 *   EKYC_TOO_MANY_ATTEMPTS (429) · EKYC_BAD_IMAGE · EKYC_CHALLENGE_MISMATCH
 * รหัสเสริม: EKYC_SESSION_NOT_FOUND (404) · EKYC_PENDING_REVIEW (409) · EKYC_CARD_REQUIRED (409)
 * รหัสรอบแก้ผลรีวิว (2026-10-04): EKYC_AI_BUSY (503 ลองซ้ำคำขอเดิม) · EKYC_CARD_LIMIT (429 เริ่มรอบใหม่)
 *   EKYC_PROCESSING (409 รอบก่อนยังตรวจอยู่ → แอปถามสถานะซ้ำ) · EKYC_SESSION_DONE (409 รอบนี้ตัดสินแล้ว)
 *   EKYC_CONSENT_OUTDATED (422 ข้อความยินยอมเปลี่ยน → อัปเดตแอป) · EKYC_DUPLICATE_ID (409 ฝั่งแอดมิน)
 */
class EkycException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422,
        public readonly array $data = [],
    ) {
        parent::__construct($message);
    }

    public static function consentRequired(): self
    {
        return new self('EKYC_CONSENT_REQUIRED', 'กรุณายอมรับการเก็บข้อมูลใบหน้าและบัตรประชาชนก่อนเริ่มยืนยันตัวตน', 422);
    }

    public static function alreadyVerified(): self
    {
        return new self('EKYC_ALREADY_VERIFIED', 'บัญชีนี้ยืนยันตัวตนเรียบร้อยแล้ว', 409);
    }

    public static function pendingReview(): self
    {
        return new self('EKYC_PENDING_REVIEW', 'ข้อมูลของคุณอยู่ระหว่างให้เจ้าหน้าที่ตรวจ จะแจ้งผลให้ทราบโดยเร็ว', 409);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function tooManyAttempts(array $data = []): self
    {
        return new self('EKYC_TOO_MANY_ATTEMPTS', 'วันนี้ลองยืนยันตัวตนครบจำนวนครั้งแล้ว กรุณาลองใหม่พรุ่งนี้', 429, $data);
    }

    public static function sessionNotFound(): self
    {
        return new self('EKYC_SESSION_NOT_FOUND', 'ไม่พบรอบยืนยันตัวตนนี้ กรุณาเริ่มใหม่', 404);
    }

    public static function sessionExpired(string $message = 'รอบยืนยันตัวตนนี้หมดเวลาแล้ว กรุณาเริ่มใหม่'): self
    {
        return new self('EKYC_SESSION_EXPIRED', $message, 410);
    }

    public static function cardRequired(): self
    {
        return new self('EKYC_CARD_REQUIRED', 'กรุณาถ่ายรูปบัตรประชาชนก่อน', 409);
    }

    public static function badImage(string $message = 'รูปไม่ถูกต้อง กรุณาถ่ายใหม่อีกครั้ง'): self
    {
        return new self('EKYC_BAD_IMAGE', $message, 422);
    }

    public static function challengeMismatch(): self
    {
        return new self('EKYC_CHALLENGE_MISMATCH', 'ลำดับท่าทางไม่ตรงกับที่ระบบให้ทำ กรุณาเริ่มสแกนใบหน้าใหม่', 422);
    }

    /**
     * บริการ AI คิวเต็ม/ตรวจไม่ทันชั่วคราว — สถานะรอบไม่เปลี่ยน แอปส่งคำขอเดิมซ้ำได้ (ไม่เสียสิทธิ์)
     */
    public static function aiBusy(int $retryAfterSeconds = 15): self
    {
        return new self('EKYC_AI_BUSY', 'ระบบตรวจมีคนใช้เยอะ กรุณาลองใหม่ในอีกสักครู่', 503, ['retry_after_seconds' => $retryAfterSeconds]);
    }

    /**
     * ส่งรูปบัตรในรอบเดียวเกินเพดาน — ต้องเริ่มรอบใหม่
     */
    public static function cardLimit(): self
    {
        return new self('EKYC_CARD_LIMIT', 'ส่งรูปบัตรในรอบนี้หลายครั้งเกินไป กรุณาเริ่มยืนยันตัวตนใหม่', 429);
    }

    /**
     * รอบก่อนหน้ายังตรวจอยู่ (เช่นเน็ตหลุดแล้วกดส่งซ้ำ) — แอปถามสถานะซ้ำแทนการเริ่มใหม่
     */
    public static function processing(): self
    {
        return new self('EKYC_PROCESSING', 'ระบบกำลังตรวจข้อมูลของคุณอยู่ กรุณารอสักครู่', 409);
    }

    /**
     * รอบนี้ตัดสินผลไปแล้ว — แอปดูผลจาก GET /ekyc/status
     */
    public static function sessionDone(): self
    {
        return new self('EKYC_SESSION_DONE', 'รอบยืนยันตัวตนนี้ตรวจเสร็จแล้ว', 409);
    }

    /**
     * แอปส่งเวอร์ชันข้อความยินยอมไม่ตรงกับที่ใช้อยู่ — ต้องอัปเดตแอปเพื่อดูข้อความใหม่
     */
    public static function consentOutdated(): self
    {
        return new self('EKYC_CONSENT_OUTDATED', 'ข้อความยินยอมมีการเปลี่ยนแปลง กรุณาอัปเดตแอปแล้วลองใหม่', 422);
    }
}
