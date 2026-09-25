<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * ข้อผิดพลาดทางธุรกิจของระบบงานไรเดอร์ (ไม่ใช่บั๊ก)
 *
 * ทุกตัวมี:
 *   - errorCode  รหัส UPPER_SNAKE ให้แอปแยกกรณีได้ (เช่น JOB_TAKEN)
 *   - message    ข้อความภาษาไทยที่แสดงให้ผู้ใช้ได้ทันที
 *   - httpStatus 403 = ไม่มีสิทธิ์/ไม่ผ่านเงื่อนไข, 404 = ไม่พบ, 409 = ชนกัน/สถานะไม่ถูก, 422 = ข้อมูลไม่ครบ
 *
 * ถ้า controller ไม่ได้ catch เอง Laravel จะเรียก render() ให้อัตโนมัติ
 * → API ได้ JSON {success:false, code, message} / เว็บได้ redirect back พร้อม flash error
 */
class RiderJobException extends RuntimeException
{
    public const JOB_TAKEN = 'JOB_TAKEN';

    public const NOT_ELIGIBLE = 'NOT_ELIGIBLE';

    public const HAS_ACTIVE_JOB = 'HAS_ACTIVE_JOB';

    public const INSUFFICIENT_COD_CREDIT = 'INSUFFICIENT_COD_CREDIT';

    public const SELF_ORDER = 'SELF_ORDER';

    public const INVALID_TRANSITION = 'INVALID_TRANSITION';

    public const NOT_YOUR_JOB = 'NOT_YOUR_JOB';

    public const JOB_NOT_FOUND = 'JOB_NOT_FOUND';

    public const PHOTO_REQUIRED = 'PHOTO_REQUIRED';

    public const COD_CONFIRM_REQUIRED = 'COD_CONFIRM_REQUIRED';

    public const INVALID_REASON = 'INVALID_REASON';

    public const INVALID_LOCATION = 'INVALID_LOCATION';

    public const OUT_OF_SERVICE_AREA = 'OUT_OF_SERVICE_AREA';

    public const COD_LIMIT_EXCEEDED = 'COD_LIMIT_EXCEEDED';

    public const INVALID_AVAILABILITY = 'INVALID_AVAILABILITY';

    public const SOURCE_NOT_DISPATCHABLE = 'SOURCE_NOT_DISPATCHABLE';

