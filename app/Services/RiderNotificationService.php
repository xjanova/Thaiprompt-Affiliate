<?php

namespace App\Services;

use App\Jobs\SendRiderPushJob;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * แจ้งเตือนของระบบไรเดอร์ = in-app notification + Expo push (ผ่านคิว)
 *
 * ❗ ไม่ใช้ LINE push เด็ดขาด (โควต้า OA 300 ครั้ง/เดือน — ดู .claude/LINE_MESSAGING_RULES.md)
 *
 * เส้นทาง push:
 *   - มีสะพานกลาง App\Jobs\SendNotificationPush (NotificationService::create → กล่องแจ้งเตือนแอป + Expo push)
 *     → ให้สะพานส่งเอง (payload = data ของแจ้งเตือน: type, job_id, screen ...) ไม่ยิงซ้ำ
 *   - ไม่มีสะพาน → ยิงเองผ่าน SendRiderPushJob (ExpoPushService::sendToUser) และตั้ง push_sent ไว้
 *
 * ทุกเมธอดในคลาสนี้ห้าม throw — การแจ้งเตือนล้มเหลวต้องไม่ทำให้งานหลักพัง
 */
class RiderNotificationService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * แจ้งผู้ใช้หลายคนด้วยข้อความเดียวกัน
     *
     * @param  array<int, int|User|null>  $users
     * @param  array<string, mixed>  $data  payload ให้แอปเลือกหน้าจอ (type, job_id ...)
     */
    public function notifyUsers(
        array $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'normal',
        bool $push = true,
    ): void {
        // ส่งหลัง transaction ที่เปิดอยู่ commit แล้วเท่านั้น (rollback = ไม่แจ้ง ไม่มี push หลอก)
        $send = function () use ($users, $type, $title, $message, $data, $actionUrl, $priority, $push) {
            $this->sendNow($users, $type, $title, $message, $data, $actionUrl, $priority, $push);
        };

        try {
            DB::afterCommit($send);
        } catch (\RuntimeException) {
            $send();
        }
    }

    /**
     * ส่งทันที (ภายใน — เรียกผ่าน notifyUsers เพื่อให้รอ commit ก่อน)
     *
     * @param  array<int, int|User|null>  $users
     * @param  array<string, mixed>  $data
     */
    private function sendNow(
        array $users,
        string $type,
        string $title,
        string $message,
        array $data,
        ?string $actionUrl,
        string $priority,
        bool $push,
    ): void {
        $userModels = $this->resolveUsers($users);
        if ($userModels === []) {
            return;
        }

        $data = array_merge(['type' => $type], $data, ['push_channel' => 'rider']);
        $selfPush = $push && ! $this->bridgeHandlesPush();
        $notificationIds = [];

        foreach ($userModels as $user) {
            try {
                $notification = $this->notifications->create(
                    $user,
                    $type,
                    $title,
                    $message,
                    $data,
                    $actionUrl,
                    null,
                    $push ? $priority : 'low',
                    in_array($priority, ['high', 'urgent'], true),
                );

                // ยิง push เอง → ตั้ง push_sent ไว้ก่อน กันระบบอื่นยิงซ้ำ (push_sent_at = เวลาที่ส่งสำเร็จจริง)
                if ($selfPush) {
                    $notification->forceFill(['push_sent' => true, 'push_sent_at' => null])->saveQuietly();
                }

                $notificationIds[] = $notification->id;
            } catch (\Throwable $e) {
                Log::warning('RiderNotification: in-app notification failed', [
                    'user_id' => $user->id,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $selfPush) {
            return;
        }

        try {
            SendRiderPushJob::dispatch(
                array_map(fn (User $u) => (int) $u->id, $userModels),
                $title,
                $message,
                $this->pushPayload($data),
                $notificationIds,
            );
        } catch (\Throwable $e) {
            Log::warning('RiderNotification: push dispatch failed', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }

    /**
     * แจ้งผู้ใช้คนเดียว
     *
     * @param  array<string, mixed>  $data
     */
    public function notifyUser(
        int|User|null $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'normal',
    ): void {
        $this->notifyUsers([$user], $type, $title, $message, $data, $actionUrl, $priority);
    }

    /**
     * สะพานกลาง NotificationService → มือถือ มีอยู่หรือไม่ (ถ้ามี ให้สะพานส่ง push แทน)
     */
    public function bridgeHandlesPush(): bool
    {
        return class_exists(\App\Jobs\SendNotificationPush::class);
    }

    /**
     * แจ้งแอดมินทุกคน (in-app + push ระดับ high — เรื่องที่ต้องลงมือจัดการ)
     *
     * @param  array<string, mixed>  $data
     */
    public function notifyAdmins(string $title, string $message, array $data = [], ?string $actionUrl = null): void
    {
        try {
            $adminIds = User::query()
                ->where(function ($q) {
                    $q->whereIn('role', ['admin', 'super_admin'])->orWhere('is_super_admin', true);
                })
                ->whereNull('blocked_at')
                ->limit(50)
                ->pluck('id')
                ->all();
        } catch (\Throwable $e) {
            Log::warning('RiderNotification: cannot load admins', ['error' => $e->getMessage()]);

            return;
        }

        $this->notifyUsers($adminIds, 'rider_admin', $title, $message, array_merge(['type' => 'rider_admin'], $data), $actionUrl, 'high');
    }

    // =====================================================
    // ข้อความสำเร็จรูป
    // =====================================================

    /**
     * แจ้งไรเดอร์หลายคนว่ามีงานใหม่ใกล้ตัว
     *
     * @param  iterable<Rider>  $riders
     */
    public function notifyRidersNewJob(iterable $riders, RiderJob $job): void
    {
        $userIds = [];
        foreach ($riders as $rider) {
            if ($rider->user_id) {
                $userIds[] = (int) $rider->user_id;
            }
        }

        if ($userIds === []) {
            return;
        }

        $earn = number_format((float) $job->rider_earnings, 0);
        $distance = number_format((float) $job->distance_km, 1);
        $cod = (float) $job->cod_amount > 0 ? ' • เก็บเงินปลายทาง ฿'.number_format((float) $job->cod_amount, 0) : '';

        $this->notifyUsers(
            $userIds,
            'rider_job_offer',
            "งานใหม่ รายได้ ฿{$earn}",
            "{$job->job_type_text} ระยะ {$distance} กม.{$cod} — กดรับงานก่อนคนอื่น",
            ['type' => 'rider_job_offer', 'job_id' => (int) $job->id, 'screen' => 'rider-jobs'],
            null,
            'high',
        );
    }

    /**
     * แจ้งไรเดอร์ของงานเรื่องสถานะงาน (ยกเลิก/มอบหมายใหม่ ฯลฯ)
     */
    public function notifyRider(?Rider $rider, RiderJob $job, string $title, string $message, string $event): void
    {
        if (! $rider || ! $rider->user_id) {
            return;
        }

        $this->notifyUser(
            (int) $rider->user_id,
            'rider_job_update',
            $title,
            $message,
            ['type' => 'rider_job_update', 'event' => $event, 'job_id' => (int) $job->id, 'screen' => 'rider-job-detail'],
            null,
            'high',
        );
    }

    /**
     * แจ้งผู้ซื้อ/ผู้ขายของออเดอร์ต้นทางเมื่อสถานะงานไรเดอร์เปลี่ยน
     *
     * @param  array<int, int>  $userIds
     */
    public function notifyParties(array $userIds, RiderJob $job, string $event): void
    {
        [$title, $message] = match ($event) {
            'accepted' => ['ไรเดอร์รับงานแล้ว', "ไรเดอร์กำลังไปรับของ งาน #{$job->job_number}"],
            'picking_up' => ['ไรเดอร์กำลังไปรับของ', "ไรเดอร์กำลังเดินทางไปรับของ งาน #{$job->job_number}"],
            'picked_up' => ['ไรเดอร์รับของแล้ว', "ไรเดอร์รับของเรียบร้อย กำลังออกเดินทาง งาน #{$job->job_number}"],
            'delivering' => ['กำลังจัดส่ง', "ไรเดอร์กำลังนำส่งถึงคุณ งาน #{$job->job_number}"],
            'completed' => ['ส่งของสำเร็จ', "ไรเดอร์ส่งของเรียบร้อยแล้ว งาน #{$job->job_number}"],
            'released' => ['กำลังหาไรเดอร์ใหม่', "ไรเดอร์คนเดิมคืนงาน ระบบกำลังหาไรเดอร์คนใหม่ให้ งาน #{$job->job_number}"],
            'reassigned' => ['เปลี่ยนไรเดอร์แล้ว', "ทีมงานเปลี่ยนไรเดอร์ผู้ส่งให้แล้ว งาน #{$job->job_number}"],
            'failed' => ['ส่งของไม่สำเร็จ', "ไรเดอร์ส่งของไม่สำเร็จ ทีมงานจะติดต่อกลับ งาน #{$job->job_number}"],
            'cancelled' => ['งานส่งถูกยกเลิก', "งานส่งของ #{$job->job_number} ถูกยกเลิกแล้ว"],
            'no_rider' => ['ยังหาไรเดอร์ไม่ได้', "ตอนนี้ยังไม่มีไรเดอร์ว่างใกล้ร้าน ทีมงานกำลังช่วยจัดหา งาน #{$job->job_number}"],
            default => ['อัปเดตการจัดส่ง', "สถานะงานส่งของ #{$job->job_number}: {$job->status_text}"],
        };

        $data = [
            'type' => 'delivery_update',
            'event' => $event,
            'job_id' => (int) $job->id,
            'source_type' => $job->source_type ? class_basename($job->source_type) : null,
            'source_id' => $job->source_id ? (int) $job->source_id : null,
        ];

        $trackingUrl = $job->tracking_url;
        if ($trackingUrl && in_array($event, ['accepted', 'picking_up', 'picked_up', 'delivering'], true)) {
            $data['tracking_url'] = $trackingUrl;
        }

        $this->notifyUsers($userIds, 'delivery_update', $title, $message, $data, $trackingUrl);
    }

    /**
     * สร้าง payload push (ตัดค่าที่ไม่ใช่ scalar ออก — Expo รับ JSON เล็กๆ)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function pushPayload(array $data): array
    {
        return array_filter($data, fn ($v) => is_scalar($v) || $v === null);
    }

    /**
     * @param  array<int, int|User|null>  $users
     * @return array<int, User>
     */
    private function resolveUsers(array $users): array
    {
        $models = [];
        $ids = [];

        foreach ($users as $user) {
            if ($user instanceof User) {
                $models[$user->id] = $user;
            } elseif (is_numeric($user) && (int) $user > 0) {
                $ids[] = (int) $user;
            }
        }

        $ids = array_values(array_diff(array_unique($ids), array_keys($models)));
        if ($ids !== []) {
            try {
                foreach (User::whereIn('id', $ids)->get() as $user) {
                    $models[$user->id] = $user;
                }
            } catch (\Throwable $e) {
                Log::warning('RiderNotification: cannot load users', ['error' => $e->getMessage()]);
            }
        }

        return array_values($models);
    }
}
