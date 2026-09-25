<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\RiderJobException;
use App\Http\Controllers\Concerns\RiderJobActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rider\RiderRegistrationRequest;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Services\RiderAccountService;
use App\Services\RiderDispatchService;
use App\Services\RiderEarningService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * API ไรเดอร์สำหรับแอปมือถือ — /api/v1/rider/* (auth:sanctum)
 *
 * รูปแบบคำตอบ (มาตรฐานโปรเจกต์):
 *   สำเร็จ  {"success": true,  "message": "ข้อความไทย", "data": {...}}
 *   ผิดพลาด {"success": false, "code": "UPPER_SNAKE", "message": "ข้อความไทย", "data": {...}|null}
 *   403 = ไม่มีสิทธิ์/ไม่ผ่านเงื่อนไข, 404 = ไม่พบ, 409 = ชนกัน/สถานะไม่ถูก, 422 = ข้อมูลไม่ครบ
 *
 * ตัวเลขทุกตัวเป็น JSON number (ไม่ใช่ string ทศนิยม) — ใช้ RiderJob::toApiSummary/toApiDetail
 * และ RiderAccountService::statusPayload เป็นตัวแปลงข้อมูลเท่านั้น
 *
 * ❗ ห้ามเปลี่ยนสถานะงานเองในไฟล์นี้ — ทุกขั้นตอนเรียก RiderJobService (ผ่าน trait RiderJobActions)
 * ❗ ห้ามคืนข้อความ exception ดิบให้แอป — log ไว้แล้วตอบข้อความไทย
 */
class RiderApiController extends Controller
{
    use RiderJobActions;

    public function __construct(
        private readonly RiderAccountService $accounts,
        private readonly RiderDispatchService $dispatch,
        private readonly RiderEarningService $earnings,
    ) {}

    // =====================================================
    // บัญชีไรเดอร์
    // =====================================================

    /**
     * GET /rider/status — สถานะไรเดอร์ของผู้ใช้ (rider = null เมื่อยังไม่สมัคร)
     */
    public function status(Request $request): JsonResponse
    {
        return $this->guard('status', function () use ($request) {
            $rider = $this->accounts->findForUser($request->user());

            return $this->ok([
                'is_rider' => $rider !== null,
                'rider' => $rider ? $this->riderPayload($rider) : null,
            ], $rider ? 'ดึงสถานะไรเดอร์สำเร็จ' : 'คุณยังไม่ได้สมัครเป็นไรเดอร์');
        });
    }

    /**
     * POST /rider/register — สมัคร / ส่งใบสมัครใหม่หลังถูกปฏิเสธ / แก้ใบสมัครที่รอตรวจ
     */
    public function register(RiderRegistrationRequest $request): JsonResponse
    {
        return $this->guard('register', function () use ($request) {
            ['rider' => $rider, 'outcome' => $outcome] = $this->accounts->register($request->user(), $request->riderData());

            if ($outcome === 'exists') {
                return $this->error('ALREADY_REGISTERED', 'คุณเป็นไรเดอร์อยู่แล้ว ไม่ต้องสมัครใหม่', 409, [
                    'rider' => $this->riderPayload($rider),
                ]);
            }

            $message = match ($outcome) {
                'created' => 'สมัครเป็นไรเดอร์สำเร็จ กรุณาอัปโหลดเอกสารให้ครบเพื่อรอการอนุมัติ',
                'reapplied' => 'ส่งใบสมัครใหม่แล้ว ทีมงานจะตรวจสอบอีกครั้ง',
                default => 'อัปเดตใบสมัครแล้ว',
            };

            return $this->ok([
                'outcome' => $outcome,
                'rider' => $this->riderPayload($rider),
            ], $message, $outcome === 'created' ? 201 : 200);
        });
    }

