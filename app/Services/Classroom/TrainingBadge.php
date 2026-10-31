<?php

namespace App\Services\Classroom;

use App\Models\ClassroomCertification;
use App\Models\ClassroomCourse;
use App\Models\User;

/**
 * 🎓 ป้าย "ผ่านการอบรม" (ตรากลมกรมท่าขอบทอง) — ตัวช่วยเดียวที่ทุก payload ใช้
 *
 * รูปแบบ (null-safe เสมอ — ยังไม่ผ่าน = null):
 *   {certified: true, role: 'seller'|'rider'|'buyer', label: 'ผ่านการอบรมไรเดอร์', needs_refresh: bool}
 *
 * กัน N+1:
 *   - User ที่ eager-load `classroomCertifications` แล้ว → ไม่ยิง query
 *   - รายการ → เรียก TrainingBadge::prime($userIds) ก่อน 1 ครั้ง (query เดียวทั้งรายการ)
 *   - ที่เหลือจำผลไว้ต่อคำขอ (scoped instance — ล้างเองทุกคำขอ/ทุกงานคิว)
 *
 * @example TrainingBadge::for($rider->user_id, 'rider');      // ป้ายไรเดอร์ของคนนี้ หรือ null
 * @example TrainingBadge::allFor($user);                      // ['seller' => …, 'rider' => …, 'buyer' => …]
 */
final class TrainingBadge
{
    /** @var array<int, array<string, ClassroomCertification>> user_id → role → ใบรับรอง (โหลดแล้ว = มี key แม้ว่างเปล่า) */
    private array $certs = [];

    /** @var array<string, string>|null role → rules_version ของคอร์สที่เปิดอยู่ */
    private ?array $currentVersions = null;

    public static function instance(): self
    {
        $app = app();
        if (! $app->bound(self::class)) {
            $app->scoped(self::class);
        }

        return $app->make(self::class);
    }

    /**
     * ป้ายของบทบาทเดียว (ยังไม่ผ่าน / บทบาทไม่รู้จัก / ไม่มีผู้ใช้ = null)
     *
     * @return array{certified: bool, role: string, label: string, needs_refresh: bool}|null
     */
    public static function for(User|int|null $user, string $role): ?array
    {
        return self::instance()->badge($user, $role);
    }

    /**
     * ป้ายครบทุกบทบาท (หน้าโปรไฟล์ของตัวเอง)
     *
     * @return array{seller: array<string, mixed>|null, rider: array<string, mixed>|null, buyer: array<string, mixed>|null}
     */
    public static function allFor(User|int|null $user): array
    {
        $out = [];
        foreach (ClassroomCourse::ROLES as $role) {
            $out[$role] = self::for($user, $role);
        }

        return $out;
    }

    /**
     * โหลดใบรับรองของผู้ใช้หลายคนในครั้งเดียว (เรียกก่อนวน serialize รายการ)
     *
     * @param  iterable<int|string|null>  $userIds
     */
    public static function prime(iterable $userIds): void
    {
        self::instance()->load($userIds);
    }

    /**
     * ล้างที่จำไว้ (หลังออกใบรับรอง / แอดมินเปลี่ยนรุ่นกติกา)
     */
    public static function forget(?int $userId = null): void
    {
        $self = self::instance();
        if ($userId === null) {
            $self->certs = [];
            $self->currentVersions = null;

            return;
        }

        unset($self->certs[$userId]);
    }

    /**
     * ชื่อป้ายภาษาไทย เช่น "ผ่านการอบรมไรเดอร์"
     */
    public static function label(string $role): string
    {
        return 'ผ่านการอบรม'.(ClassroomCourse::ROLE_LABELS[$role] ?? '');
    }

    /**
     * @return array{certified: bool, role: string, label: string, needs_refresh: bool}|null
     */
    public function badge(User|int|null $user, string $role): ?array
    {
        if (! ClassroomCourse::isValidRole($role)) {
            return null;
        }

        $cert = $this->certificationFor($user, $role);
        if ($cert === null) {
            return null;
        }

        return [
            'certified' => true,
            'role' => $role,
            'label' => self::label($role),
            'needs_refresh' => $this->needsRefresh($role, (string) $cert->rules_version),
        ];
    }

    /**
     * ใบรับรองรุ่นเก่ากว่าคอร์สที่เปิดอยู่ = ต้องอบรมทบทวน (ไม่มีคอร์สเปิด = ไม่ต้องทบทวน)
     */
    public function needsRefresh(string $role, string $rulesVersion): bool
    {
        $current = $this->currentVersions()[$role] ?? null;

        return $current !== null && $current !== $rulesVersion;
    }

    public function certificationFor(User|int|null $user, string $role): ?ClassroomCertification
    {
        if ($user instanceof User && $user->relationLoaded('classroomCertifications')) {
            return $user->classroomCertifications->firstWhere('role', $role);
        }

        $userId = $user instanceof User ? (int) $user->getKey() : (int) $user;
        if ($userId <= 0) {
            return null;
        }

        if (! array_key_exists($userId, $this->certs)) {
            $this->load([$userId]);
        }

        return $this->certs[$userId][$role] ?? null;
    }

    /**
     * @param  iterable<int|string|null>  $userIds
     */
    private function load(iterable $userIds): void
    {
        $missing = [];
        foreach ($userIds as $id) {
            $id = (int) $id;
            if ($id > 0 && ! array_key_exists($id, $this->certs)) {
                $missing[$id] = $id;
            }
        }

        if ($missing === []) {
            return;
        }

        foreach ($missing as $id) {
            $this->certs[$id] = [];
        }

        // 🛡️ ตราเป็นของประดับ — อ่านไม่ได้ (ตารางยังไม่มี/DB สะดุด) ต้องไม่ทำให้ /me หรือหน้าร้านพัง 500
        try {
            foreach (array_chunk(array_values($missing), 500) as $chunk) {
                ClassroomCertification::query()
                    ->whereIn('user_id', $chunk)
                    ->get(['id', 'user_id', 'role', 'course_id', 'rules_version', 'points_awarded', 'certified_at'])
                    ->each(function (ClassroomCertification $cert) {
                        $this->certs[(int) $cert->user_id][(string) $cert->role] = $cert;
                    });
            }
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::warning('TrainingBadge: อ่านตราไม่ได้ — แสดงเป็นไม่มีตรา', ['error' => class_basename($e)]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function currentVersions(): array
    {
        if ($this->currentVersions === null) {
            $this->currentVersions = [];
            try {
                ClassroomCourse::query()->active()->ordered()->get(['id', 'role', 'rules_version', 'sort_order'])
                    ->each(function (ClassroomCourse $course) {
                        // คอร์สแรกตามลำดับของแต่ละบทบาท = คอร์สที่ใช้จริง (ตรงกับ activeForRole)
                        $this->currentVersions[$course->role] ??= (string) $course->rules_version;
                    });
            } catch (\Illuminate\Database\QueryException $e) {
                // ตารางยังไม่มี → ไม่มีเวอร์ชันให้เทียบ (ตราที่มีอยู่ถือว่าใช้ได้)
            }
        }

        return $this->currentVersions;
    }
}
