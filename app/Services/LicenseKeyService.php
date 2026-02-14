<?php

namespace App\Services;

use App\Models\SoftwareDownload;
use App\Models\SoftwareLicense;
use App\Models\SoftwareProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * LicenseKeyService
 *
 * จัดการ License Keys และการดาวน์โหลด
 */
class LicenseKeyService
{
    /**
     * สร้าง License Key ใหม่
     *
     *
     * @throws \Exception
     */
    public function generateLicense(
        SoftwareProduct $product,
        User $user,
        ?int $orderId = null,
        array $options = []
    ): SoftwareLicense {
        return DB::transaction(function () use ($product, $user, $orderId, $options) {
            // สร้าง License Key
            $licenseKey = $this->generateLicenseKey();

            // สร้าง Activation Code (optional)
            $activationCode = $options['generate_activation_code'] ?? false
                ? $this->generateActivationCode()
                : null;

            // สร้าง License
            $license = SoftwareLicense::create([
                'software_product_id' => $product->id,
                'user_id' => $user->id,
                'order_id' => $orderId,
                'license_key' => $licenseKey,
                'activation_code' => $activationCode,
                'status' => SoftwareLicense::STATUS_ACTIVE,
                'is_activated' => false,
                'expires_at' => $options['expires_at'] ?? null,
                'max_activations' => $options['max_activations'] ?? 1,
                'max_downloads' => $options['max_downloads'] ?? -1, // -1 = unlimited
                'metadata' => $options['metadata'] ?? null,
                'notes' => $options['notes'] ?? null,
            ]);

            // Log activity
            activity()
                ->performedOn($license)
                ->causedBy($user)
                ->log('สร้าง License Key');

            return $license;
        });
    }

    /**
     * สร้าง Download Token แบบ One-Time/Temporary
     *
     *
     * @throws \Exception
     */
    public function createDownloadToken(
        SoftwareLicense $license,
        int $expirationMinutes = 60
    ): SoftwareDownload {
        // ตรวจสอบว่า license ยังใช้งานได้
        if (! $license->isValid()) {
            throw new \Exception('License is not valid');
        }

        // ตรวจสอบว่ายังดาวน์โหลดได้หรือไม่
        if (! $license->canDownload()) {
            throw new \Exception('Download limit reached');
        }

        // สร้าง Download Token
        $token = $this->generateDownloadToken();
        $expiresAt = now()->addMinutes($expirationMinutes);

        // เก็บข้อมูลจาก Request
        $userAgent = request()->userAgent();
        $ipAddress = request()->ip();

        // Parse User Agent
        $agent = new \Jenssegers\Agent\Agent;
        $agent->setUserAgent($userAgent);

        $download = SoftwareDownload::create([
            'software_product_id' => $license->software_product_id,
            'software_license_id' => $license->id,
            'user_id' => $license->user_id,
            'download_token' => $token,
            'token_expires_at' => $expiresAt,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->browser(),
            'os' => $agent->platform(),
            'status' => SoftwareDownload::STATUS_PENDING,
        ]);

        return $download;
    }

    /**
     * ตรวจสอบ Download Token และให้สิทธิ์ดาวน์โหลด
     *
     *
     * @throws \Exception
     */
    public function validateDownloadToken(string $token): array
    {
        $download = SoftwareDownload::where('download_token', $token)->first();

        if (! $download) {
            throw new \Exception('Invalid download token');
        }

        if (! $download->isTokenValid()) {
            throw new \Exception('Download token has expired or already used');
        }

        // ตรวจสอบ License
        $license = $download->license;
        if (! $license || ! $license->isValid()) {
            throw new \Exception('License is not valid');
        }

        // Mark token as used
        $download->markTokenUsed();

        return [
            'download' => $download,
            'license' => $license,
            'product' => $download->product,
        ];
    }