    /**
     * POST /rider/document — อัปโหลดเอกสาร 1 รายการ (multipart: type + image)
     *
     * เก็บบน private disk เท่านั้น — ไม่คืน path ของไฟล์
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        return $this->guard('upload_document', function () use ($request) {
            $rider = $this->riderOrFail($request);

            $file = $request->file('image') ?? $request->file('document') ?? $request->file('file');

            $data = $this->validateRiderInput($request, [
                'type' => ['required', Rule::in(array_keys(RiderAccountService::DOCUMENT_TYPES))],
                'image' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,heic,heif', 'max:10240'],
            ], [], ['image' => $file]);

            $rider = $this->accounts->storeDocument($rider, $data['type'], $file);

            return $this->ok([
                'type' => $data['type'],
                'uploaded' => true,
                'url' => $this->signedDocumentUrl($rider, $data['type']),
                'documents' => $this->accounts->documentFlags($rider),
                'documents_missing' => $this->accounts->missingDocuments($rider),
                'documents_complete' => $this->accounts->documentsComplete($rider),
                'documents_pending_review' => $this->accounts->documentsChangedAt($rider) !== null,
            ], 'อัปโหลดเอกสารสำเร็จ');
        });
    }

    /**
     * GET /rider/documents — รายการเอกสาร + ลิงก์เปิดดูชั่วคราว (30 นาที)
     */
    public function documents(Request $request): JsonResponse
    {
        return $this->guard('documents', function () use ($request) {
            $rider = $this->riderOrFail($request);

            return $this->ok([
                'documents' => $this->accounts->documentList($rider, fn (Rider $r, string $t) => $this->signedDocumentUrl($r, $t)),
                'required' => $this->accounts->requiredDocumentTypes($rider),
                'missing' => $this->accounts->missingDocuments($rider),
                'complete' => $this->accounts->documentsComplete($rider),
                'pending_review' => $this->accounts->documentsChangedAt($rider) !== null,
            ], 'ดึงรายการเอกสารสำเร็จ');
        });
    }

    /**
     * GET /rider/documents/file/{rider}/{type} — เปิดไฟล์เอกสารของตัวเองผ่าน signed URL (middleware signed)
     */
    public function documentFile(Rider $rider, string $type): StreamedResponse
    {
        if (! array_key_exists($type, RiderAccountService::DOCUMENT_TYPES)) {
            abort(404);
        }

        return $this->accounts->documentResponse($rider, $type);
    }

    /**
     * POST /rider/permissions — สิทธิ์ในเครื่อง + ความยินยอมแชร์ตำแหน่งให้ลูกค้าระหว่างงาน
     *
     * gps = อนุญาตตำแหน่ง "ขณะใช้แอป" ก็พอ (ไม่บังคับ "ตลอดเวลา")
     */
    public function permissions(Request $request): JsonResponse
    {
        return $this->guard('permissions', function () use ($request) {
            $rider = $this->riderOrFail($request);

            $data = $this->validateRiderInput($request, [
                'gps' => ['nullable', 'boolean'],
                'gps_background' => ['nullable', 'boolean'],
                'camera' => ['nullable', 'boolean'],
                'microphone' => ['nullable', 'boolean'],
                'notification' => ['nullable', 'boolean'],
                'location_consent' => ['nullable', 'boolean'],
            ]);

            $rider = $this->accounts->updatePermissions($rider, $data);
            $payload = $this->riderPayload($rider);

            return $this->ok([
                'permissions' => $payload['permissions'],
                'location_consent_at' => $payload['location_consent_at'],
                'can_accept_jobs' => $payload['can_accept_jobs'],
                'block_reason' => $payload['block_reason'],
            ], 'บันทึกสิทธิ์เรียบร้อย');
        });
    }

    /**
     * PUT /rider/profile — แก้เบอร์/ยานพาหนะ/ความชอบงาน
     *
     * เปลี่ยนยานพาหนะหลังอนุมัติ → แอดมินต้องตรวจซ้ำ (documents_pending_review = true) + อาจต้องอัปโหลดเอกสารเพิ่ม
     */
    public function updateProfile(Request $request): JsonResponse
    {
        return $this->guard('update_profile', function () use ($request) {
            $rider = $this->riderOrFail($request);

            $request->merge([
                'phone' => preg_replace('/[\s\-().]+/', '', (string) $request->input('phone')),
            ]);

            $data = $this->validateRiderInput($request, RiderAccountService::profileRules(), RiderAccountService::profileMessages());

            ['rider' => $rider, 'vehicle_changed' => $vehicleChanged, 'missing' => $missing] = $this->accounts->updateProfile($rider, $data);

            $message = 'บันทึกข้อมูลไรเดอร์แล้ว';
            if ($vehicleChanged && $missing !== []) {
                $message .= ' — กรุณาอัปโหลด'.$this->accounts->documentLabels($missing).'เพิ่มเติม';
            }

            return $this->ok([
                'vehicle_changed' => $vehicleChanged,
                'rider' => $this->riderPayload($rider),
            ], $message);
        });
    }

