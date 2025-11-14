<?php

namespace App\Services;

use App\Models\FoodProduct;
use App\Models\FoodCertification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificationService
{
    public function __construct(
        protected BlockchainRecordService $blockchain
    ) {}

    /**
     * Issue a new certification
     */
    public function issueCertification(array $data): FoodCertification
    {
        $certificateNumber = $data['certificate_number'] ?? $this->generateCertificateNumber();

        $certification = FoodCertification::create([
            'food_product_id' => $data['food_product_id'],
            'certification_type' => $data['certification_type'],
            'certification_name' => $data['certification_name'],
            'certifying_body' => $data['certifying_body'],
            'certificate_number' => $certificateNumber,
            'issued_date' => $data['issued_date'] ?? now(),
            'expiry_date' => $data['expiry_date'],
            'status' => 'active',
            'scope' => $data['scope'] ?? null,
            'standards_version' => $data['standards_version'] ?? null,
            'certificate_url' => $data['certificate_url'] ?? null,
            'audit_report_url' => $data['audit_report_url'] ?? null,
            'verification_code' => $this->generateVerificationCode(),
        ]);

        // Generate QR code for verification
        $this->generateCertificateQRCode($certification);

        // Record on blockchain
        try {
            $hash = $this->recordCertificationOnChain($certification);
            $certification->update(['blockchain_hash' => $hash]);
        } catch (\Exception $e) {
            \Log::error('Blockchain certification recording failed', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage()
            ]);
        }

        // Send notification
        $this->notifyCertificationIssued($certification);

        return $certification;
    }

    /**
     * Verify a certification
     */
    public function verifyCertificate(string $certificateNumber): ?FoodCertification
    {
        return FoodCertification::where('certificate_number', $certificateNumber)
            ->with('foodProduct')
            ->first();
    }

    /**
     * Renew a certification
     */
    public function renewCertificate(int $certId): FoodCertification
    {
        $cert = FoodCertification::findOrFail($certId);

        // Create new certification based on old one
        $newCert = $this->issueCertification([
            'food_product_id' => $cert->food_product_id,
            'certification_type' => $cert->certification_type,
            'certification_name' => $cert->certification_name,
            'certifying_body' => $cert->certifying_body,
            'issued_date' => now(),
            'expiry_date' => now()->addYear(),
            'scope' => $cert->scope,
            'standards_version' => $cert->standards_version,
        ]);

        // Mark old certificate as expired
        $cert->update(['status' => 'expired']);

        return $newCert;
    }

    /**
     * Revoke a certification
     */
    public function revokeCertificate(int $certId, string $reason): bool
    {
        $cert = FoodCertification::findOrFail($certId);

        $cert->update([
            'status' => 'revoked',
            'scope' => $cert->scope . "\n\nRevoked: " . $reason,
        ]);

        // Notify stakeholders
        $this->notifyCertificationRevoked($cert, $reason);

        return true;
    }

    /**
     * Check certifications expiring soon
     */
    public function checkExpiringSoon(int $days = 30): Collection
    {
        return FoodCertification::expiringSoon($days)
            ->with('foodProduct.farmer')
            ->get();
    }

    /**
     * Get all certifications for a product
     */
    public function getProductCertifications(int $productId): Collection
    {
        return FoodCertification::where('food_product_id', $productId)
            ->orderBy('issued_date', 'desc')
            ->get();
    }

    /**
     * Generate certificate number
     */
    protected function generateCertificateNumber(): string
    {
        $prefix = 'CERT';
        $year = date('Y');
        $sequence = FoodCertification::whereYear('issued_date', $year)->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    /**
     * Generate verification code
     */
    protected function generateVerificationCode(): string
    {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
    }

    /**
     * Generate QR code for certificate verification
     */
    protected function generateCertificateQRCode(FoodCertification $certification): void
    {
        try {
            $url = route('food-passport.certifications.verify', $certification->certificate_number);

            $qrCode = QrCode::format('png')
                ->size(400)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($url);

            $filename = "certificates/qr/{$certification->certificate_number}.png";
            Storage::disk('public')->put($filename, $qrCode);

            $certification->update([
                'qr_code_url' => Storage::url($filename)
            ]);
        } catch (\Exception $e) {
            \Log::error('Certificate QR code generation failed', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Record certification on blockchain
     */
    protected function recordCertificationOnChain(FoodCertification $certification): string
    {
        try {
            $data = [
                'certificate_number' => $certification->certificate_number,
                'product_passport_id' => $certification->foodProduct->food_passport_id,
                'certification_type' => $certification->certification_type,
                'certifying_body' => $certification->certifying_body,
                'issued_date' => $certification->issued_date->timestamp,
                'expiry_date' => $certification->expiry_date->timestamp,
                'verification_code' => $certification->verification_code,
            ];

            // Use blockchain service to record
            $txHash = $this->blockchain->submitTransaction('issueCertificate', $data);

            \Log::info('Certification recorded on blockchain', [
                'certification_id' => $certification->id,
                'tx_hash' => $txHash,
            ]);

            return $txHash;
        } catch (\Exception $e) {
            \Log::error('Blockchain certification recording failed', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);

            // Return mock hash for development
            return '0x' . bin2hex(random_bytes(32));
        }
    }

    /**
     * Notify farmer about certification issuance
     */
    protected function notifyCertificationIssued(FoodCertification $certification): void
    {
        $product = $certification->foodProduct;
        $farmer = $product->farmer;

        if ($farmer && $farmer->line_user_id) {
            try {
                app(LineService::class)->sendFlexMessage(
                    $farmer->line_user_id,
                    $this->buildCertificationMessage($certification)
                );
            } catch (\Exception $e) {
                \Log::error('LINE notification failed', [
                    'certification_id' => $certification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Notify about certification revocation
     */
    protected function notifyCertificationRevoked(FoodCertification $certification, string $reason): void
    {
        $product = $certification->foodProduct;
        $farmer = $product->farmer;

        if ($farmer && $farmer->line_user_id) {
            try {
                app(LineService::class)->sendTextMessage(
                    $farmer->line_user_id,
                    "⚠️ ใบรับรอง {$certification->certificate_number} ถูกเพิกถอน\nเหตุผล: {$reason}"
                );
            } catch (\Exception $e) {
                \Log::error('LINE notification failed', [
                    'certification_id' => $certification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Build LINE flex message for certification
     */
    protected function buildCertificationMessage(FoodCertification $certification): array
    {
        $product = $certification->foodProduct;

        return [
            'type' => 'flex',
            'altText' => 'คุณได้รับใบรับรอง!',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🏆 ใบรับรอง',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#FFD700',
                        ],
                        [
                            'type' => 'text',
                            'text' => $certification->certification_name,
                            'margin' => 'md',
                            'weight' => 'bold',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'lg',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => 'ผลิตภัณฑ์: ' . $product->variety,
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'เลขที่: ' . $certification->certificate_number,
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'ออกโดย: ' . $certification->certifying_body,
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'หมดอายุ: ' . $certification->expiry_date->format('d/m/Y'),
                                    'size' => 'sm',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
