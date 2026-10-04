<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * ข้อผิดพลาดทางธุรกิจของการส่งมอบของ (ไรเดอร์รอบ 2 — สแกน QR ใส่กัน / รหัส 6 หลัก / รูป 2 รอบ / ร้องเรียน)
 *
 * รหัสตามสัญญากลาง (CONTRACT.md · Money lane):
 *   HANDOVER_NOT_READY(409) HANDOVER_FINAL(409) HANDOVER_TOKEN_INVALID(422) HANDOVER_TOKEN_EXPIRED(422)
 *   HANDOVER_CODE_INVALID(422) HANDOVER_CODE_LOCKED(429) LOCATION_REQUIRED(422) TOO_FAR_FROM_DROPOFF(422, data.distance_m)
 *   WAIT_NOT_OVER(409, data.wait_until) DISPUTE_NOT_ALLOWED(409)
 *
 * ข้อความเป็นภาษาไทยที่แสดงผู้ใช้ได้ทันที — ห้ามใส่ข้อความ exception ดิบ
 */
class HandoverException extends RuntimeException
{
    public const NOT_READY = 'HANDOVER_NOT_READY';

    public const FINAL = 'HANDOVER_FINAL';

    public const TOKEN_INVALID = 'HANDOVER_TOKEN_INVALID';

    public const TOKEN_EXPIRED = 'HANDOVER_TOKEN_EXPIRED';

    public const CODE_INVALID = 'HANDOVER_CODE_INVALID';

    public const CODE_LOCKED = 'HANDOVER_CODE_LOCKED';

    public const LOCATION_REQUIRED = 'LOCATION_REQUIRED';

    public const TOO_FAR = 'TOO_FAR_FROM_DROPOFF';

    public const WAIT_NOT_OVER = 'WAIT_NOT_OVER';

    public const DISPUTE_NOT_ALLOWED = 'DISPUTE_NOT_ALLOWED';

    public const NOT_FOUND = 'ORDER_NOT_FOUND';

    /**
     * @param  array<string, mixed>  $context  ข้อมูลประกอบที่ส่งกลับใน data (เช่น distance_m, wait_until)
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus = 409,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public static function notReady(string $message = 'ยังส่งมอบไม่ได้ในตอนนี้ กรุณารอให้ไรเดอร์รับของก่อน'): self
    {
        return new self(self::NOT_READY, $message, 409);
    }

    public static function final(): self
    {
        return new self(self::FINAL, 'การส่งมอบนี้ปิดไปแล้ว', 409);
    }

    public static function tokenInvalid(): self
    {
        return new self(self::TOKEN_INVALID, 'QR นี้ใช้ส่งมอบออเดอร์นี้ไม่ได้ กรุณาสแกน QR บนหน้าจอของอีกฝ่าย', 422);
    }

    public static function tokenExpired(): self
    {
        return new self(self::TOKEN_EXPIRED, 'QR หมดอายุแล้ว กรุณาสแกน QR ใหม่ที่เพิ่งเปลี่ยน', 422);
    }

    public static function codeInvalid(int $attemptsLeft): self
    {
        return new self(
            self::CODE_INVALID,
            'รหัสไม่ถูกต้อง เหลือโอกาสอีก '.$attemptsLeft.' ครั้ง',
            422,
            ['attempts_left' => $attemptsLeft]
        );
    }

    public static function codeLocked(Carbon $until): self
    {
        return new self(
            self::CODE_LOCKED,
            'กรอกรหัสผิดหลายครั้ง กรุณาลองใหม่หลังเวลา '.$until->copy()->timezone(config('app.timezone') ?: 'Asia/Bangkok')->format('H:i').' น. หรือให้ผู้รับสแกน QR แทน',
            429,
            ['locked_until' => $until->toIso8601String()]
        );
    }

    public static function locationRequired(): self
    {
        return new self(self::LOCATION_REQUIRED, 'ไม่พบตำแหน่งปัจจุบัน กรุณาเปิด GPS แล้วลองใหม่', 422);
    }

    public static function tooFar(int $distanceM, int $geofenceM): self
    {
        return new self(
            self::TOO_FAR,
            'คุณอยู่ห่างจุดส่ง '.number_format($distanceM).' ม. ต้องอยู่ในระยะ '.number_format($geofenceM).' ม. จึงจะส่งมอบได้',
            422,
            ['distance_m' => $distanceM, 'geofence_m' => $geofenceM]
        );
    }

    public static function waitNotOver(Carbon $waitUntil): self
    {
        return new self(
            self::WAIT_NOT_OVER,
            'ยังไม่ครบเวลารอผู้รับ ถ่ายรูปรอบที่ 2 ได้หลังเวลา '.$waitUntil->copy()->timezone(config('app.timezone') ?: 'Asia/Bangkok')->format('H:i:s').' น.',
            409,
            ['wait_until' => $waitUntil->toIso8601String()]
        );
    }

    public static function disputeNotAllowed(): self
    {
        return new self(self::DISPUTE_NOT_ALLOWED, 'ร้องเรียนการส่งมอบนี้ไม่ได้ในตอนนี้', 409);
    }

    public static function orderNotFound(): self
    {
        return new self(self::NOT_FOUND, 'ไม่พบคำสั่งซื้อนี้', 404);
    }

    /**
     * รูปแบบ JSON ตาม SellerAppResponses: {success:false, code, message, data?}
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $body = ['success' => false, 'code' => $this->errorCode, 'message' => $this->getMessage()];

        if ($this->context !== []) {
            $body['data'] = $this->context;
        }

        return $body;
    }

    /**
     * Laravel เรียกอัตโนมัติเมื่อหลุดออกจาก controller
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json($this->toArray(), $this->httpStatus);
    }

    /**
     * ข้อผิดพลาดทางธุรกิจ ไม่ต้องลง error log
     */
    public function report(): void {}
}