    /**
     * POST /rider/availability — เปิด/ปิดรับงาน {availability: online|offline, latitude?, longitude?}
     */
    public function availability(Request $request): JsonResponse
    {
        return $this->guard('availability', function () use ($request) {
            $rider = $this->riderOrFail($request);

            $data = $this->validateRiderInput($request, [
                'availability' => ['required', Rule::in(['online', 'offline'])],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ]);

            // แนบพิกัดมาด้วย → บันทึกก่อน ให้รับงานได้ทันทีหลังเปิด
            if ($data['availability'] === 'online') {
                $this->recordLocationFromRequest($request, $rider);
            }

            $rider = $this->accounts->setAvailability($rider, $data['availability']);
            $payload = $this->riderPayload($rider);

            return $this->ok([
                'availability' => $payload['availability'],
                'availability_text' => $payload['availability_text'],
                'can_accept_jobs' => $payload['can_accept_jobs'],
                'block_reason' => $payload['block_reason'],
                'active_job_id' => $payload['active_job_id'],
            ], $data['availability'] === 'online' ? 'เปิดรับงานแล้ว' : 'ปิดรับงานแล้ว');
        });
    }

    /**
     * POST /rider/location — ส่งตำแหน่งปัจจุบัน (ค่า heading/speed ติดลบ = ไม่ทราบ ไม่ถือว่าผิด)
     */
    public function location(Request $request): JsonResponse
    {
        return $this->guard('location', function () use ($request) {
            $rider = $this->riderOrFail($request);

            $result = $this->doRecordLocation($request, $rider);
            $rider->refresh();

            return $this->ok([
                'has_active_job' => (bool) $result['has_active_job'],
                'job_id' => $result['job_id'] !== null ? (int) $result['job_id'] : null,
                'is_tracking' => (bool) $result['is_tracking'],
                'gps_resumed' => (bool) $result['gps_resumed'],
                'availability' => (string) $rider->availability,
                'server_time' => now()->toIso8601String(),
            ], 'อัปเดตตำแหน่งสำเร็จ');
        });
    }

    // =====================================================
    // งาน
    // =====================================================

    /**
     * GET /rider/jobs/available?latitude&longitude — งานที่รอรับใกล้ตัว
     *
     * ยังไม่เปิดรับงาน/มีงานค้าง → 200 + jobs ว่าง + reason (แอปใช้แสดงการ์ดที่ถูกต้อง ไม่ใช่ error)
     */
    public function availableJobs(Request $request): JsonResponse
    {
        return $this->guard('available_jobs', function () use ($request) {
            $rider = $this->riderOrFail($request);

            $data = $this->validateRiderInput($request, [
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ]);

            if ($rider->status !== 'approved') {
                $reason = $rider->onlineBlockReason() ?? ['code' => 'NOT_APPROVED', 'message' => 'บัญชีไรเดอร์ยังไม่พร้อมใช้งาน'];

                return $this->error('NOT_APPROVED', $reason['message'], 403, ['block_reason' => $reason]);
            }

            $base = [
                'jobs' => [],
                'count' => 0,
                'reason' => null,
                'active_job_id' => null,
                'can_accept_jobs' => false,
                'block_reason' => $rider->acceptBlockReason(),
                'rider_location' => $this->riderLocation($rider),
            ];

            if ($active = $rider->activeJob()) {
                return $this->ok(array_merge($base, ['reason' => 'busy', 'active_job_id' => (int) $active->id]), 'คุณมีงานที่กำลังทำอยู่');
            }

            if ($rider->availability !== 'online') {
                return $this->ok(array_merge($base, ['reason' => 'offline']), 'คุณยังปิดรับงานอยู่ กดเปิดรับงานเพื่อดูงานใกล้คุณ');
            }

            $lat = isset($data['latitude'], $data['longitude']) ? (float) $data['latitude'] : null;
            $lng = isset($data['latitude'], $data['longitude']) ? (float) $data['longitude'] : null;

            $jobs = $this->dispatch->availableJobsFor($rider, $lat, $lng, 30)
                ->reject(fn (RiderJob $job) => $this->rejectedByRider($job, $rider))
                ->values();

            $hasLocation = ($lat !== null && $lng !== null) || ($rider->last_latitude !== null && $rider->last_longitude !== null);

            return $this->ok(array_merge($base, [
                'jobs' => $jobs->map(fn (RiderJob $job) => $job->toApiSummary($rider))->all(),
                'count' => $jobs->count(),
                'reason' => $hasLocation ? null : 'no_location',
                'can_accept_jobs' => $rider->acceptBlockReason() === null,
            ]), $jobs->isEmpty() ? 'ยังไม่มีงานใกล้คุณตอนนี้' : 'ดึงรายการงานสำเร็จ');
        });
    }

