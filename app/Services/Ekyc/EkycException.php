<?php

namespace App\Services\Ekyc;

use RuntimeException;

/**
 * ข้อผิดพลาดของขั้นตอน eKYC ที่ส่งกลับแอปได้ตรงๆ (รหัส + ข้อความไทย + HTTP status)
 *
 * รหัสตามสัญญา: EKYC_CONSENT_REQUIRED · EKYC_SESSION_EXPIRED · EKYC_ALREADY_VERIFIED
 *   EKYC_TOO_MANY_ATTEMPTS (429) · EKYC_BAD_IMAGE · EKYC_CHALLENGE_MISMATCH
 * รหัสเสริม: EKYC_SESSION_NOT_FOUND (404) · EKYC_PENDING_REVIEW (409) · EKYC_CARD_REQUIRED (409)
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
}
