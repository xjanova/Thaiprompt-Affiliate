<?php

namespace App\Services;

use App\Models\ServiceBooking;
use App\Models\ServiceBookingNotification;
use App\Models\ServiceProvider;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ServiceBookingNotificationService
 *
 * บริการส่งการแจ้งเตือนสำหรับ Service Booking System
 * รองรับ LINE OA, Email, SMS, Push Notification
 */
class ServiceBookingNotificationService
{
    /** สวิตช์ LINE push (ค่าเริ่มต้นปิด) */
    public const LINE_PUSH_SETTING = 'service_booking_line_push_enabled';

    /**
     * ส่งการแจ้งเตือนงานใหม่ถึง Provider
     *
     * @return array ผลลัพธ์การส่ง notification แต่ละช่องทาง
     */
    public function notifyNewBooking(ServiceBooking $booking, ServiceProvider $provider): array
    {
        $results = [];

        // 1. LINE OA — 🚫 ปิดไว้เป็นค่าเริ่มต้น (2026-10-16): ตัวนี้ยิง LINE push API ตรงๆ กินโควต้า 300/เดือน
        //    (.claude/LINE_MESSAGING_RULES.md) · ผู้ให้บริการได้ push เข้าแอปจากข้อ 3 อยู่แล้ว
        //    เปิดกลับได้ด้วย Setting `service_booking_line_push_enabled` = true เท่านั้น
        if ($this->linePushEnabled() && $provider->hasLineConnected() && $provider->isLineNotificationEnabled()) {
            $results['line'] = $this->sendLineNotification($booking, $provider, 'new_booking');
        }

        // 2. In-App Notification
        $results['in_app'] = $this->sendInAppNotification($booking, $provider, 'new_booking');

        // 3. 🔔 push เข้ามือถือผู้ให้บริการ (ผ่าน NotificationService → SendNotificationPush)
        $results['push'] = $this->pushToProvider($booking, $provider, 'new_booking');

        // 3. Email (ถ้าต้องการ)
        if ($provider->email_notifications ?? false) {
            $results['email'] = $this->sendEmailNotification($booking, $provider, 'new_booking');
        }

        // 4. SMS (ถ้าต้องการ)
        if ($provider->sms_notifications ?? false) {
            $results['sms'] = $this->sendSmsNotification($booking, $provider, 'new_booking');
        }

        return $results;
    }

