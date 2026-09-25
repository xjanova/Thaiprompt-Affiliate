<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\RiderJobException;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Services\DeliveryFeeCalculator;
use App\Services\RiderDispatchService;
use App\Services\RiderGpsTrackingService;
use App\Services\RiderJobService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ขั้นตอนงานไรเดอร์ที่ใช้ร่วมกันระหว่าง API แอป (RiderApiController) และหน้าเว็บ (User\RiderController)
 *
 * ทุกการเปลี่ยนสถานะเรียก RiderJobService เท่านั้น — ที่นี่ทำแค่:
 *   - ตรวจข้อมูลเข้า (ข้อความภาษาไทย)
 *   - ตรวจว่างานเป็นของไรเดอร์คนนี้ (กัน IDOR) ก่อนแตะอะไร
 *   - ดึงรูปจากชื่อฟิลด์ทั้งแบบใหม่ (photo) และแบบเก่า (proof_image / proof_photo)
 *
 * validation ไม่ผ่าน → ValidationException (API แปลงเป็น JSON มาตรฐาน / เว็บ redirect กลับ)
 * ผิดเงื่อนไขทางธุรกิจ → RiderJobException (มีรหัส + ข้อความไทย + HTTP status)
 */
trait RiderJobActions
{
    /**
     * กฎรูปถ่ายยืนยัน (สูงสุด 10MB)
     *
     * @return array<int, string>
     */
    protected function photoRules(bool $required): array
    {
        return [$required ? 'required' : 'nullable', 'file', 'mimes:jpeg,jpg,png,webp,heic,heif', 'max:10240'];
    }

    /**
     * ข้อความ validation ภาษาไทย (โปรเจกต์ไม่มี lang/th/validation.php)
     *
     * @return array<string, string>
     */
    protected function riderValidationMessages(): array
    {
        return [
            'required' => 'กรุณาระบุ:attribute',
            'required_if' => 'กรุณาระบุ:attribute',
            'numeric' => ':attribute ต้องเป็นตัวเลข',
            'integer' => ':attribute ต้องเป็นจำนวนเต็ม',
            'between' => ':attribute ต้องอยู่ระหว่าง :min ถึง :max',
            'min' => ':attribute ต้องไม่น้อยกว่า :min',
            'max' => ':attribute ต้องไม่เกิน :max',
            'in' => ':attribute ไม่ถูกต้อง',
            'boolean' => ':attribute ไม่ถูกต้อง',
            'string' => ':attribute ไม่ถูกต้อง',
            'array' => ':attribute ไม่ถูกต้อง',
            'file' => ':attribute ต้องเป็นไฟล์',
            'image' => ':attribute ต้องเป็นไฟล์รูปภาพ',
            'mimes' => ':attribute ต้องเป็นรูป jpg, png, webp หรือ heic',
            'uploaded' => 'อัปโหลด:attributeไม่สำเร็จ กรุณาลองใหม่',
            'photo.max' => 'รูปยืนยันต้องมีขนาดไม่เกิน 10MB',
            'document.max' => 'ไฟล์เอกสารต้องมีขนาดไม่เกิน 10MB',
            'image.max' => 'ไฟล์เอกสารต้องมีขนาดไม่เกิน 10MB',
            'file.max' => 'ไฟล์เอกสารต้องมีขนาดไม่เกิน 10MB',
        ];
    }

    /**
     * ชื่อฟิลด์ภาษาไทย
     *
     * @return array<string, string>
     */
    protected function riderValidationAttributes(): array
    {
        return [
            'latitude' => 'ละติจูด',
            'longitude' => 'ลองจิจูด',
            'accuracy' => 'ความแม่นยำ GPS',
            'speed' => 'ความเร็ว',
            'heading' => 'ทิศทาง',
            'altitude' => 'ความสูง',
            'battery_level' => 'ระดับแบตเตอรี่',
            'is_charging' => 'สถานะชาร์จ',
            'activity_type' => 'ประเภทการเคลื่อนที่',
            'job_id' => 'รหัสงาน',
            'availability' => 'สถานะรับงาน',
            'status' => 'สถานะงาน',
            'photo' => 'รูปยืนยัน',
            'reason' => 'เหตุผล',
            'reason_code' => 'เหตุผลที่ส่งไม่สำเร็จ',
            'note' => 'หมายเหตุ',
            'cod_collected' => 'การยืนยันเก็บเงินปลายทาง',
            'type' => 'ประเภทเอกสาร',
            'document_type' => 'ประเภทเอกสาร',
            'document' => 'ไฟล์เอกสาร',
            'image' => 'ไฟล์เอกสาร',
            'file' => 'ไฟล์เอกสาร',
            'period' => 'ช่วงเวลา',
            'per_page' => 'จำนวนต่อหน้า',
            'gps' => 'สิทธิ์ตำแหน่ง',
            'camera' => 'สิทธิ์กล้อง',
            'microphone' => 'สิทธิ์ไมโครโฟน',
            'notification' => 'สิทธิ์แจ้งเตือน',
            'location_consent' => 'ความยินยอมแชร์ตำแหน่ง',
            'phone' => 'เบอร์โทรศัพท์',
            'vehicle_type' => 'ประเภทยานพาหนะ',
            'vehicle_plate' => 'ทะเบียนรถ',
            'vehicle_brand' => 'ยี่ห้อรถ',
            'vehicle_color' => 'สีรถ',
            'preferred_radius_km' => 'รัศมีรับงาน',
            'preferred_min_fee' => 'ค่าส่งขั้นต่ำที่รับ',
            'preferred_job_types' => 'ประเภทงานที่รับ',
        ];
    }

