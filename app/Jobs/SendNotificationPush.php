<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Services\ExpoPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🔔 สะพาน "แจ้งเตือนในระบบ → มือถือ" (audit CC-07)
 *
 * เดิม NotificationService::create เขียนแค่ตาราง notifications (กล่องแจ้งเตือนบนเว็บ)
 * → ไม่มีใครเขียน push_sent เลย และแอปอ่านกล่องแจ้งเตือนจากตาราง user_notifications
 *   (MobileApiController::getNotifications) ผลคือไรเดอร์/ร้าน/ผู้ซื้อไม่เห็นแจ้งเตือนในแอปเลย
 *
 * งานนี้ทำ 2 อย่างต่อ 1 แจ้งเตือน:
 *   1. คัดลอกลงกล่องแจ้งเตือนของแอป (user_notifications) — ทุกระดับความสำคัญ
 *   2. ส่ง Expo push เข้ามือถือ — เฉพาะ priority normal/high/urgent และเมื่อผู้ใช้มีเครื่องที่ลงทะเบียนไว้
 *      แล้วตั้ง notifications.push_sent/push_sent_at (ส่งไม่สำเร็จ = คืนค่าเป็น false)
 *
 * กันส่งซ้ำ:
 *   - "จอง" แถวด้วย UPDATE ... WHERE push_sent = 0 (อะตอมมิก) ก่อนส่ง → รันซ้ำ/พร้อมกันส่งครั้งเดียว
 *   - ถ้าโค้ดส่วนอื่นยิง ExpoPushService::sendToUser() หัวข้อ+ข้อความเดียวกันไปแล้วเมื่อครู่
 *     (เช่นงานไรเดอร์ที่ยิง push เองด้วย) → ข้าม push แต่ยังคัดลอกลงกล่องแจ้งเตือนของแอป
 *
 * ❌ ห้ามใช้ LINE push แทน (โควต้า 300 ครั้ง/เดือน)
 * สวิตช์ปิดทั้งระบบ: settings.notifications.push_bridge_enabled = false
 */
class SendNotificationPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** ห้าม retry อัตโนมัติ — ส่ง push ไปแล้วบางเครื่องแล้ว retry = เด้งซ้ำ */
    public int $tries = 1;

    public int $timeout = 60;

    /** priority ที่ส่ง push (low = เก็บในกล่องอย่างเดียว) */
    public const PUSH_PRIORITIES = ['normal', 'high', 'urgent'];

    public function __construct(public int $notificationId) {}

    /**
     * ส่งงานนี้เข้าคิวแบบปลอดภัย (เรียกจาก NotificationService::create)
     *
     * - queue แบบ async (database/redis): เข้าคิว default หลัง transaction commit + หน่วง 5 วินาที
     *   (ให้ push ที่โค้ดต้นทางยิงเองมาก่อน ตัวกันซ้ำจะได้เห็น)
     * - queue แบบ sync: รันหลังส่ง response แล้ว — ห้ามให้การยิง Expo (timeout 30 วิ) ถ่วง request
     */
    public static function dispatchFor(Notification $notification): void
    {
        if (! self::shouldDispatch($notification)) {
            return;
        }

        try {
            if (config('queue.default') === 'sync') {
                if (app()->runningInConsole()) {
                    self::dispatchSync((int) $notification->id);
                } else {
                    self::dispatchAfterResponse((int) $notification->id);
                }

                return;
            }

            self::dispatch((int) $notification->id)
                ->onQueue('default')
                ->afterCommit()
                ->delay(now()->addSeconds(5));
        } catch (\Throwable $e) {
            // เข้าคิวไม่ได้ ห้ามทำให้การสร้างแจ้งเตือน (และธุรกรรมต้นทาง) ล้ม
            Log::warning('SendNotificationPush: dispatch failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * แจ้งเตือนแบบไหนต้องส่งต่อเข้ามือถือ
     */
    public static function shouldDispatch(Notification $notification): bool
    {
        // broadcast ถึงทุกคน → ใช้ระบบ push campaign ของแอดมิน (MobilePushNotification) แทน
        if ($notification->is_broadcast) {
            return false;
        }

        // แจ้งเตือนตั้งเวลาที่ยังไม่ถึงเวลา → SendScheduledNotifications จะส่งให้ตอนถึงเวลา
        if ($notification->is_scheduled && ! $notification->is_sent) {
            return false;
        }

        return ! $notification->push_sent;
    }

    public function handle(ExpoPushService $expo): void
    {
        $notification = Notification::find($this->notificationId);
        if (! $notification || $notification->push_sent) {
            return;
        }

        $user = User::find($notification->user_id);
        if (! $user || $user->isSuspended()) {
            return;
        }

        if (! (bool) Setting::get('notifications.push_bridge_enabled', true)) {
            return;
        }

        $title = trim((string) $notification->title);
        $body = trim((string) $notification->message);
        if ($title === '' && $body === '') {
            return;
        }

        // 1) กล่องแจ้งเตือนของแอป (ทำก่อน push — ผู้ใช้กดแจ้งเตือนแล้วต้องเจอรายการ)
        $inboxId = $this->mirrorToAppInbox($notification, $user, $title, $body);

        // 2) push เข้ามือถือ
        if (! in_array((string) $notification->priority, self::PUSH_PRIORITIES, true)) {
            return;
        }

        // จองแถวแบบอะตอมมิก — รันซ้ำ/พร้อมกันจะมีแค่ตัวเดียวที่ได้ส่ง
        $claimed = Notification::whereKey($notification->id)
            ->where('push_sent', false)
            ->update(['push_sent' => true, 'push_sent_at' => now()]);
        if ($claimed === 0) {
            return;
        }

        // โค้ดต้นทางยิง push หัวข้อ+ข้อความนี้ไปเองแล้วเมื่อครู่ → ไม่ยิงซ้ำ (นับว่าส่งแล้ว)
        if (ExpoPushService::wasRecentlyPushed((int) $user->id, $title, $body)) {
            return;
        }

        $data = is_array($notification->data) ? $notification->data : [];
        $channel = in_array($data['channel'] ?? null, self::ANDROID_CHANNELS, true)
            ? $data['channel']
            : self::channelFor((string) $notification->type, (string) $notification->priority);

        $payload = array_merge($data, [
            'type' => $notification->type,
            'notification_id' => $notification->id,
            'user_notification_id' => $inboxId,
            'url' => $notification->action_url,
            'channel' => $channel,
        ]);

        try {
            $result = $expo->sendToUser((int) $user->id, $title !== '' ? $title : 'แจ้งเตือน', $body, $payload);
        } catch (\Throwable $e) {
            $result = ['success' => 0, 'failed' => 1, 'errors' => [$e->getMessage()]];
        }

        if (($result['success'] ?? 0) < 1) {
            // ไม่มีเครื่อง/ส่งไม่ผ่าน → คืนสถานะ (ไม่ retry เพื่อกันเด้งซ้ำ — ผู้ใช้ยังเห็นในกล่องแจ้งเตือน)
            Notification::whereKey($notification->id)->update(['push_sent' => false, 'push_sent_at' => null]);

            if (! empty($result['errors'])) {
                Log::info('SendNotificationPush: push not delivered', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'errors' => array_slice((array) $result['errors'], 0, 3),
                ]);
            }
        }
    }

    /**
     * คัดลอกลง user_notifications (กล่องแจ้งเตือนของแอป) แบบไม่ซ้ำ
     */
    private function mirrorToAppInbox(Notification $notification, User $user, string $title, string $body): ?int
    {
        if (! Schema::hasTable('user_notifications')) {
            return null;
        }

        try {
            $existing = DB::table('user_notifications')
                ->where('user_id', $user->id)
                ->where('data->source_notification_id', $notification->id)
                ->value('id');
            if ($existing) {
                return (int) $existing;
            }

            $data = is_array($notification->data) ? $notification->data : [];
            $data['source_notification_id'] = $notification->id;
            $data['source_type'] = $notification->type;

            return (int) DB::table('user_notifications')->insertGetId([
                'user_id' => $user->id,
                'title' => mb_substr($title !== '' ? $title : 'แจ้งเตือน', 0, 255),
                'body' => $body,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'type' => $this->inboxTypeFor((string) $notification->type),
                'icon' => $notification->icon ? mb_substr((string) $notification->icon, 0, 255) : null,
                'action_url' => $notification->action_url ? mb_substr((string) $notification->action_url, 0, 255) : null,
                'is_read' => (bool) $notification->is_read,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at ?? now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SendNotificationPush: mirror to app inbox failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * แปลงประเภทแจ้งเตือนเว็บ → enum ของ user_notifications.type
     * (general, order, rider, wallet, promotion, system, ticket)
     */
    public static function inboxTypeFor(string $type): string
    {
        $type = strtolower($type);

        return match (true) {
            str_contains($type, 'rider') => 'rider',
            str_contains($type, 'order'), str_contains($type, 'fresh_market'), str_contains($type, 'shop'),
            str_contains($type, 'seller') => 'order',
            str_contains($type, 'wallet'), str_contains($type, 'deposit'), str_contains($type, 'withdraw'),
            str_contains($type, 'transfer'), str_contains($type, 'commission'), str_contains($type, 'debt'),
            str_contains($type, 'payment'), str_contains($type, 'refund') => 'wallet',
            str_contains($type, 'ticket') => 'ticket',
            str_contains($type, 'promo') => 'promotion',
            str_contains($type, 'kyc'), str_contains($type, 'account'), str_contains($type, 'security'),
            str_contains($type, 'system'), str_contains($type, 'rank') => 'system',
            default => 'general',
        };
    }

    /**
     * Android notification channel — ต้องเป็น channel ที่แอปสร้างไว้จริงเท่านั้น
     * (thaiprompt/services/notifications.ts: default, important, orders, messages, promotions)
     * ⚠️ ส่ง channel ที่ไม่มีในเครื่อง = Android ไม่แสดงแจ้งเตือนเลย
     */
    public const ANDROID_CHANNELS = ['default', 'important', 'orders', 'messages', 'promotions'];

    public static function channelFor(string $type, string $priority = 'normal'): string
    {
        if (in_array($priority, ['high', 'urgent'], true)) {
            return 'important';
        }

        return match (self::inboxTypeFor($type)) {
            'rider' => 'important',
            'order' => 'orders',
            'promotion' => 'promotions',
            default => 'default',
        };
    }
}
