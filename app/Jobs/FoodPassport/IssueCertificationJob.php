<?php

namespace App\Jobs\FoodPassport;

use App\Models\FoodCertification;
use App\Models\QualityCheckpoint;
use App\Services\TpixBlockchainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Issue Certification Job
 *
 * Issues quality certification based on checkpoint results and mints NFT certificate
 */
class IssueCertificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public QualityCheckpoint $checkpoint
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TpixBlockchainService $blockchain): void
    {
        Log::info('Issuing certification for quality checkpoint', [
            'checkpoint_id' => $this->checkpoint->id,
        ]);

        $product = $this->checkpoint->foodProduct;
        if (!$product) {
            Log::error('Cannot issue certification: Product not found');
            return;
        }

        // Determine certification type and standard
        $certificationType = $this->determineCertificationType();
        $certificationStandard = $this->determineCertificationStandard();

        // Generate unique certificate number
        $certificateNumber = $this->generateCertificateNumber();

        // Create certification record
        $certification = FoodCertification::create([
            'food_product_id' => $product->id,
            'certificate_number' => $certificateNumber,
            'certification_type' => $certificationType,
            'certification_standard' => $certificationStandard,
            'quality_checkpoint_id' => $this->checkpoint->id,
            'issued_by' => $this->checkpoint->inspector_name ?? 'System Auto-Issued',
            'issued_date' => now(),
            'expiry_date' => now()->addYear(),
            'status' => 'valid',
            'test_results' => $this->checkpoint->test_parameters,
            'quality_score' => $this->checkpoint->pass_score,
            'verified' => true,
        ]);

        Log::info('Certification created', [
            'certification_id' => $certification->id,
            'certificate_number' => $certificateNumber,
        ]);

        // Mint NFT certificate on TPIX blockchain
        try {
            $nftResult = $blockchain->mintQualityCertificateNFT($certification, $this->checkpoint);

            $certification->update([
                'nft_token_id' => $nftResult['token_id'],
                'blockchain_hash' => $nftResult['tx_hash'],
                'ipfs_hash' => $nftResult['ipfs_hash'],
            ]);

            Log::info('Certification NFT minted successfully', [
                'token_id' => $nftResult['token_id'],
                'tx_hash' => $nftResult['tx_hash'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mint certification NFT', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);
            // Continue without NFT - certification is still valid
        }

        // Mark checkpoint as having issued certification
        $this->checkpoint->update([
            'certification_issued' => true,
            'certification_number' => $certificateNumber,
        ]);

        // Update product certification status
        $product->update([
            'certification_status' => 'certified',
        ]);

        Log::info('Certification issued successfully', [
            'certification_id' => $certification->id,
            'product_id' => $product->id,
        ]);
    }

    /**
     * Determine certification type based on checkpoint.
     */
    protected function determineCertificationType(): string
    {
        return match($this->checkpoint->checkpoint_type) {
            'laboratory' => 'laboratory_tested',
            'certification' => 'quality_certified',
            'processing' => 'processing_certified',
            'organic' => 'organic_certified',
            default => 'quality_passed',
        };
    }

    /**
     * Determine certification standard based on score.
     */
    protected function determineCertificationStandard(): string
    {
        $score = $this->checkpoint->pass_score;

        return match(true) {
            $score >= 98 => 'ISO 22000',
            $score >= 95 => 'HACCP',
            $score >= 90 => 'GMP',
            $score >= 85 => 'GAP',
            default => 'Basic Quality',
        };
    }

    /**
     * Generate unique certificate number.
     */
    protected function generateCertificateNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $random = strtoupper(Str::random(6));

        return "QC-{$year}{$month}-{$random}";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to issue certification', [
            'checkpoint_id' => $this->checkpoint->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
