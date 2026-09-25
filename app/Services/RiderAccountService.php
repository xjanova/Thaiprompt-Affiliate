<?php

namespace App\Services;

use App\Exceptions\RiderJobException;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * บัญชีไรเดอร์ (ใช้ร่วมกันระหว่าง API แอป, หน้าเว็บ /user/rider และหลังบ้านแอดมิน)
 *
 * - สมัคร / สมัครใหม่หลังถูกปฏิเสธ / แก้ใบสมัครระหว่างรอตรวจ
 * - เอกสาร: เก็บบน private disk ('local' = storage/app) เท่านั้น — ห้ามอยู่บน public disk (PDPA)
 *   เปิดดูได้ผ่าน route ที่ตรวจสิทธิ์แล้วเท่านั้น (แอดมิน / เจ้าของ / signed URL อายุสั้น)
 * - เปิด/ปิดรับงาน, สิทธิ์ในเครื่อง, ความยินยอมแชร์ตำแหน่ง
 * - ข้อมูลสถานะไรเดอร์ชุดเดียวที่ทุกช่องทางใช้ (statusPayload)
 *
 * ❗ การเปลี่ยนสถานะงานทั้งหมดอยู่ที่ RiderJobService — คลาสนี้ไม่แตะงาน
 */
class RiderAccountService
{
    /**
     * ดิสก์ที่เก็บเอกสารไรเดอร์ (private — ไม่มี URL สาธารณะ)
     */
    public const DOCUMENT_DISK = 'local';

    /**
     * ประเภทเอกสาร → คอลัมน์ในตาราง riders + ชื่อไทย
     *
     * @var array<string, array{column: string, label: string}>
     */
    public const DOCUMENT_TYPES = [
        'id_card' => ['column' => 'id_card_image', 'label' => 'บัตรประชาชน'],
        'driver_license' => ['column' => 'driver_license_image', 'label' => 'ใบอนุญาตขับขี่'],
        'vehicle_registration' => ['column' => 'vehicle_registration_image', 'label' => 'สำเนาทะเบียนรถ'],
        'profile' => ['column' => 'profile_image', 'label' => 'รูปถ่ายหน้าตรง'],
    ];

    /**
     * เอกสารที่เปลี่ยนหลังอนุมัติแล้วต้องให้แอดมินตรวจซ้ำ
     */
    private const REVIEW_SENSITIVE_TYPES = ['id_card', 'driver_license', 'vehicle_registration'];

    /**
     * ประเภทยานพาหนะ (value => ชื่อไทย)
     *
     * @var array<string, string>
     */
    public const VEHICLE_TYPES = [
        'motorcycle' => 'มอเตอร์ไซค์',
        'car' => 'รถยนต์',
        'bicycle' => 'จักรยาน',
        'walk' => 'เดินเท้า',
    ];

    /**
     * ประเภทงานที่ไรเดอร์เลือกรับได้ (value => ชื่อไทย) — ว่าง = รับทุกงาน
     *
     * @var array<string, string>
     */
    public const JOB_TYPE_OPTIONS = [
        'delivery' => 'ส่งของทุกประเภท',
        'fresh_market' => 'ส่งของตลาดสด',
        'shop_delivery' => 'ส่งสินค้าร้านค้า',
        'food' => 'ส่งอาหาร',
        'document' => 'ส่งเอกสาร',
    ];

    public function __construct(private readonly RiderNotificationService $notifier) {}

    // =====================================================
    // ค้นหา
    // =====================================================

    /**
     * ไรเดอร์ของผู้ใช้ (ไม่รวมที่ถูกลบ)
     */
    public function findForUser(User|int|null $user): ?Rider
    {
        $userId = $user instanceof User ? $user->id : $user;

        if (! $userId) {
            return null;
        }

        return Rider::where('user_id', (int) $userId)->first();
    }

    // =====================================================
    // สมัคร
    // =====================================================

