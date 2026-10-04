<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\HandoverException;
use App\Exceptions\RiderJobException;
use App\Exceptions\ShopException;
use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Services\Rider\HandoverService;
use App\Services\RiderAccountService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * 🤝 API ส่งมอบของ (ไรเดอร์รอบ 2 — เลน money) · routes/api_v1/rider_r2_handover.php
 *
 * ผู้ซื้อ:  GET  /orders/{source}/{id}/handover · POST .../scan {token} · POST .../dispute {reason, note?}
 * ไรเดอร์: GET  /rider/jobs/{id}/handover · POST .../scan {token?|code?, latitude, longitude}
 *          POST .../arrival-photo · POST .../waited-photo (multipart {photo, latitude, longitude})
 *
 * ตรรกะทั้งหมดอยู่ที่ HandoverService — ที่นี่ตรวจข้อมูลเข้า + แปลง error เป็น JSON มาตรฐาน (ข้อความไทย ไม่หลุดข้อความดิบ)
 */
class HandoverApiController extends Controller
{
    use SellerAppResponses;

    public function __construct(
        private readonly HandoverService $handovers,
        private readonly RiderAccountService $accounts,
    ) {}

    // =====================================================
    // ผู้ซื้อ
    // =====================================================

    public function buyerShow(Request $request, string $source, int $id): JsonResponse
    {
        return $this->guard('buyer_show', function () use ($request, $source, $id) {
            return $this->ok($this->handovers->buyerView($request->user(), $source, $id), 'ดึงข้อมูลการส่งมอบสำเร็จ');
        }, ['source' => $source, 'order_id' => $id]);
    }

    public function buyerScan(Request $request, string $source, int $id): JsonResponse
    {
        return $this->guard('buyer_scan', function () use ($request, $source, $id) {
            $data = $this->validateOrFail($request, [
                'token' => ['required', 'string', 'max:200'],
            ], [
                'token.required' => 'กรุณาสแกน QR บนมือถือของไรเดอร์',
                'token.string' => 'QR ไม่ถูกต้อง',
                'token.max' => 'QR ไม่ถูกต้อง',
            ]);
            if ($data instanceof JsonResponse) {
                return $data;
            }

            $payload = $this->handovers->buyerScan($request->user(), $source, $id, (string) $data['token']);

            return $this->ok($payload, $payload['handover']['status'] === 'completed'
                ? 'ยืนยันรับของเรียบร้อย ขอบคุณที่ใช้บริการ'
                : 'ยืนยันฝั่งคุณแล้ว รอไรเดอร์สแกน QR ของคุณ');
        }, ['source' => $source, 'order_id' => $id]);
    }

    public function buyerDispute(Request $request, string $source, int $id): JsonResponse
    {
        return $this->guard('buyer_dispute', function () use ($request, $source, $id) {
            $data = $this->validateOrFail($request, [
                'reason' => ['required', Rule::in(HandoverService::DISPUTE_REASONS)],
                'note' => ['nullable', 'string', 'max:1000', 'required_if:reason,other'],
            ], [
                'reason.required' => 'กรุณาเลือกเหตุผลที่ร้องเรียน',
                'reason.in' => 'เหตุผลที่ร้องเรียนไม่ถูกต้อง',
                'note.required_if' => 'กรุณาระบุรายละเอียดเพิ่มเติม',
                'note.max' => 'รายละเอียดยาวเกินไป (ไม่เกิน 1,000 ตัวอักษร)',
                'note.string' => 'รายละเอียดไม่ถูกต้อง',
            ]);
            if ($data instanceof JsonResponse) {
                return $data;
            }

            return $this->ok(
                $this->handovers->buyerDispute($request->user(), $source, $id, (string) $data['reason'], $data['note'] ?? null),
                'ส่งเรื่องให้ทีมงานตรวจสอบแล้ว ระบบพักเงินไว้จนกว่าจะได้ข้อสรุป'
            );
        }, ['source' => $source, 'order_id' => $id]);
    }

    // =====================================================
    // ไรเดอร์
    // =====================================================

