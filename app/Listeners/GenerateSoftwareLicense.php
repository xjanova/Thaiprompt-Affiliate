<?php

namespace App\Listeners;

use App\Events\SoftwarePurchased;
use App\Services\LicenseKeyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * GenerateSoftwareLicense Listener
 *
 * สร้าง License Key เมื่อมีการซื้อซอฟต์แวร์
 */
class GenerateSoftwareLicense implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var LicenseKeyService
     */
    protected $licenseKeyService;

    /**
     * สร้าง listener instance
     */
    public function __construct(LicenseKeyService $licenseKeyService)
    {
        $this->licenseKeyService = $licenseKeyService;
    }

    /**
     * จัดการ event
     */
    public function handle(SoftwarePurchased $event): void
    {
        try {
            // ข้อมูล product ต้องมี license
            if (! $event->product->requires_license) {
                return;
            }

            // ดึง license options จาก event options
            $licenseOptions = $event->options['license'] ?? [];

            // กำหนด default options
            $licenseOptions = array_merge([
                'max_activations' => 1,
                'max_downloads' => -1, // unlimited
                'expires_at' => null, // ไม่มีวันหมดอายุ
                'generate_activation_code' => false,
                'metadata' => [
                    'purchase_date' => now()->toIso8601String(),
                    'product_name' => $event->product->name,
                    'product_version' => $event->product->version,
                ],
            ], $licenseOptions);

            // สร้าง license
            $license = $this->licenseKeyService->generateLicense(
                $event->product,
                $event->buyer,
                $event->order?->id,
                $licenseOptions
            );

            // เก็บ license ใน event options เพื่อให้ listeners อื่นใช้งาน
            $event->options['generated_license'] = $license;

            // Log การสร้าง license
            \Log::info('Software license generated', [
                'license_id' => $license->id,
                'license_key' => $license->license_key,
                'product_id' => $event->product->id,
                'buyer_id' => $event->buyer->id,
                'order_id' => $event->order?->id,
            ]);
        } catch (\Exception $e) {
            // Log error แต่ไม่ throw เพื่อไม่ให้ block listeners อื่น
            \Log::error('Failed to generate software license', [
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
        return 10;
    }
}