    /**
     * สมัครไรเดอร์ / ส่งใบสมัครใหม่หลังถูกปฏิเสธ / แก้ใบสมัครที่รอตรวจ
     *
     * กดซ้ำ (double tap) ปลอดภัย: ชน unique user_id → โหลดแถวเดิมมาอัปเดตแทน ไม่ 500
     *
     * @param  array<string, mixed>  $data  ข้อมูลที่ผ่าน RiderRegistrationRequest::riderData() แล้ว
     * @return array{rider: Rider, outcome: string} outcome = created|reapplied|updated|exists
     */
    public function register(User $user, array $data): array
    {
        $existing = Rider::withTrashed()->where('user_id', $user->id)->first();

        if (! $existing) {
            try {
                $rider = Rider::create(array_merge($data, [
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'availability' => 'offline',
                ]));

                Log::info('Rider: registered', ['rider_id' => $rider->id, 'user_id' => $user->id]);

                $this->afterApplicationSubmitted($rider, 'created');

                return ['rider' => $rider->fresh(), 'outcome' => 'created'];
            } catch (UniqueConstraintViolationException) {
                // กดส่งซ้ำพร้อมกัน → อีกคำขอสร้างไปแล้ว ใช้แถวนั้นต่อ
                $existing = Rider::withTrashed()->where('user_id', $user->id)->first();

                if (! $existing) {
                    throw new RiderJobException('REGISTER_FAILED', 'สมัครไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 409);
                }
            }
        }

        [$rider, $outcome] = DB::transaction(function () use ($existing, $data) {
            /** @var Rider $locked */
            $locked = Rider::withTrashed()->whereKey($existing->id)->lockForUpdate()->firstOrFail();

            if ($locked->trashed()) {
                $locked->restore();
                $locked->forceFill(array_merge($data, $this->resetApplicationFields()))->save();

                return [$locked, 'reapplied'];
            }

            return match ($locked->status) {
                'rejected', 'inactive' => (function () use ($locked, $data) {
                    $locked->forceFill(array_merge($data, $this->resetApplicationFields()))->save();

                    return [$locked, 'reapplied'];
                })(),
                'pending' => (function () use ($locked, $data) {
                    $locked->forceFill($data)->save();

                    return [$locked, 'updated'];
                })(),
                default => [$locked, 'exists'],
            };
        });

        if ($outcome === 'reapplied') {
            Log::info('Rider: re-applied', ['rider_id' => $rider->id]);
            $this->afterApplicationSubmitted($rider, 'reapplied');
        }

        return ['rider' => $rider->fresh(), 'outcome' => $outcome];
    }

    /**
     * ค่าที่ต้องล้างเมื่อส่งใบสมัครใหม่
     *
     * @return array<string, mixed>
     */
    private function resetApplicationFields(): array
    {
        return [
            'status' => 'pending',
            'availability' => 'offline',
            'rejection_reason' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'approved_at' => null,
            'approved_by' => null,
        ];
    }

    /**
     * แจ้งผู้สมัคร + แอดมินหลังส่งใบสมัคร
     */
    private function afterApplicationSubmitted(Rider $rider, string $outcome): void
    {
        $missing = $this->missingDocuments($rider);

        $this->notifier->notifyUser(
            (int) $rider->user_id,
            'rider_account',
            'ส่งใบสมัครไรเดอร์แล้ว',
            $missing === []
                ? 'ทีมงานกำลังตรวจสอบใบสมัครของคุณ จะแจ้งผลให้ทราบโดยเร็ว'
                : 'กรุณาอัปโหลดเอกสารให้ครบ ('.$this->documentLabels($missing).') เพื่อให้ทีมงานตรวจสอบได้',
            ['type' => 'rider_account', 'event' => 'application_'.$outcome, 'screen' => 'rider'],
        );

        $this->notifier->notifyAdmins(
            $outcome === 'reapplied' ? 'ไรเดอร์ส่งใบสมัครใหม่' : 'มีใบสมัครไรเดอร์ใหม่',
            "{$rider->full_name} ({$rider->vehicle_type_text}) ".($missing === [] ? 'เอกสารครบ รอตรวจ' : 'ยังขาดเอกสาร '.count($missing).' รายการ'),
            ['rider_id' => (int) $rider->id, 'event' => 'rider_application'],
            $this->adminRiderUrl($rider),
        );
    }

    // =====================================================
    // เอกสาร
    // =====================================================

    /**
     * เอกสารที่ต้องมีก่อนอนุมัติ: บัตรประชาชน + รูปหน้าตรงเสมอ, ใบขับขี่ + ทะเบียนรถ เมื่อใช้รถ
     *
     * @return array<int, string>
     */
    public function requiredDocumentTypes(Rider|string|null $riderOrVehicle): array
    {
        $vehicle = $riderOrVehicle instanceof Rider ? $riderOrVehicle->vehicle_type : $riderOrVehicle;

        $required = ['id_card', 'profile'];

        if (in_array($vehicle, ['motorcycle', 'car'], true)) {
            $required[] = 'driver_license';
            $required[] = 'vehicle_registration';
        }

        return $required;
    }

    /**
     * อัปโหลดแล้วหรือยัง แยกตามประเภท
     *
     * @return array<string, bool>
     */
    public function documentFlags(Rider $rider): array
    {
        $flags = [];
        foreach (self::DOCUMENT_TYPES as $type => $meta) {
            $flags[$type] = ! empty($rider->getAttribute($meta['column']));
        }

        return $flags;
    }

    /**
     * เอกสารที่ยังขาด (เฉพาะที่บังคับ)
     *
     * @return array<int, string>
     */
    public function missingDocuments(Rider $rider): array
    {
        $flags = $this->documentFlags($rider);

        return array_values(array_filter(
            $this->requiredDocumentTypes($rider),
            fn (string $type) => ! ($flags[$type] ?? false)
        ));
    }

    public function documentsComplete(Rider $rider): bool
    {
        return $this->missingDocuments($rider) === [];
    }

    /**
     * รายการเอกสารสำหรับแสดงผล
     *
     * @param  callable(Rider, string): ?string|null  $urlFor  สร้างลิงก์เปิดไฟล์ (ตามช่องทาง) — null = ไม่ใส่ลิงก์
     * @return array<int, array{type: string, label: string, uploaded: bool, required: bool, url: ?string}>
     */
    public function documentList(Rider $rider, ?callable $urlFor = null): array
    {
        $required = $this->requiredDocumentTypes($rider);
        $flags = $this->documentFlags($rider);
        $list = [];

        foreach (self::DOCUMENT_TYPES as $type => $meta) {
            $list[] = [
                'type' => $type,
                'label' => $meta['label'],
                'uploaded' => $flags[$type],
                'required' => in_array($type, $required, true),
                'url' => ($flags[$type] && $urlFor) ? $urlFor($rider, $type) : null,
            ];
        }

        return $list;
    }

    /**
     * ชื่อไทยของเอกสารหลายรายการ คั่นด้วยจุลภาค
     *
     * @param  array<int, string>  $types
     */
    public function documentLabels(array $types): string
    {
        return implode(', ', array_map(fn ($t) => self::DOCUMENT_TYPES[$t]['label'] ?? $t, $types));
    }

    /**
     * บันทึกเอกสาร 1 รายการ (ทับไฟล์เดิม) บน private disk
     *
     * - ไรเดอร์ที่อนุมัติแล้วเปลี่ยนเอกสารสำคัญ → ตั้ง documents_changed_at + แจ้งแอดมินตรวจซ้ำ
     * - ใบสมัครที่รอตรวจ เอกสารครบเป็นครั้งแรก → แจ้งแอดมินว่าพร้อมตรวจ
     *
     * @throws RiderJobException INVALID_DOCUMENT_TYPE|UPLOAD_FAILED
     */
    public function storeDocument(Rider $rider, string $type, UploadedFile $file): Rider
    {
        $meta = self::DOCUMENT_TYPES[$type] ?? null;
        if (! $meta) {
            throw new RiderJobException('INVALID_DOCUMENT_TYPE', 'ประเภทเอกสารไม่ถูกต้อง', 422);
        }

        if (! $file->isValid() || ! str_starts_with((string) $file->getMimeType(), 'image/')) {
            throw new RiderJobException('UPLOAD_FAILED', 'ไฟล์รูปไม่ถูกต้อง กรุณาเลือกรูปใหม่', 422);
        }

        $wasComplete = $this->documentsComplete($rider);
        $column = $meta['column'];
        $oldPath = $rider->getAttribute($column);

        $path = $file->store("riders/{$rider->id}/{$type}", self::DOCUMENT_DISK);
        if (! $path) {
            Log::error('Rider: cannot store document', ['rider_id' => $rider->id, 'type' => $type]);
            throw new RiderJobException('UPLOAD_FAILED', 'บันทึกไฟล์ไม่สำเร็จ กรุณาลองใหม่', 500);
        }

        $needsReview = $rider->status === 'approved' && in_array($type, self::REVIEW_SENSITIVE_TYPES, true);

        try {
            $rider->forceFill(array_filter([
                $column => $path,
                'documents_changed_at' => $needsReview ? now() : null,
                // รอแอดมินตรวจเอกสารใหม่ → ปิดรับงาน (ถ้ากำลังวิ่งงานอยู่ ให้จบงานเดิมก่อน แล้วระบบจะปิดเองหลังจบงาน)
                'availability' => $needsReview && $rider->availability === 'online' ? 'offline' : null,
            ], fn ($v) => $v !== null))->save();
        } catch (\Throwable $e) {
            $this->deleteDocumentFile($path);
            throw $e;
        }

        if ($oldPath && $oldPath !== $path) {
            $this->deleteDocumentFile($oldPath);
        }

        Log::info('Rider: document uploaded', ['rider_id' => $rider->id, 'type' => $type, 'needs_review' => $needsReview]);

        if ($needsReview) {
            $this->notifier->notifyAdmins(
                'ไรเดอร์เปลี่ยนเอกสาร ต้องตรวจซ้ำ',
                "{$rider->full_name} อัปโหลด{$meta['label']}ใหม่หลังได้รับอนุมัติแล้ว",
                ['rider_id' => (int) $rider->id, 'event' => 'rider_document_changed', 'document' => $type],
                $this->adminRiderUrl($rider),
            );
        } elseif ($rider->status === 'pending' && ! $wasComplete && $this->documentsComplete($rider)) {
            $this->notifier->notifyAdmins(
                'ไรเดอร์ส่งเอกสารครบแล้ว',
                "{$rider->full_name} อัปโหลดเอกสารครบ พร้อมให้ตรวจอนุมัติ",
                ['rider_id' => (int) $rider->id, 'event' => 'rider_documents_complete'],
                $this->adminRiderUrl($rider),
            );
        }

        return $rider->fresh();
    }

    /**
     * ส่งไฟล์เอกสารออกไป (ผู้เรียกต้องตรวจสิทธิ์ก่อนเสมอ)
     *
     * อ่านจาก private disk ก่อน แล้วค่อยลอง public disk (ไฟล์เก่าจากหน้าเว็บเดิม)
     * ตอบแบบห้าม cache + ห้ามเดาชนิดไฟล์
     */
    public function documentResponse(Rider $rider, string $type): StreamedResponse
    {
        $meta = self::DOCUMENT_TYPES[$type] ?? null;
        $path = $meta ? $rider->getAttribute($meta['column']) : null;

        if (! $path || str_contains($path, '..')) {
            abort(404, 'ไม่พบเอกสาร');
        }

        foreach ([self::DOCUMENT_DISK, 'public'] as $disk) {
            $storage = Storage::disk($disk);

            if ($storage->exists($path)) {
                $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';

                return $storage->response($path, "rider-{$rider->id}-{$type}.{$extension}", [
                    'Cache-Control' => 'private, no-store, max-age=0',
                    'X-Content-Type-Options' => 'nosniff',
                    'Content-Security-Policy' => "default-src 'none'",
                ], 'inline');
            }
        }

        abort(404, 'ไม่พบไฟล์เอกสาร');
    }

    /**
     * ลบไฟล์เอกสาร (ทั้ง private และ public เผื่อไฟล์เก่า) — ลบไม่ได้ไม่ถือว่าพัง
     */
    private function deleteDocumentFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        foreach ([self::DOCUMENT_DISK, 'public'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable) {
                // ไฟล์กำพร้าไม่กระทบข้อมูล
            }
        }
    }