    /**
     * GET /rider/jobs/current — งานที่กำลังทำ (accepted → delivering)
     */
    public function currentJob(Request $request): JsonResponse
    {
        return $this->guard('current_job', function () use ($request) {
            $rider = $this->riderOrFail($request);
            $job = $rider->activeJob();

            return $this->ok([
                'has_job' => $job !== null,
                'job' => $job ? $job->toApiDetail($rider) : null,
                'is_tracking' => $job !== null,
            ], $job ? 'ดึงงานปัจจุบันสำเร็จ' : 'ยังไม่มีงานที่กำลังทำ');
        });
    }

    /**
     * GET /rider/jobs/history?page&status&per_page — ประวัติงานที่จบแล้วของตัวเอง
     */
    public function history(Request $request): JsonResponse
    {
        return $this->guard('history', function () use ($request) {
            $rider = $this->riderOrFail($request);

            $data = $this->validateRiderInput($request, [
                'status' => ['nullable', Rule::in(RiderJob::TERMINAL_STATUSES)],
                'per_page' => ['nullable', 'integer', 'between:5,50'],
                'page' => ['nullable', 'integer', 'min:1'],
            ]);

            $page = RiderJob::query()
                ->where('rider_id', $rider->id)
                ->whereIn('status', isset($data['status']) ? [$data['status']] : RiderJob::TERMINAL_STATUSES)
                ->orderByDesc('id')
                ->paginate((int) ($data['per_page'] ?? 20));

            return $this->ok([
                'jobs' => collect($page->items())->map(fn (RiderJob $job) => $job->toApiSummary($rider))->all(),
                'pagination' => $this->pagination($page),
            ], 'ดึงประวัติงานสำเร็จ');
        });
    }

