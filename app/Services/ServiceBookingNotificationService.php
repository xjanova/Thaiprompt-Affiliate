<?php

namespace App\Services;

use App\Models\ServiceBooking;
use App\Models\ServiceBookingNotification;
use App\Models\ServiceProvider;
use App\Models\User;
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
    /**
     * ส่งการแจ้งเตือนงานใหม่ถึง Provider
     *
     * @param ServiceBooking $booking
     * @param ServiceProvider $provider
     * @return array ผลลัพธ์การส่ง notification แต่ละช่องทาง
     */
    public function notifyNewBooking(ServiceBooking $booking, ServiceProvider $provider): array
    {
        $results = [];

        // 1. LINE OA (ลำดับความสำคัญสูงสุด)
        if ($provider->hasLineConnected() && $provider->isLineNotificationEnabled()) {
            $results['line'] = $this->sendLineNotification($booking, $provider, 'new_booking');
        }

        // 2. In-App Notification
        $results['in_app'] = $this->sendInAppNotification($booking, $provider, 'new_booking');

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
     * ส่ง LINE Notification
     *
     * @param ServiceBooking $booking
     * @param ServiceProvider $provider
     * @param string $type
     * @return array
     */
    public function sendLineNotification(ServiceBooking $booking, ServiceProvider $provider, string $type): array
    {
        if (!$provider->line_user_id) {
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
     *
     * @param ServiceBooking $booking
     * @param string $type
     * @return array
     */
    protected function buildLineMessage(ServiceBooking $booking, string $type): array
    {
        $messages = [
            'new_booking' => [
                'type' => 'flex',
                'altText' => '🔔 มีงานใหม่! งานจอง #' . $booking->booking_number,
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
                                    ['type' => 'text', 'text' => number_format($booking->total_amount, 2) . ' ฿', 'margin' => 'sm'],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'contents' => [
                                    ['type' => 'text', 'text' => '📍', 'flex' => 0],
                                    ['type' => 'text', 'text' => number_format($booking->distance_km, 1) . ' km', 'margin' => 'sm'],
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
     *
     * @param string $lineUserId
     * @param array $message
     * @return array
     */
    protected function sendLineMessage(string $lineUserId, array $message): array
    {
        $channelAccessToken = config('services.line.channel_access_token');

        if (!$channelAccessToken) {
            throw new \Exception('LINE Channel Access Token ไม่ถูกตั้งค่า');
        }

        $response = Http::withToken($channelAccessToken)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $lineUserId,
                'messages' => [$message],
            ]);

        if (!$response->successful()) {
            throw new \Exception('LINE API Error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * ส่ง In-App Notification
     *
     * @param ServiceBooking $booking
     * @param ServiceProvider $provider
     * @param string $type
     * @return array
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
     *
     * @param ServiceBooking $booking
     * @param ServiceProvider $provider
     * @param string $type
     * @return array
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
     *
     * @param ServiceBooking $booking
     * @param ServiceProvider $provider
     * @param string $type
     * @return array
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
     * สร้าง Notification record
     *
     * @param ServiceBooking $booking
     * @param ServiceProvider $provider
     * @param string $channel
     * @param string $type
     * @param string $message
     * @param string|null $title
     * @return ServiceBookingNotification
     */
    protected function createNotification(
        ServiceBooking $booking,
        ServiceProvider $provider,
        string $channel,
        string $type,
        string $message,
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