    /**
     * ตรวจข้อมูลด้วยข้อความไทย (ไม่ผ่าน → ValidationException)
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, mixed>  $extra  ค่าที่แทนที่/เพิ่มจากคำขอ (เช่นรูปที่มาจากชื่อฟิลด์เก่า)
     * @return array<string, mixed>
     */
    protected function validateRiderInput(Request $request, array $rules, array $messages = [], array $extra = []): array
    {
        return Validator::make(
            array_merge($request->all(), $extra),
            $rules,
            array_merge($this->riderValidationMessages(), $messages),
            $this->riderValidationAttributes()
        )->validate();
    }

    /**
     * รูปที่แนบมา (รองรับชื่อฟิลด์เก่าของแอป/หน้าเว็บ: proof_image, proof_photo)
     */
    protected function uploadedPhoto(Request $request): ?UploadedFile
    {
        foreach (['photo', 'proof_image', 'proof_photo'] as $key) {
            $file = $request->file($key);
            if ($file instanceof UploadedFile) {
                return $file;
            }
        }

        return null;
    }

    /**
     * งานนี้ต้องเป็นของไรเดอร์คนนี้เท่านั้น (กัน IDOR)
     *
     * @throws RiderJobException NOT_YOUR_JOB
     */
    protected function assertOwnJob(RiderJob $job, Rider $rider): void
    {
        if ($job->rider_id === null || (int) $job->rider_id !== (int) $rider->id) {
            throw RiderJobException::notYourJob();
        }
    }