    /**
     * GET /rider/jobs/{id} — รายละเอียดงาน (ของตัวเอง หรืองานที่ยังเปิดรับและเสนอให้ตัวเองได้)
     */
    public function showJob(Request $request, int $id): JsonResponse
    {
        return $this->guard('show_job', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            $job = $this->findJob($id);

            if (! $this->canViewJob($job, $rider)) {
                throw RiderJobException::notYourJob();
            }

            return $this->ok(['job' => $job->toApiDetail($rider)], 'ดึงรายละเอียดงานสำเร็จ');
        });
    }

    /**
     * POST /rider/jobs/{id}/accept — รับงาน (race-safe: คนที่สองได้ 409 JOB_TAKEN)
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        return $this->guard('accept', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            $job = $this->doAccept($request, $this->findJob($id), $rider);

            return $this->ok(['job' => $job->toApiDetail($rider)], 'รับงานสำเร็จ! กรุณาเดินทางไปรับของที่ร้าน');
        }, ['job_id' => $id]);
    }

    /**
     * POST /rider/jobs/{id}/reject — ไม่สนใจงานนี้ (ไม่แสดง/ไม่แจ้งซ้ำ)
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        return $this->guard('reject', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            $this->doReject($this->findJob($id), $rider);

            return $this->ok(['job_id' => $id], 'ซ่อนงานนี้แล้ว');
        }, ['job_id' => $id]);
    }

    /**
     * POST /rider/jobs/{id}/release {reason} — คืนงาน (ก่อนรับของเท่านั้น)
     */
    public function release(Request $request, int $id): JsonResponse
    {
        return $this->guard('release', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            $job = $this->doRelease($request, $this->findJob($id), $rider);

            return $this->ok(['job_id' => (int) $job->id, 'status' => (string) $job->status], 'คืนงานแล้ว ระบบจะหาไรเดอร์คนอื่นให้');
        }, ['job_id' => $id]);
    }

    /**
     * POST /rider/jobs/{id}/status {status: picking_up|picked_up|delivering, photo?}
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        return $this->guard('update_status', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            [$job, $message] = $this->doUpdateStatus($request, $this->findJob($id), $rider);

            return $this->ok(['job' => $job->toApiDetail($rider)], $message);
        }, ['job_id' => $id]);
    }

    /**
     * POST /rider/jobs/{id}/deliver multipart {photo, latitude?, longitude?, cod_collected?, note?}
     *
     * ส่งสำเร็จ = delivered → completed ในคำขอเดียว + รายได้เข้ากระเป๋า
     */
    public function deliver(Request $request, int $id): JsonResponse
    {
        return $this->guard('deliver', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            [$job, $message] = $this->doDeliver($request, $this->findJob($id), $rider);

            return $this->ok([
                'job' => $job->toApiDetail($rider),
                'earnings' => [
                    'rider_earnings' => round((float) $job->rider_earnings, 2),
                    'cod_amount' => round((float) $job->cod_amount, 2),
                    'settled' => $job->earnings_settled_at !== null,
                    'wallet_balance' => $rider->fresh()->walletBalance(),
                ],
            ], $message);
        }, ['job_id' => $id]);
    }

    /**
     * POST /rider/jobs/{id}/fail {reason_code, note?, photo?} — ส่งไม่สำเร็จ
     */
    public function fail(Request $request, int $id): JsonResponse
    {
        return $this->guard('fail', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            [$job, $message] = $this->doFail($request, $this->findJob($id), $rider);

            return $this->ok(['job' => $job->toApiDetail($rider)], $message);
        }, ['job_id' => $id]);
    }

    /**
     * POST /rider/jobs/{id}/gps-lost — แอปตรวจพบว่า GPS หายระหว่างงาน
     */
    public function gpsLost(Request $request, int $id): JsonResponse
    {
        return $this->guard('gps_lost', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            $result = $this->doGpsLost($this->findJob($id), $rider);

            return $this->ok($result, $result['message']);
        }, ['job_id' => $id]);
    }

    /**
     * POST /rider/jobs/{id}/gps-off — ไรเดอร์ยืนยันปิด GPS (หยุดติดตามชั่วคราว)
     */
    public function gpsOff(Request $request, int $id): JsonResponse
    {
        return $this->guard('gps_off', function () use ($request, $id) {
            $rider = $this->riderOrFail($request);
            $result = $this->doGpsOff($this->findJob($id), $rider);

            return $this->ok($result, $result['message']);
        }, ['job_id' => $id]);
    }

    /**
     * GET /rider/earnings?period=today|week|month|all — สรุปรายได้ + งานล่าสุดในช่วงนั้น
     */
    public function earnings(Request $request): JsonResponse
    {
        return $this->guard('earnings', function () use ($request) {
            $rider = $this->riderOrFail($request);

            $data = $this->validateRiderInput($request, [
                'period' => ['nullable', Rule::in(['today', 'week', 'month', 'all'])],
            ]);

            $period = $data['period'] ?? 'today';
            $summary = $this->earnings->summary($rider, $period);

            // ช่วงเวลาเดียวกับ RiderEarningService::summary (ใช้ Carbon ตรงๆ ไม่ส่ง string ISO เข้า SQL)
            $from = match ($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                default => null,
            };

            $recent = RiderJob::query()
                ->where('rider_id', $rider->id)
                ->where('status', 'completed')
                ->when($from, fn ($q) => $q->where('completed_at', '>=', $from))
                ->orderByDesc('completed_at')
                ->limit(20)
                ->get(['id', 'job_number', 'job_type', 'completed_at', 'rider_earnings', 'cod_amount', 'total_fee', 'earnings_settled_at']);

            return $this->ok(array_merge($summary, [
                'recent_jobs' => $recent->map(fn (RiderJob $job) => [
                    'id' => (int) $job->id,
                    'job_number' => (string) $job->job_number,
                    'job_type_text' => $job->job_type_text,
                    'completed_at' => $job->completed_at?->toIso8601String(),
                    'rider_earnings' => round((float) $job->rider_earnings, 2),
                    'total_fee' => round((float) $job->total_fee, 2),
                    'cod_amount' => round((float) $job->cod_amount, 2),
                    'settled' => $job->earnings_settled_at !== null,
                ])->all(),
            ]), 'ดึงข้อมูลรายได้สำเร็จ');
        });
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * ข้อมูลไรเดอร์ + ลิงก์เปิดเอกสารของตัวเอง (signed URL)
     *
     * @return array<string, mixed>
     */
    private function riderPayload(Rider $rider): array
    {
        return $this->accounts->statusPayload($rider, fn (Rider $r, string $t) => $this->signedDocumentUrl($r, $t));
    }

    /**
     * ลิงก์เปิดไฟล์เอกสารของตัวเอง อายุ 30 นาที
     */
    private function signedDocumentUrl(Rider $rider, string $type): ?string
    {
        try {
            return URL::temporarySignedRoute(
                'api.v1.rider.documents.file',
                now()->addMinutes(30),
                ['rider' => $rider->id, 'type' => $type]
            );
        } catch (\Throwable $e) {
            Log::warning('RiderApi: cannot sign document url', ['rider_id' => $rider->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{latitude: float, longitude: float, updated_at: ?string}|null
     */
    private function riderLocation(Rider $rider): ?array
    {
        if ($rider->last_latitude === null || $rider->last_longitude === null) {
            return null;
        }

        return [
            'latitude' => (float) $rider->last_latitude,
            'longitude' => (float) $rider->last_longitude,
            'updated_at' => $rider->last_location_update?->toIso8601String(),
        ];
    }

    /**
     * ไรเดอร์ของผู้ใช้ที่ล็อกอิน (ยังไม่สมัคร → 403 NOT_RIDER)
     */
    private function riderOrFail(Request $request): Rider
    {
        $rider = $this->accounts->findForUser($request->user());

        if (! $rider) {
            throw new HttpResponseException($this->error('NOT_RIDER', 'กรุณาสมัครเป็นไรเดอร์ก่อน', 403));
        }

        return $rider;
    }

    /**
     * @throws RiderJobException JOB_NOT_FOUND
     */
    private function findJob(int $id): RiderJob
    {
        $job = RiderJob::find($id);

        if (! $job) {
            throw RiderJobException::jobNotFound();
        }

        return $job;
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $page
     * @return array{current_page: int, last_page: int, per_page: int, total: int, has_more: bool}
     */
    private function pagination($page): array
    {
        return [
            'current_page' => (int) $page->currentPage(),
            'last_page' => (int) $page->lastPage(),
            'per_page' => (int) $page->perPage(),
            'total' => (int) $page->total(),
            'has_more' => $page->hasMorePages(),
        ];
    }

    /**
     * ตอบสำเร็จ
     */
    private function ok(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * ตอบผิดพลาด
     */
    private function error(string $code, string $message, int $status, mixed $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * ครอบทุก action: แปลง error ทุกชนิดเป็น JSON มาตรฐาน (ไม่หลุดข้อความ exception ดิบ)
     *
     * @param  callable(): JsonResponse  $callback
     * @param  array<string, mixed>  $context
     */
    private function guard(string $action, callable $callback, array $context = []): JsonResponse
    {
        try {
            return $callback();
        } catch (RiderJobException $e) {
            return response()->json($e->toArray(), $e->httpStatus);
        } catch (ValidationException $e) {
            // รูปแบบเดียวกับ RiderRegistrationRequest: errors อยู่ชั้นบนสุด (แอปอ่านที่เดียว)
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $e->validator->errors()->first() ?: 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors(),
                'data' => null,
            ], 422);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('RiderApi: '.$action.' failed', array_merge($context, [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile().':'.$e->getLine(),
            ]));

            return $this->error('SERVER_ERROR', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500);
        }
    }
}