    /**
     * สวิตช์ส่ง LINE push ให้ผู้ให้บริการ (ค่าเริ่มต้นปิด — โควต้า push LINE มีแค่ 300 ครั้ง/เดือน)
     */
    public function linePushEnabled(): bool
    {
        try {
            return (bool) Setting::get(self::LINE_PUSH_SETTING, false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ส่ง LINE Notification
     */
    public function sendLineNotification(ServiceBooking $booking, ServiceProvider $provider, string $type): array
    {
        if (! $provider->line_user_id) {
            return $this->createFailedNotification($booking, $provider, 'line', $type, 'ไม่มี LINE User ID');
        }

        try {
            // สร้างข้อความตามประเภท
            $message = $this->buildLineMessage($booking, $type);

            // ส่งผ่าน LINE Messaging API
            $response = $this->sendLineMessage($provider->line_user_id, $message);

            // บันทึก notification
            $notification = $this->createNotification($booking, $provider, 'line', $type, $message);
            $notification->markAsSent();

            return [
                'success' => true,
                'channel' => 'line',
                'notification_id' => $notification->id,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('LINE Notification Error', [
                'booking_id' => $booking->id,
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            $notification = $this->createNotification($booking, $provider, 'line', $type, $message ?? '');
            $notification->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'channel' => 'line',
                'error' => $e->getMessage(),
                'notification_id' => $notification->id,
            ];
        }
    }

    /**
     * สร้างข้อความ LINE
     */
    protected function buildLineMessage(ServiceBooking $booking, string $type): array
    {
        $messages = [
            'new_booking' => [
                'type' => 'flex',
                'altText' => '🔔 มีงานใหม่! งานจอง #'.$booking->booking_number,
                'contents' => [
                    'type' => 'bubble',
                    'header' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '🔔 งานใหม่!',
                                'weight' => 'bold',
                                'size' => 'xl',
                                'color' => '#FF6B6B',
                            ],
                        ],
                    ],
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => $booking->service->name ?? 'บริการ',
                                'weight' => 'bold',
                                'size' => 'lg',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'margin' => 'md',
                                'contents' => [
                                    ['type' => 'text', 'text' => '📅', 'flex' => 0],
                                    ['type' => 'text', 'text' => $booking->scheduled_at->format('d/m/Y H:i'), 'margin' => 'sm'],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'contents' => [
                                    ['type' => 'text', 'text' => '💰', 'flex' => 0],
                                    ['type' => 'text', 'text' => number_format($booking->total_amount, 2).' ฿', 'margin' => 'sm'],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'contents' => [
                                    ['type' => 'text', 'text' => '📍', 'flex' => 0],
                                    ['type' => 'text', 'text' => number_format($booking->distance_km, 1).' km', 'margin' => 'sm'],
                                ],
                            ],
                        ],
                    ],
                    'footer' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'spacing' => 'sm',
                        'contents' => [
                            [
                                'type' => 'button',
                                'style' => 'primary',
                                'color' => '#4CAF50',
                                'action' => [
                                    'type' => 'uri',
                                    'label' => '✅ รับงาน',
                                    'uri' => route('provider.bookings.accept', $booking->id),
                                ],
                            ],
                            [
                                'type' => 'button',
                                'style' => 'secondary',
                                'action' => [
                                    'type' => 'uri',
                                    'label' => '❌ ปฏิเสธ',
                                    'uri' => route('provider.bookings.reject', $booking->id),
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'booking_accepted' => [
                'type' => 'text',
                'text' => "✅ ผู้ให้บริการรับงานแล้ว\n\nเลขที่จอง: {$booking->booking_number}\nผู้ให้บริการ: {$booking->provider->name}\nเวลานัดหมาย: {$booking->scheduled_at->format('d/m/Y H:i')}",
            ],

            'booking_rejected' => [
                'type' => 'text',
                'text' => "❌ ผู้ให้บริการปฏิเสธงาน\n\nเลขที่จอง: {$booking->booking_number}\nเหตุผล: {$booking->provider_rejected_reason}",
            ],

            'provider_on_way' => [
                'type' => 'text',
                'text' => "🚗 ผู้ให้บริการกำลังเดินทาง\n\nเลขที่จอง: {$booking->booking_number}\nผู้ให้บริการ: {$booking->provider->name}",
            ],

            'service_completed' => [
                'type' => 'text',
                'text' => "✨ บริการเสร็จสิ้น\n\nเลขที่จอง: {$booking->booking_number}\n\nกรุณาให้คะแนนและรีวิวบริการ",
            ],
        ];

        return $messages[$type] ?? [
            'type' => 'text',
            'text' => "การแจ้งเตือนจากระบบจองบริการ\nเลขที่จอง: {$booking->booking_number}",
        ];
    }

    /**
     * ส่งข้อความผ่าน LINE Messaging API
     */
    protected function sendLineMessage(string $lineUserId, array $message): array
    {
        $channelAccessToken = config('services.line.channel_access_token');

        if (! $channelAccessToken) {
            throw new \Exception('LINE Channel Access Token ไม่ถูกตั้งค่า');
        }

        $response = Http::withToken($channelAccessToken)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $lineUserId,
                'messages' => [$message],
            ]);

        if (! $response->successful()) {
            throw new \Exception('LINE API Error: '.$response->body());
        }

        return $response->json();
    }

    /**
     * ส่ง In-App Notification
     */
    protected function sendInAppNotification(ServiceBooking $booking, ServiceProvider $provider, string $type): array
    {
        $titles = [
            'new_booking' => '🔔 มีงานใหม่!',
            'booking_accepted' => '✅ ผู้ให้บริการรับงานแล้ว',
            'booking_rejected' => '❌ ผู้ให้บริการปฏิเสธงาน',
            'provider_on_way' => '🚗 ผู้ให้บริการกำลังเดินทาง',
            'service_completed' => '✨ บริการเสร็จสิ้น',
        ];

        $messages = [
            'new_booking' => "งานจอง #{$booking->booking_number} - {$booking->service->name}",
            'booking_accepted' => "เลขที่จอง: {$booking->booking_number}",
            'booking_rejected' => "เลขที่จอง: {$booking->booking_number}",
            'provider_on_way' => "เลขที่จอง: {$booking->booking_number}",
            'service_completed' => "เลขที่จอง: {$booking->booking_number}",
        ];

        $notification = $this->createNotification(
            $booking,
            $provider,
            'in_app',
            $type,
            $messages[$type],
            $titles[$type]
        );

        $notification->markAsSent();

        return [
            'success' => true,
            'channel' => 'in_app',
            'notification_id' => $notification->id,
        ];
    }

    /**
     * ส่ง Email Notification
     */
    protected function sendEmailNotification(ServiceBooking $booking, ServiceProvider $provider, string $type): array
    {
        try {
            // TODO: สร้าง Mailable class สำหรับแต่ละ type
            // Mail::to($provider->email)->send(new NewBookingNotification($booking));

            $notification = $this->createNotification(
                $booking,
                $provider,
                'email',
                $type,
                "งานจอง #{$booking->booking_number}",
                'มีงานใหม่รอการยืนยัน'
            );

            $notification->markAsSent();

            return [
                'success' => true,
                'channel' => 'email',
                'notification_id' => $notification->id,
            ];
        } catch (\Exception $e) {
            Log::error('Email Notification Error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'channel' => 'email',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ส่ง SMS Notification
     */
    protected function sendSmsNotification(ServiceBooking $booking, ServiceProvider $provider, string $type): array
    {
        // TODO: Implement SMS sending via SMS gateway
        $notification = $this->createNotification(
            $booking,
            $provider,
            'sms',
            $type,
            "มีงานใหม่ #{$booking->booking_number}"
        );

        $notification->markAsSent();

        return [
            'success' => true,
            'channel' => 'sms',
            'notification_id' => $notification->id,
        ];
    }

    /**
     * 🔔 (2026-10-16) แจ้งเตือน + push เข้ามือถือ "ลูกค้า" ตามสถานะการจอง
     *
     * ผ่าน NotificationService::create เท่านั้น (ทางเดียวที่ push ออก) · ❌ ไม่ใช้ LINE push (โควต้า 300/เดือน)
     * ส่งไม่ได้ต้องไม่ทำให้การจอง/การคืนเงินล้ม → กลืน error แล้ว log
     *
     * @param  string  $event  booking_accepted | booking_rejected | provider_on_way | service_completed | booking_cancelled
     */
    public function pushToCustomer(ServiceBooking $booking, string $event): bool
    {
        $user = $booking->user;
        if (! $user) {
            return false;
        }

        $number = $booking->booking_number;
        $serviceName = $booking->service->name ?? 'บริการ';
        $providerName = $booking->provider->name ?? 'ผู้ให้บริการ';

        [$title, $message, $priority] = match ($event) {
            'booking_accepted' => ['✅ ผู้ให้บริการรับงานแล้ว', "{$providerName} รับงาน {$serviceName} แล้ว · เลขที่จอง {$number}", 'high'],
            'booking_rejected' => ['ผู้ให้บริการไม่สะดวกรับงาน', "การจอง {$number} กำลังหาผู้ให้บริการใหม่ให้ หรือยกเลิกได้ในแอป", 'high'],
            'provider_on_way' => ['🚗 ผู้ให้บริการกำลังเดินทาง', "{$providerName} กำลังไปหาคุณ · เลขที่จอง {$number}", 'high'],
            'service_completed' => ['✨ บริการเสร็จสิ้น', "ขอบคุณที่ใช้บริการ {$serviceName} · ให้คะแนนผู้ให้บริการได้ในแอป", 'normal'],
            'booking_cancelled' => ['❌ การจองถูกยกเลิก', $booking->payment_status === 'refunded'
                ? "การจอง {$number} ถูกยกเลิก · คืนเงินเข้ากระเป๋าแล้ว"
                : "การจอง {$number} ถูกยกเลิก", 'high'],
            default => ['แจ้งเตือนการจองบริการ', "เลขที่จอง {$number}", 'normal'],
        };

        return $this->pushTo($user, 'service_booking_'.$event, $title, $message, $priority, $booking, 'user.bookings.show');
    }

    /**
     * 🔔 (2026-10-16) แจ้งเตือน + push เข้ามือถือ "ผู้ให้บริการ"
     *
     * @param  string  $event  new_booking | booking_cancelled
     */
    public function pushToProvider(ServiceBooking $booking, ServiceProvider $provider, string $event): bool
    {
        $user = $provider->user;
        if (! $user) {
            return false;
        }

        $number = $booking->booking_number;
        $serviceName = $booking->service->name ?? 'บริการ';

        [$title, $message, $priority] = match ($event) {
            'new_booking' => ['🔔 มีงานจองใหม่!', "{$serviceName} · ".number_format((float) $booking->total_amount, 0).' บาท · กดรับงานในแอป', 'urgent'],
            'booking_cancelled' => ['❌ ลูกค้ายกเลิกการจอง', "การจอง {$number} ({$serviceName}) ถูกยกเลิกแล้ว", 'high'],
            default => ['แจ้งเตือนงานบริการ', "เลขที่จอง {$number}", 'normal'],
        };

        return $this->pushTo($user, 'service_booking_provider_'.$event, $title, $message, $priority, $booking, 'provider.bookings.show');
    }

    /**
     * สร้างแจ้งเตือนในระบบ (→ กล่องแจ้งเตือนแอป + push) แบบไม่ทำให้ธุรกรรมต้นทางล้ม
     */
    protected function pushTo(
        \App\Models\User $user,
        string $type,
        string $title,
        string $message,
        string $priority,
        ServiceBooking $booking,
        string $routeName
    ): bool {
        try {
            $url = null;
            try {
                $url = route($routeName, $booking->id);
            } catch (\Throwable) {
                // ไม่มี route → แจ้งเตือนแบบไม่มีลิงก์
            }

            app(NotificationService::class)->create(
                $user,
                $type,
                $title,
                $message,
                [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'channel' => in_array($priority, ['high', 'urgent'], true) ? 'important' : 'orders',
                ],
                $url,
                'ดูการจอง',
                $priority
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('ServiceBooking push failed', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * สร้าง Notification record
     *
     * @param  string|array  $message  ข้อความ LINE เป็น message object (array) → เก็บเป็น JSON
     *                                 (เดิมรับแค่ string → ทาง LINE โยน TypeError ทำให้แจ้งงานจองล้มทั้งก้อน)
     */
    protected function createNotification(
        ServiceBooking $booking,
        ServiceProvider $provider,
        string $channel,
        string $type,
        string|array $message,
        ?string $title = null
    ): ServiceBookingNotification {
        return ServiceBookingNotification::create([
            'booking_id' => $booking->id,
            'provider_id' => $provider->id,
            'user_id' => $booking->user_id,
            'notification_type' => $type,
            'channel' => $channel,
            'recipient' => $this->getRecipient($provider, $channel),
            'title' => $title,
            'message' => is_array($message) ? json_encode($message) : $message,
            'data' => [
                'booking_number' => $booking->booking_number,
                'scheduled_at' => $booking->scheduled_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * สร้าง Failed Notification record
     */
    protected function createFailedNotification(
        ServiceBooking $booking,
        ServiceProvider $provider,
        string $channel,
        string $type,
        string $reason
    ): array {
        $notification = $this->createNotification($booking, $provider, $channel, $type, '');
        $notification->markAsFailed($reason);

        return [
            'success' => false,
            'channel' => $channel,
            'error' => $reason,
            'notification_id' => $notification->id,
        ];
    }

    /**
     * หาผู้รับสำหรับแต่ละช่องทาง
     */
    protected function getRecipient(ServiceProvider $provider, string $channel): string
    {
        return match ($channel) {
            'line' => $provider->line_user_id ?? '',
            'email' => $provider->user->email ?? '',
            'sms' => $provider->user->phone ?? '',
            'in_app' => "provider_{$provider->id}",
            default => '',
        };
    }

    /**
     * Retry ส่ง notification ที่ล้มเหลว
     *
     * @return int จำนวน notification ที่ส่งซ้ำ
     */
    public function retryFailedNotifications(): int
    {
        $failedNotifications = ServiceBookingNotification::pendingRetry()->get();
        $retried = 0;

        foreach ($failedNotifications as $notification) {
            $notification->incrementRetry();

            // TODO: ลองส่งซ้ำตาม channel
            $retried++;
        }

        return $retried;
    }
}