    // =====================================================
    // โปรไฟล์ / ยานพาหนะ / ความชอบงาน
    // =====================================================

    /**
     * กฎตรวจข้อมูลโปรไฟล์ไรเดอร์ (ใช้ทั้งแอป PUT /rider/profile และเว็บ user.rider.settings.update)
     *
     * @return array<string, mixed>
     */
    public static function profileRules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^0\d{8,9}$/'],
            'vehicle_type' => ['required', \Illuminate\Validation\Rule::in(array_keys(self::VEHICLE_TYPES))],
            'vehicle_plate' => ['nullable', 'required_if:vehicle_type,motorcycle,car', 'string', 'max:20'],
            'vehicle_brand' => ['nullable', 'string', 'max:100'],
            'vehicle_color' => ['nullable', 'string', 'max:50'],
            'preferred_radius_km' => ['nullable', 'numeric', 'between:1,50'],
            'preferred_min_fee' => ['nullable', 'numeric', 'between:0,1000'],
            'preferred_job_types' => ['nullable', 'array'],
            'preferred_job_types.*' => [\Illuminate\Validation\Rule::in(array_keys(self::JOB_TYPE_OPTIONS))],
        ];
    }

    /**
     * ข้อความภาษาไทยเฉพาะของฟอร์มโปรไฟล์
     *
     * @return array<string, string>
     */
    public static function profileMessages(): array
    {
        return [
            'phone.regex' => 'เบอร์โทรศัพท์ไม่ถูกต้อง (ตัวเลข 9-10 หลัก ขึ้นต้นด้วย 0)',
            'vehicle_plate.required_if' => 'กรุณากรอกทะเบียนรถ',
            'preferred_job_types.*.in' => 'ประเภทงานที่เลือกไม่ถูกต้อง',
        ];
    }

    /**
     * บันทึกโปรไฟล์ + ความชอบงาน (ข้อมูลต้องผ่าน profileRules() แล้ว)
     *
     * เปลี่ยนยานพาหนะ:
     *   - ระหว่างมีงานไม่ได้ (ยานพาหนะผูกกับงานที่รับไปแล้ว)
     *   - ไรเดอร์ที่อนุมัติแล้ว → ตั้ง documents_changed_at + แจ้งแอดมินตรวจซ้ำ
     *
     * @param  array<string, mixed>  $data
     * @return array{rider: Rider, vehicle_changed: bool, missing: array<int, string>}
     *
     * @throws RiderJobException HAS_ACTIVE_JOB
     */
    public function updateProfile(Rider $rider, array $data): array
    {
        $vehicleChanged = ($data['vehicle_type'] ?? $rider->vehicle_type) !== $rider->vehicle_type;

        if ($vehicleChanged && ($active = $rider->activeJob())) {
            throw RiderJobException::hasActiveJob((int) $active->id);
        }

        $clean = fn ($value) => is_string($value) && trim($value) !== '' ? trim($value) : null;

        $rider->forceFill([
            'phone' => preg_replace('/[\s\-().]+/', '', (string) ($data['phone'] ?? $rider->phone)),
            'vehicle_type' => $data['vehicle_type'] ?? $rider->vehicle_type,
            'vehicle_plate' => $clean($data['vehicle_plate'] ?? null),
            'vehicle_brand' => $clean($data['vehicle_brand'] ?? null),
            'vehicle_color' => $clean($data['vehicle_color'] ?? null),
            'preferred_job_types' => array_values(array_unique(array_filter((array) ($data['preferred_job_types'] ?? []), 'is_string'))) ?: null,
            'preferred_radius_km' => isset($data['preferred_radius_km']) && $data['preferred_radius_km'] !== '' ? round((float) $data['preferred_radius_km'], 2) : null,
            'preferred_min_fee' => isset($data['preferred_min_fee']) && $data['preferred_min_fee'] !== '' ? round((float) $data['preferred_min_fee'], 2) : null,
        ]);

        if ($vehicleChanged && $rider->status === 'approved') {
            // รอแอดมินตรวจเอกสารชุดใหม่ → ปิดรับงานทันที (Rider::onlineBlockReason = DOCUMENTS_REVIEW_PENDING)
            $rider->forceFill(['documents_changed_at' => now(), 'availability' => 'offline']);
        }

        $rider->save();
        $rider->refresh();

        $missing = $this->missingDocuments($rider);

        if ($vehicleChanged && $rider->status === 'approved') {
            Log::info('Rider: vehicle changed after approval', ['rider_id' => $rider->id, 'vehicle_type' => $rider->vehicle_type]);

            $this->notifier->notifyAdmins(
                'ไรเดอร์เปลี่ยนยานพาหนะ ต้องตรวจซ้ำ',
                "{$rider->full_name} เปลี่ยนเป็น{$rider->vehicle_type_text}".($missing ? ' (ยังขาด '.$this->documentLabels($missing).')' : ''),
                ['rider_id' => (int) $rider->id, 'event' => 'rider_vehicle_changed'],
                $this->adminRiderUrl($rider),
            );
        }

        return ['rider' => $rider, 'vehicle_changed' => $vehicleChanged, 'missing' => $missing];
    }

    // =====================================================
    // สิทธิ์ในเครื่อง / ความยินยอม / เปิด-ปิดรับงาน
    // =====================================================

    /**
     * บันทึกสิทธิ์ที่ผู้ใช้อนุญาตในเครื่อง + ความยินยอมแชร์ตำแหน่งให้ลูกค้าระหว่างงาน
     *
     * gps = true เมื่ออนุญาตตำแหน่ง "ขณะใช้แอป" ก็พอ (ไม่บังคับ "ตลอดเวลา" — audit PLAY-14)
     *
     * @param  array<string, mixed>  $input  gps?, camera?, microphone?, notification?, location_consent?
     *
     * @throws RiderJobException HAS_ACTIVE_JOB เมื่อถอนความยินยอมระหว่างมีงาน
     */
    public function updatePermissions(Rider $rider, array $input): Rider
    {
        $permissions = [];
        foreach (['gps', 'camera', 'microphone', 'notification'] as $key) {
            if (array_key_exists($key, $input) && $input[$key] !== null) {
                $permissions[$key] = filter_var($input[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        if ($permissions !== []) {
            $rider->grantPermissions($permissions);
        }

        if (array_key_exists('location_consent', $input) && $input['location_consent'] !== null) {
            if (filter_var($input['location_consent'], FILTER_VALIDATE_BOOLEAN)) {
                $rider->grantLocationConsent();
            } elseif ($rider->share_location_consent_at !== null) {
                if ($active = $rider->activeJob()) {
                    throw RiderJobException::hasActiveJob((int) $active->id);
                }
                $rider->forceFill(['share_location_consent_at' => null])->save();
            }
        }

        return $rider->fresh();
    }

    /**
     * เปิด/ปิดรับงาน
     *
     * เปิดรับงานได้เมื่อ: อนุญาตตำแหน่งในเครื่องแล้ว (ขณะใช้แอปก็พอ) หรือเพิ่งส่งพิกัดเข้ามา
     * — แอปรุ่นเก่าส่ง gps=false เมื่อไม่ได้ "อนุญาตตลอดเวลา" แต่ถ้าพิกัดยังเข้ามาแปลว่า GPS ใช้งานได้
     *
     * @throws RiderJobException GPS_REQUIRED|HAS_ACTIVE_JOB|NOT_ELIGIBLE|INVALID_AVAILABILITY
     */
    public function setAvailability(Rider $rider, string $availability): Rider
    {
        if ($availability === 'online') {
            // ลำดับเหตุผลให้ตรงกับสิ่งที่ไรเดอร์ต้องแก้ก่อน: งานค้าง → สถานะบัญชี → GPS
            if ($active = $rider->activeJob()) {
                throw RiderJobException::hasActiveJob((int) $active->id);
            }

            if ($reason = $rider->onlineBlockReason()) {
                throw RiderJobException::notEligible($reason['message'], $reason['code']);
            }
        }

        if ($availability === 'online' && ! $rider->gps_permission_granted && ! $rider->hasFreshLocation()) {
            throw new RiderJobException(
                'GPS_REQUIRED',
                'กรุณาอนุญาตให้แอปเข้าถึงตำแหน่ง (เลือก "ขณะใช้แอป" ก็ได้) แล้วลองเปิดรับงานอีกครั้ง',
                403,
                ['require_permission' => 'gps']
            );
        }

        $rider->setAvailability($availability);

        Log::info('Rider: availability changed', ['rider_id' => $rider->id, 'availability' => $availability]);

        return $rider->fresh();
    }

    // =====================================================
    // ข้อมูลสถานะไรเดอร์ (ชุดเดียวทุกช่องทาง)
    // =====================================================

    /**
     * ข้อมูลไรเดอร์สำหรับ /rider/status และหน้าเว็บ (ตัวเลขเป็น number เสมอ)
     *
     * @param  callable(Rider, string): ?string|null  $documentUrl  ลิงก์เปิดเอกสารของตัวเอง (ตามช่องทาง)
     * @return array<string, mixed>
     */
    public function statusPayload(Rider $rider, ?callable $documentUrl = null): array
    {
        $rider->loadMissing('user');
        $user = $rider->user;

        $onlineBlock = $rider->onlineBlockReason();
        $acceptBlock = $rider->acceptBlockReason();
        $activeJob = $rider->activeJob();
        $missing = $this->missingDocuments($rider);
        $walletBalance = $rider->walletBalance();
        $documentsChangedAt = $rider->getAttribute('documents_changed_at');

        return [
            'id' => (int) $rider->id,
            'status' => (string) $rider->status,
            'status_text' => $rider->status_text,
            'availability' => (string) $rider->availability,
            'availability_text' => $rider->availability_text,
            'rejection_reason' => $rider->status === 'rejected' ? $rider->rejection_reason : null,
            'suspension_reason' => $rider->status === 'suspended' ? $rider->suspension_reason : null,
            'can_reapply' => in_array($rider->status, ['rejected', 'inactive'], true),
            'full_name' => (string) $rider->full_name,
            'phone' => (string) $rider->phone,
            'id_card_number_masked' => $this->maskIdCard($rider->id_card_number),
            'birth_date' => $rider->birth_date?->toDateString(),
            'address' => $rider->address,
            'province' => $rider->province,
            'district' => $rider->district,
            'vehicle_type' => $rider->vehicle_type,
            'vehicle_type_text' => $rider->vehicle_type_text,
            'vehicle_plate' => $rider->vehicle_plate,
            'vehicle_brand' => $rider->vehicle_brand,
            'vehicle_color' => $rider->vehicle_color,
            'rider_type' => $rider->rider_type,
            'rating' => round((float) $rider->rating, 2),
            'rating_count' => (int) $rider->rating_count,
            'total_jobs' => (int) $rider->total_jobs,
            'completed_jobs' => (int) $rider->completed_jobs,
            'cancelled_jobs' => (int) $rider->cancelled_jobs,
            'completion_rate' => (float) $rider->completion_rate,
            'total_earnings' => round((float) $rider->total_earnings, 2),
            'wallet_balance' => $walletBalance,
            // วงเงิน COD ที่ใช้รับงานใหม่ได้ = ยอดวอลเลต − ยอดที่ติดภาระนำส่งเงิน COD ของงานก่อน
            'cod_credit_available' => round(max(0.0, $walletBalance - RiderJob::codReserveForUser((int) $rider->user_id)), 2),
            'documents' => $this->documentFlags($rider),
            'documents_required' => $this->requiredDocumentTypes($rider),
            'documents_missing' => $missing,
            'documents_complete' => $missing === [],
            'documents_pending_review' => $documentsChangedAt !== null,
            'document_urls' => $documentUrl
                ? collect($this->documentList($rider, $documentUrl))->mapWithKeys(fn ($d) => [$d['type'] => $d['url']])->all()
                : null,
            'permissions' => [
                'gps' => (bool) $rider->gps_permission_granted,
                'camera' => (bool) $rider->camera_permission_granted,
                'microphone' => (bool) $rider->microphone_permission_granted,
                'notification' => (bool) $rider->notification_permission_granted,
                'location_consent' => $rider->share_location_consent_at !== null,
            ],
            'location_consent_at' => $rider->share_location_consent_at?->toIso8601String(),
            'last_location' => ($rider->last_latitude !== null && $rider->last_longitude !== null) ? [
                'latitude' => (float) $rider->last_latitude,
                'longitude' => (float) $rider->last_longitude,
                'updated_at' => $rider->last_location_update?->toIso8601String(),
                'is_fresh' => $rider->hasFreshLocation(),
            ] : null,
            'can_go_online' => $onlineBlock === null && $activeJob === null,
            'online_block_reason' => $onlineBlock,
            'can_accept_jobs' => $acceptBlock === null,
            'block_reason' => $acceptBlock,
            'active_job_id' => $activeJob ? (int) $activeJob->id : null,
            'deposit' => [
                'required' => app(DeliveryFeeCalculator::class)->boolSetting('rider.require_deposit'),
                'status' => $rider->deposit_status,
                'amount' => round((float) $rider->deposit_amount, 2),
            ],
            'kyc' => [
                'status' => $user?->kyc_status ?? 'not_submitted',
                'verified' => $user ? $user->isKycVerified() : false,
                // ถอนรายได้ต้องยืนยันตัวตน (KYC) ก่อน — ให้แอป/เว็บโชว์ปุ่มพาไปทำ KYC (audit CC-24)
                'required_for_withdrawal' => true,
            ],
            'approved_at' => $rider->approved_at?->toIso8601String(),
            'created_at' => $rider->created_at?->toIso8601String(),
        ];
    }

    /**
     * เลขบัตรแบบปิดบัง เช่น 1-10xx-xxxxx-83-1 → แสดงแค่ 3 ตัวหน้า 3 ตัวท้าย
     */
    public function maskIdCard(?string $idCard): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $idCard) ?? '';

        if (strlen($digits) < 6) {
            return $digits === '' ? null : str_repeat('x', strlen($digits));
        }

        return substr($digits, 0, 3).str_repeat('x', strlen($digits) - 6).substr($digits, -3);
    }

    /**
     * เอกสารถูกเปลี่ยนหลังอนุมัติเมื่อไร (null = ไม่มีรอตรวจ)
     */
    public function documentsChangedAt(Rider $rider): ?Carbon
    {
        $value = $rider->getAttribute('documents_changed_at');

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * ลิงก์หน้าไรเดอร์ในหลังบ้าน (ไม่พังถ้า route ยังไม่ถูกโหลด เช่นในคิว)
     */
    private function adminRiderUrl(Rider $rider): ?string
    {
        try {
            return route('admin.riders.show', $rider->id);
        } catch (\Throwable) {
            return null;
        }
    }
}