    /**
     * บันทึกการดาวน์โหลดเสร็จสิ้น
     */
    public function completeDownload(SoftwareDownload $download): void
    {
        DB::transaction(function () use ($download) {
            // คำนวณระยะเวลาดาวน์โหลด
            $duration = $download->started_at
                ? now()->diffInSeconds($download->started_at)
                : null;

            // Mark download as completed
            $download->markCompleted($duration);

            // เพิ่มจำนวนดาวน์โหลดใน License
            if ($download->license) {
                $download->license->incrementDownloadCount();
            }

            // Log activity
            activity()
                ->performedOn($download)
                ->causedBy($download->user)
                ->log('ดาวน์โหลดซอฟต์แวร์');
        });
    }

    /**
     * สร้าง License Key (UUID format)
     */
    protected function generateLicenseKey(): string
    {
        do {
            // สร้าง UUID
            $key = strtoupper(Str::uuid()->toString());

            // เช็คว่า key ซ้ำหรือไม่
            $exists = SoftwareLicense::where('license_key', $key)->exists();
        } while ($exists);

        return $key;
    }

    /**
     * สร้าง Activation Code (8 characters)
     */
    protected function generateActivationCode(): string
    {
        do {
            // สร้าง code 8 ตัว (ตัวพิมพ์ใหญ่ + ตัวเลข)
            $code = strtoupper(Str::random(8));

            // เช็คว่า code ซ้ำหรือไม่
            $exists = SoftwareLicense::where('activation_code', $code)->exists();
        } while ($exists);

        return $code;
    }

    /**
     * สร้าง Download Token (64 characters)
     */
    protected function generateDownloadToken(): string
    {
        do {
            // สร้าง token แบบ cryptographically secure
            $token = bin2hex(random_bytes(32)); // 64 hex characters

            // เช็คว่า token ซ้ำหรือไม่
            $exists = SoftwareDownload::where('download_token', $token)->exists();
        } while ($exists);

        return $token;
    }

    /**
     * ยกเลิก License
     */
    public function revokeLicense(SoftwareLicense $license, User $revokedBy, string $reason): void
    {
        DB::transaction(function () use ($license, $revokedBy, $reason) {
            $license->revoke($revokedBy->id, $reason);

            // Log activity
            activity()
                ->performedOn($license)
                ->causedBy($revokedBy)
                ->withProperties(['reason' => $reason])
                ->log('ยกเลิก License');
        });
    }

    /**
     * ต่ออายุ License
     */
    public function renewLicense(SoftwareLicense $license, \Carbon\Carbon $newExpiryDate): void
    {
        DB::transaction(function () use ($license, $newExpiryDate) {
            $license->update([
                'expires_at' => $newExpiryDate,
                'status' => SoftwareLicense::STATUS_ACTIVE,
            ]);

            // Log activity
            activity()
                ->performedOn($license)
                ->withProperties(['new_expiry' => $newExpiryDate])
                ->log('ต่ออายุ License');
        });
    }

    /**
     * ดึงสถิติการดาวน์โหลด
     */
    public function getDownloadStats(SoftwareProduct $product): array
    {
        return [
            'total_downloads' => SoftwareDownload::where('software_product_id', $product->id)
                ->completed()
                ->count(),
            'today_downloads' => SoftwareDownload::where('software_product_id', $product->id)
                ->completed()
                ->today()
                ->count(),
            'total_licenses' => SoftwareLicense::where('software_product_id', $product->id)->count(),
            'active_licenses' => SoftwareLicense::where('software_product_id', $product->id)
                ->active()
                ->count(),
        ];
    }

    /**
     * ตรวจสอบ License ที่หมดอายุและอัพเดทสถานะ
     *
     * @return int จำนวน License ที่อัพเดท
     */
    public function checkExpiredLicenses(): int
    {
        $expiredLicenses = SoftwareLicense::where('status', SoftwareLicense::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredLicenses as $license) {
            $license->checkExpiration();
        }

        return $expiredLicenses->count();
    }
}