    /**
     * @param  string  $errorCode  รหัสข้อผิดพลาด (ค่าคงที่ในคลาสนี้)
     * @param  string  $message  ข้อความภาษาไทยสำหรับผู้ใช้
     * @param  int  $httpStatus  HTTP status ที่ควรตอบ
     * @param  array<string, mixed>  $context  ข้อมูลประกอบ (ส่งกลับใน data ได้ เช่น active_job_id)
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus = 409,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    // =====================================================
    // ตัวสร้างสำเร็จรูป (ข้อความไทยอยู่ที่เดียว)
    // =====================================================

    public static function jobTaken(): self
    {
        return new self(self::JOB_TAKEN, 'งานนี้ถูกไรเดอร์คนอื่นรับไปแล้ว', 409);
    }

    public static function notEligible(string $reason, string $blockCode = 'NOT_ELIGIBLE'): self
    {
        return new self(self::NOT_ELIGIBLE, $reason, 403, ['block_code' => $blockCode]);
    }

    public static function hasActiveJob(?int $activeJobId = null): self
    {
        return new self(
            self::HAS_ACTIVE_JOB,
            'คุณมีงานที่ยังไม่เสร็จอยู่ กรุณาทำงานเดิมให้เสร็จก่อน',
            409,
            ['active_job_id' => $activeJobId]
        );
    }

    public static function insufficientCodCredit(float $required, float $available): self
    {
        return new self(
            self::INSUFFICIENT_COD_CREDIT,
            'งานนี้เก็บเงินปลายทาง ต้องมียอดในวอลเลตอย่างน้อย ฿'.number_format($required, 2)
                .' (ตอนนี้มี ฿'.number_format($available, 2).') กรุณาเติมเงินก่อนรับงาน',
            403,
            ['required' => round($required, 2), 'available' => round($available, 2)]
        );
    }

    public static function selfOrder(): self
    {
        return new self(self::SELF_ORDER, 'ไม่สามารถรับงานส่งออเดอร์ของตัวเองหรือร้านของตัวเองได้', 403);
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self(
            self::INVALID_TRANSITION,
            'ไม่สามารถเปลี่ยนสถานะงานจาก "'.self::statusLabel($from).'" เป็น "'.self::statusLabel($to).'" ได้',
            409,
            ['from' => $from, 'to' => $to]
        );
    }

    public static function notYourJob(): self
    {
        return new self(self::NOT_YOUR_JOB, 'งานนี้ไม่ใช่งานของคุณ', 403);
    }

    public static function jobNotFound(): self
    {
        return new self(self::JOB_NOT_FOUND, 'ไม่พบงานนี้', 404);
    }

    public static function photoRequired(string $what = 'ส่งของ'): self
    {
        return new self(self::PHOTO_REQUIRED, "กรุณาถ่ายรูปยืนยันการ{$what}", 422);
    }

    public static function codConfirmRequired(float $amount): self
    {
        return new self(
            self::COD_CONFIRM_REQUIRED,
            'งานนี้เก็บเงินปลายทาง ฿'.number_format($amount, 2).' กรุณายืนยันว่าเก็บเงินจากลูกค้าแล้ว',
            422
        );
    }

    public static function invalidReason(): self
    {
        return new self(self::INVALID_REASON, 'กรุณาเลือกเหตุผลที่ส่งไม่สำเร็จให้ถูกต้อง', 422);
    }

    public static function invalidLocation(string $which = 'จุดส่ง'): self
    {
        return new self(self::INVALID_LOCATION, "ไม่พบพิกัด{$which}ที่ถูกต้อง กรุณาปักหมุดตำแหน่งก่อน", 422);
    }

    public static function outOfServiceArea(float $distanceKm, float $maxKm): self
    {
        return new self(
            self::OUT_OF_SERVICE_AREA,
            'ระยะทาง '.number_format($distanceKm, 1).' กม. เกินพื้นที่ให้บริการไรเดอร์ (สูงสุด '
                .number_format($maxKm, 1).' กม.)',
            422,
            ['distance_km' => round($distanceKm, 2), 'max_distance_km' => round($maxKm, 2)]
        );
    }

    public static function codLimitExceeded(float $amount, float $max): self
    {
        return new self(
            self::COD_LIMIT_EXCEEDED,
            'ยอดเก็บเงินปลายทาง ฿'.number_format($amount, 2).' เกินวงเงินที่ไรเดอร์รับได้ (฿'
                .number_format($max, 2).') กรุณาชำระล่วงหน้า',
            422,
            ['cod_amount' => round($amount, 2), 'max_cod_amount' => round($max, 2)]
        );
    }

    public static function invalidAvailability(): self
    {
        return new self(self::INVALID_AVAILABILITY, 'สถานะการรับงานไม่ถูกต้อง', 422);
    }

    /**
     * ออเดอร์ต้นทางไม่อยู่ในสถานะที่เรียกไรเดอร์ได้ (ยกเลิก/คืนเงิน/ส่งถึงแล้ว/ยังไม่จ่าย)
     */
    public static function sourceNotDispatchable(): self
    {
        return new self(
            self::SOURCE_NOT_DISPATCHABLE,
            'ออเดอร์นี้ไม่อยู่ในสถานะที่เรียกไรเดอร์ได้ (อาจถูกยกเลิก คืนเงิน หรือส่งถึงแล้ว)',
            409
        );
    }

    // =====================================================
    // การแสดงผล
    // =====================================================

    /**
     * รูปแบบ JSON ตามมาตรฐาน API ของโปรเจกต์
     *
     * @return array{success: bool, code: string, message: string, data: array<string, mixed>|null}
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'data' => $this->context ?: null,
        ];
    }

    /**
     * Laravel เรียกอัตโนมัติเมื่อ exception หลุดออกจาก controller
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($this->toArray(), $this->httpStatus);
        }

        return redirect()->back()->with('error', $this->getMessage());
    }

    /**
     * เป็นข้อผิดพลาดทางธุรกิจ ไม่ต้องลง error log (ลงแค่ระดับ info ไว้ตามรอย)
     */
    public function report(): void
    {
        Log::info('RiderJobException', [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context,
        ]);
    }

    /**
     * ชื่อสถานะงานภาษาไทย (ใช้ในข้อความ error)
     */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'รอไรเดอร์รับงาน',
            'accepted' => 'รับงานแล้ว',
            'picking_up' => 'กำลังไปรับของ',
            'picked_up' => 'รับของแล้ว',
            'delivering' => 'กำลังจัดส่ง',
            'delivered' => 'ส่งแล้ว',
            'completed' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิก',
            'failed' => 'ส่งไม่สำเร็จ',
            default => $status,
        };
    }
}