    public function riderShow(Request $request, int $id): JsonResponse
    {
        return $this->guard('rider_show', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);

            return $this->ok($this->handovers->riderView($rider, $this->findJob($id)), 'ดึงข้อมูลการส่งมอบสำเร็จ');
        }, ['job_id' => $id]);
    }

    public function riderScan(Request $request, int $id): JsonResponse
    {
        return $this->guard('rider_scan', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);

            $data = $this->validateOrFail($request, [
                'token' => ['nullable', 'string', 'max:200'],
                'code' => ['nullable', 'string', 'max:12', 'required_without:token'],
            ], [
                'code.required_without' => 'กรุณาสแกน QR ของผู้รับ หรือกรอกรหัส 6 หลักที่ผู้รับแจ้ง',
                'code.max' => 'รหัสต้องเป็นตัวเลข 6 หลัก',
                'code.string' => 'รหัสต้องเป็นตัวเลข 6 หลัก',
                'token.string' => 'QR ไม่ถูกต้อง',
                'token.max' => 'QR ไม่ถูกต้อง',
            ]);
            if ($data instanceof JsonResponse) {
                return $data;
            }

            $payload = $this->handovers->riderScan(
                $rider,
                $this->findJob($id),
                isset($data['token']) ? (string) $data['token'] : null,
                isset($data['code']) ? (string) $data['code'] : null,
                $request->input('latitude'),
                $request->input('longitude'),
            );

            return $this->ok($payload, $payload['handover']['status'] === 'completed'
                ? 'ส่งมอบสำเร็จ! รายได้เข้ากระเป๋าเงินของคุณแล้ว'
                : 'ยืนยันฝั่งไรเดอร์แล้ว ให้ผู้รับสแกน QR บนมือถือของคุณ');
        }, ['job_id' => $id]);
    }

    public function arrivalPhoto(Request $request, int $id): JsonResponse
    {
        return $this->guard('arrival_photo', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            $photo = $this->validatedPhoto($request);
            if ($photo instanceof JsonResponse) {
                return $photo;
            }

            return $this->ok(
                $this->handovers->arrivalPhoto($rider, $this->findJob($id), $photo, $request->input('latitude'), $request->input('longitude')),
                'บันทึกรูปถึงจุดส่งแล้ว ระบบแจ้งผู้รับให้ออกมารับของ'
            );
        }, ['job_id' => $id]);
    }

    public function waitedPhoto(Request $request, int $id): JsonResponse
    {
        return $this->guard('waited_photo', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            $photo = $this->validatedPhoto($request);
            if ($photo instanceof JsonResponse) {
                return $photo;
            }

            return $this->ok(
                $this->handovers->waitedPhoto($rider, $this->findJob($id), $photo, $request->input('latitude'), $request->input('longitude')),
                'บันทึกการวางของแล้ว คุณรับงานใหม่ได้เลย รายได้จะเข้ากระเป๋าเมื่อระบบปลดเงิน'
            );
        }, ['job_id' => $id]);
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * รูปถ่ายยืนยัน (jpg/png/webp/heic ไม่เกิน 10MB)
     */
    private function validatedPhoto(Request $request): UploadedFile|JsonResponse
    {
        $data = $this->validateOrFail($request, [
            'photo' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,heic,heif', 'max:10240'],
        ], [
            'photo.required' => 'กรุณาถ่ายรูปยืนยัน',
            'photo.file' => 'รูปยืนยันต้องเป็นไฟล์',
            'photo.uploaded' => 'อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่',
            'photo.mimes' => 'รูปยืนยันต้องเป็นรูป jpg, png, webp หรือ heic',
            'photo.max' => 'รูปยืนยันต้องมีขนาดไม่เกิน 10MB',
        ]);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        return $data['photo'];
    }

    private function riderOrFail(Request $request): Rider
    {
        $rider = $this->accounts->findForUser($request->user());

        if (! $rider) {
            throw new HttpResponseException($this->fail('NOT_RIDER', 'กรุณาสมัครเป็นไรเดอร์ก่อน', 403));
        }

        return $rider;
    }

    private function findJob(int $id): RiderJob
    {
        $job = RiderJob::find($id);

        if (! $job) {
            throw RiderJobException::jobNotFound();
        }

        return $job;
    }

    /**
     * ครอบทุก action: error ทางธุรกิจ → JSON {success:false, code, message, data?} · อื่นๆ → log + ข้อความกลาง
     *
     * @param  array<string, mixed>  $context
     */
    private function guard(string $action, callable $callback, array $context = []): JsonResponse
    {
        try {
            return $callback();
        } catch (HandoverException $e) {
            return response()->json($e->toArray(), $e->httpStatus);
        } catch (RiderJobException $e) {
            return $this->fail($e->errorCode, $e->getMessage(), $e->httpStatus, $e->context);
        } catch (ShopException $e) {
            return $this->fail('HANDOVER_FAILED', $e->getMessage(), 409);
        } catch (HttpResponseException|HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('HandoverApi: '.$action.' failed', array_merge($context, [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile().':'.$e->getLine(),
            ]));

            return $this->fail('SERVER_ERROR', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500);
        }
    }
}