    /**
     * ไรเดอร์คนนี้กดปฏิเสธงานนี้ไปแล้วหรือไม่ (ไม่โชว์ในรายการงานอีก)
     */
    protected function rejectedByRider(RiderJob $job, Rider $rider): bool
    {
        foreach ($job->dispatch_attempts ?? [] as $attempt) {
            if (($attempt['status'] ?? null) === 'rejected' && (int) ($attempt['rider_id'] ?? 0) === (int) $rider->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * ไรเดอร์คนนี้ดูรายละเอียดงานนี้ได้หรือไม่
     *
     * - งานของตัวเอง (ทุกสถานะ)
     * - งานที่ยังเปิดรับ และเสนอให้ตัวเองได้ (ไม่ใช่ออเดอร์ของตัวเอง, cascade ต้องเสนอให้ตัวเอง, ไม่เคยคืนงานนี้)
     */
    protected function canViewJob(RiderJob $job, Rider $rider): bool
    {
        if ($job->rider_id !== null && (int) $job->rider_id === (int) $rider->id) {
            return true;
        }

        if (! $job->isOpen() || $rider->status !== 'approved') {
            return false;
        }

        if (in_array((int) $rider->user_id, $job->partyUserIds(), true)
            || in_array((int) $rider->id, $job->releasedRiderIds(), true)) {
            return false;
        }

        if ($job->dispatch_type === 'cascade'
            && $job->current_offer_rider_id !== null
            && (int) $job->current_offer_rider_id !== (int) $rider->id) {
            return false;
        }

        return true;
    }

    /**
     * บันทึกพิกัดที่แนบมากับคำขอ (ถ้ามีและถูกต้อง) — ใช้ตอนรับงาน/เปิดรับงาน ให้พิกัดสดเสมอ
     */
    protected function recordLocationFromRequest(Request $request, Rider $rider): void
    {
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');

        if (! DeliveryFeeCalculator::isValidCoordinate($lat, $lng)) {
            return;
        }

        (new RiderGpsTrackingService)->recordRiderLocation($rider, [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'accuracy' => $request->input('accuracy'),
            'speed' => $request->input('speed'),
            'heading' => $request->input('heading'),
        ]);

        $rider->refresh();
    }

    // =====================================================
    // ขั้นตอนงาน
    // =====================================================

    /**
     * รับตำแหน่งจากเครื่องไรเดอร์ (ค่าติดลบของ heading/speed จาก iOS = ไม่ทราบ → เก็บเป็น null)
     *
     * @return array{has_active_job: bool, job_id: ?int, is_tracking: bool, gps_resumed: bool}
     *
     * @throws RiderJobException INVALID_LOCATION|NOT_ELIGIBLE
     */
    protected function doRecordLocation(Request $request, Rider $rider): array
    {
        $data = $this->validateRiderInput($request, [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
            'speed' => ['nullable', 'numeric'],
            'heading' => ['nullable', 'numeric'],
            'altitude' => ['nullable', 'numeric'],
            'battery_level' => ['nullable', 'numeric'],
            'is_charging' => ['nullable', 'boolean'],
            'activity_type' => ['nullable', 'string', 'max:20'],
            'job_id' => ['nullable', 'integer'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'os_version' => ['nullable', 'string', 'max:255'],
        ]);

        if (! DeliveryFeeCalculator::isValidCoordinate($data['latitude'], $data['longitude'])) {
            throw RiderJobException::invalidLocation('ปัจจุบัน');
        }

        // ไรเดอร์ที่ถูกระงับแต่ยังถือของอยู่ ต้องส่งตำแหน่งต่อได้ (แอดมินต้องตามของ)
        if ($rider->status !== 'approved' && ! $rider->hasActiveJob()) {
            $reason = $rider->onlineBlockReason() ?? ['code' => 'NOT_APPROVED', 'message' => 'บัญชีไรเดอร์ยังไม่พร้อมใช้งาน'];
            throw RiderJobException::notEligible($reason['message'], $reason['code']);
        }

        // battery_level จาก iOS อาจเป็น -1 (ไม่ทราบ) หรือทศนิยม 0-1 → แปลงเป็น % หรือทิ้ง
        if (isset($data['battery_level']) && is_numeric($data['battery_level'])) {
            $battery = (float) $data['battery_level'];
            $data['battery_level'] = $battery < 0 ? null : (int) round($battery <= 1 ? $battery * 100 : $battery);
        }

        return (new RiderGpsTrackingService)->recordRiderLocation($rider, $data);
    }

    /**
     * ไรเดอร์กดรับงาน
     */
    protected function doAccept(Request $request, RiderJob $job, Rider $rider): RiderJob
    {
        $this->recordLocationFromRequest($request, $rider);

        return app(RiderJobService::class)->accept($job, $rider);
    }

    /**
     * ไรเดอร์ไม่สนใจงานนี้ (ไม่แจ้งซ้ำ / เลื่อนไปเสนอคนถัดไปในโหมด cascade)
     */
    protected function doReject(RiderJob $job, Rider $rider): void
    {
        if (! $job->isOpen()) {
            return; // มีคนรับไปแล้ว/ยกเลิกแล้ว → ไม่ต้องทำอะไร
        }

        app(RiderDispatchService::class)->handleRiderReject($job, $rider);
    }

    /**
     * คืนงาน (ก่อนรับของเท่านั้น)
     */
    protected function doRelease(Request $request, RiderJob $job, Rider $rider): RiderJob
    {
        $this->assertOwnJob($job, $rider);

        $data = $this->validateRiderInput($request, [
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return app(RiderJobService::class)->release($job, $rider, (string) ($data['reason'] ?? ''));
    }

    /**
     * อัปเดตสถานะระหว่างทาง: picking_up | picked_up | delivering
     *
     * รองรับแอปรุ่นเก่าที่ส่ง status=delivered/completed มาพร้อมรูป → ส่งต่อให้ขั้นตอน "ส่งสำเร็จ"
     *
     * @return array{0: RiderJob, 1: string} [งานล่าสุด, ข้อความไทย]
     */
    protected function doUpdateStatus(Request $request, RiderJob $job, Rider $rider): array
    {
        $this->assertOwnJob($job, $rider);
        $photo = $this->uploadedPhoto($request);

        $data = $this->validateRiderInput($request, [
            'status' => ['required', Rule::in(['picking_up', 'picked_up', 'delivering', 'delivered', 'completed'])],
            'photo' => $this->photoRules(false),
        ], [], ['photo' => $photo]);

        $service = app(RiderJobService::class);

        return match ($data['status']) {
            'picking_up' => [$service->markPickingUp($job, $rider), 'กำลังเดินทางไปรับของ'],
            'picked_up' => [$service->markPickedUp($job, $rider, $photo), 'รับของเรียบร้อย'],
            'delivering' => [$service->markDelivering($job, $rider), 'เริ่มนำส่งแล้ว'],
            default => $this->doDeliver($request, $job, $rider),
        };
    }

    /**
     * ส่งของถึงมือลูกค้า (ต้องมีรูป, งานเก็บเงินปลายทางต้องยืนยันว่าเก็บเงินแล้ว)
     *
     * @return array{0: RiderJob, 1: string}
     */
    protected function doDeliver(Request $request, RiderJob $job, Rider $rider): array
    {
        $this->assertOwnJob($job, $rider);
        $photo = $this->uploadedPhoto($request);

        if (! $photo) {
            throw RiderJobException::photoRequired();
        }

        $data = $this->validateRiderInput($request, [
            'photo' => $this->photoRules(true),
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'cod_collected' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['photo' => $photo]);

        $cod = round((float) $job->cod_amount, 2);
        if ($cod > 0 && ! $request->has('cod_collected')) {
            throw RiderJobException::codConfirmRequired($cod);
        }

        $lat = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $lng = isset($data['longitude']) ? (float) $data['longitude'] : null;

        $done = app(RiderJobService::class)->deliver(
            $job,
            $rider,
            $photo,
            $lat,
            $lng,
            filter_var($data['cod_collected'] ?? false, FILTER_VALIDATE_BOOLEAN)
        );

        $note = trim((string) ($data['note'] ?? ''));
        if ($note !== '') {
            RiderJob::whereKey($done->id)->whereNull('delivery_note')->update(['delivery_note' => mb_substr($note, 0, 500)]);
        }

        $message = $cod > 0
            ? 'ส่งของสำเร็จ! ระบบหักยอดนำส่งเงินปลายทางและบันทึกรายได้ให้แล้ว'
            : 'ส่งของสำเร็จ! รายได้ค่าส่งเข้ากระเป๋าเงินของคุณแล้ว';

        return [$done->fresh(), $message];
    }

    /**
     * ส่งไม่สำเร็จ (หลังรับของแล้ว)
     *
     * @return array{0: RiderJob, 1: string}
     */
    protected function doFail(Request $request, RiderJob $job, Rider $rider): array
    {
        $this->assertOwnJob($job, $rider);
        $photo = $this->uploadedPhoto($request);

        $data = $this->validateRiderInput($request, [
            'reason_code' => ['required', Rule::in(RiderJob::RIDER_FAILURE_REASONS)],
            'note' => ['nullable', 'required_if:reason_code,other', 'string', 'max:1000'],
            'photo' => $this->photoRules(false),
        ], [
            'note.required_if' => 'กรุณาระบุรายละเอียดที่ส่งไม่สำเร็จ',
        ], ['photo' => $photo]);

        $failed = app(RiderJobService::class)->fail(
            $job,
            $rider,
            $data['reason_code'],
            $data['note'] ?? null,
            $photo
        );

        return [$failed, 'บันทึกว่าส่งไม่สำเร็จแล้ว ทีมงานจะติดต่อเพื่อจัดการคืนสินค้า'];
    }

    /**
     * แจ้งว่า GPS ในเครื่องหาย (หน้าเว็บ/แอปตรวจเจอเอง)
     *
     * @return array<string, mixed>
     */
    protected function doGpsLost(RiderJob $job, Rider $rider): array
    {
        $this->assertOwnJob($job, $rider);

        if (! $job->isTrackable()) {
            throw RiderJobException::invalidTransition((string) $job->status, (string) $job->status);
        }

        $result = (new RiderGpsTrackingService)->handleGpsLost($job);

        return [
            'warning_count' => (int) $result['warning_count'],
            'max_warnings' => (int) $result['max_warnings'],
            'flow_stopped' => (bool) $result['flow_stopped'],
            'message' => (string) $result['message'],
        ];
    }

    /**
     * ไรเดอร์ยืนยันปิด GPS เอง (งานหยุดติดตามชั่วคราว — แอดมินเห็นในรายการงาน)
     *
     * @return array<string, mixed>
     */
    protected function doGpsOff(RiderJob $job, Rider $rider): array
    {
        $this->assertOwnJob($job, $rider);

        if (! $job->isTrackable()) {
            throw RiderJobException::invalidTransition((string) $job->status, (string) $job->status);
        }

        $result = (new RiderGpsTrackingService)->confirmGpsOff($job);

        return ['message' => (string) $result['message'], 'gps_active' => false];
    }
}
