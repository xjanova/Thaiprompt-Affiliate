<?php

namespace App\Listeners;

use App\Events\SoftwarePurchased;
use App\Mail\SoftwarePurchasedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

/**
 * SendSoftwarePurchaseEmail Listener
 *
 * ส่งอีเมลแจ้งเตือนเมื่อซื้อซอฟต์แวร์สำเร็จ
 */
class SendSoftwarePurchaseEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * จัดการ event
     */
    public function handle(SoftwarePurchased $event): void
    {
        try {
            // รอให้ GenerateSoftwareLicense ทำงานเสร็จก่อน
            $license = $event->options['generated_license'] ?? null;

            // ถ้าไม่มี license ให้ข้าม (อาจเป็นสินค้าที่ไม่ต้องใช้ license)
            if (! $license) {
                \Log::warning('No license found when sending purchase email', [
                    'product_id' => $event->product->id,
                    'buyer_id' => $event->buyer->id,
                ]);

                return;
            }

            // ส่งอีเมล
            Mail::to($event->buyer->email)
                ->send(new SoftwarePurchasedMail(
                    $event->buyer,
                    $event->product,
                    $license,
                    $event->order
                ));

            // Log การส่งอีเมล
            \Log::info('Software purchase email sent', [
                'email' => $event->buyer->email,
                'product_id' => $event->product->id,
                'license_id' => $license->id,
            ]);
        } catch (\Exception $e) {
            // Log error แต่ไม่ throw เพราะอีเมลไม่สำคัญขนาดนั้น
            \Log::error('Failed to send software purchase email', [
                'error' => $e->getMessage(),
                'product_id' => $event->product->id,
                'buyer_id' => $event->buyer->id,
            ]);

            // Re-throw เพื่อให้ queue retry
            throw $e;
        }
    }

    /**
     * กำหนดจำนวนครั้งที่ job จะ retry หาก fail
     */
    public function retries(): int
    {
        return 3;
    }

    /**
     * กำหนดเวลาที่รอก่อน retry (วินาที)
     */
    public function backoff(): int
    {
        return 30;
    }
}
