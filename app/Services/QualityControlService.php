<?php

namespace App\Services;

use App\Models\FoodProduct;
use App\Models\QualityCheckpoint;
use App\Models\FoodCertification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QualityControlService
{
    public function __construct(
        protected BlockchainRecordService $blockchain
    ) {}

    /**
     * Create a new quality checkpoint
     */
    public function createCheckpoint(array $data): QualityCheckpoint
    {
        return DB::transaction(function () use ($data) {
            $checkpoint = QualityCheckpoint::create([
                'food_product_id' => $data['food_product_id'],
                'journey_stage_id' => $data['journey_stage_id'] ?? null,
                'checkpoint_type' => $data['checkpoint_type'],
                'checkpoint_name' => $data['checkpoint_name'],
                'checked_at' => $data['checked_at'] ?? now(),
                'inspector_id' => $data['inspector_id'] ?? auth()->id(),
                'inspector_name' => $data['inspector_name'] ?? auth()->user()->name,
                'inspector_license' => $data['inspector_license'] ?? null,
                'organization' => $data['organization'] ?? null,
                'test_parameters' => $data['test_parameters'] ?? [],
                'overall_result' => $data['overall_result'],
                'pass_score' => $data['pass_score'] ?? null,
                'standards_applied' => $data['standards_applied'] ?? [],
                'test_report_url' => $data['test_report_url'] ?? null,
                'photos' => $data['photos'] ?? [],
                'notes' => $data['notes'] ?? null,
            ]);

            // Evaluate test results
            $evaluation = $this->evaluateResults($checkpoint);
            $checkpoint->update($evaluation);

            // Issue certification if applicable
            if ($checkpoint->certification_issued) {
                $this->issueCertification($checkpoint, $data);
            }

            // Record on blockchain
            try {
                $hash = $this->blockchain->recordQualityCheckOnChain($checkpoint);
                $checkpoint->update(['blockchain_hash' => $hash]);
            } catch (\Exception $e) {
                \Log::error('Blockchain quality check recording failed', [
                    'checkpoint_id' => $checkpoint->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Update product quality score
            $this->updateProductQualityScore($checkpoint->food_product_id);

            // Send notifications
            $this->notifyStakeholders($checkpoint);

            return $checkpoint->fresh();
        });
    }

    /**
     * Evaluate test results and calculate score
     */
    public function evaluateResults(QualityCheckpoint $checkpoint): array
    {
        $testParams = $checkpoint->test_parameters;

        if (empty($testParams)) {
            return [];
        }

        $totalTests = count($testParams);
        $passedTests = collect($testParams)
            ->filter(fn($param) => $param['status'] === 'pass')
            ->count();

        $score = ($passedTests / $totalTests) * 100;

        return [
            'pass_score' => round($score, 2),
        ];
    }

    /**
     * Approve a checkpoint
     */
    public function approveCheckpoint(int $checkpointId): bool
    {
        $checkpoint = QualityCheckpoint::findOrFail($checkpointId);

        $checkpoint->update(['overall_result' => 'pass']);

        $this->updateProductQualityScore($checkpoint->food_product_id);

        return true;
    }

    /**
     * Reject a checkpoint and request corrective actions
     */
    public function rejectCheckpoint(int $checkpointId, string $reason): bool
    {
        $checkpoint = QualityCheckpoint::findOrFail($checkpointId);

        $checkpoint->update([
            'overall_result' => 'fail',
            'corrective_actions' => $reason,
            'retest_required' => true,
        ]);

        // Flag the product
        $product = $checkpoint->foodProduct;
        // Could add a flag or status to product here

        return true;
    }

    /**
     * Request retest for a checkpoint
     */
    public function requestRetest(int $checkpointId): QualityCheckpoint
    {
        $originalCheckpoint = QualityCheckpoint::findOrFail($checkpointId);

        // Create new checkpoint for retest
        $retestData = $originalCheckpoint->toArray();
        unset($retestData['id'], $retestData['created_at'], $retestData['updated_at']);

        $retestData['checkpoint_name'] = 'Retest: ' . $retestData['checkpoint_name'];
        $retestData['retest_checkpoint_id'] = $originalCheckpoint->id;
        $retestData['checked_at'] = null; // Will be set when actual retest happens

        return QualityCheckpoint::create($retestData);
    }

    /**
     * Generate quality report for a product
     */
    public function generateQualityReport(int $productId): array
    {
        $product = FoodProduct::with('qualityCheckpoints')->findOrFail($productId);

        $checkpoints = $product->qualityCheckpoints;

        return [
            'product_id' => $product->id,
            'passport_id' => $product->food_passport_id,
            'variety' => $product->variety,
            'total_checkpoints' => $checkpoints->count(),
            'passed_checkpoints' => $checkpoints->where('overall_result', 'pass')->count(),
            'failed_checkpoints' => $checkpoints->where('overall_result', 'fail')->count(),
            'average_score' => $checkpoints->avg('pass_score'),
            'latest_checkpoint' => $product->latestQualityCheck(),
            'checkpoints_by_type' => $checkpoints->groupBy('checkpoint_type')->map(fn($c) => $c->count()),
            'checkpoints_by_stage' => $checkpoints->groupBy('journey_stage_id')->map(fn($c) => $c->count()),
        ];
    }

    /**
     * Calculate overall quality score for a product
     */
    public function calculateQualityScore(int $productId): float
    {
        $avgScore = QualityCheckpoint::where('food_product_id', $productId)
            ->where('overall_result', 'pass')
            ->avg('pass_score');

        return round($avgScore ?? 0, 2);
    }

    /**
     * Update product quality score
     */
    protected function updateProductQualityScore(int $productId): void
    {
        $score = $this->calculateQualityScore($productId);

        // Update product or create a summary record
        // For now, we'll just calculate it on-the-fly
        // Could add a quality_score field to food_products table
    }

    /**
     * Issue certification based on checkpoint
     */
    protected function issueCertification(QualityCheckpoint $checkpoint, array $data): void
    {
        if (!$checkpoint->passed()) {
            return;
        }

        $certificationService = app(CertificationService::class);

        $certificationService->issueCertification([
            'food_product_id' => $checkpoint->food_product_id,
            'certification_type' => $data['certification_type'] ?? 'Quality Assurance',
            'certification_name' => $data['certification_name'] ?? 'Quality Pass',
            'certifying_body' => $checkpoint->organization,
            'issued_date' => now(),
            'expiry_date' => now()->addYear(),
            'scope' => "Quality checkpoint passed with score {$checkpoint->pass_score}%",
            'standards_applied' => $checkpoint->standards_applied,
        ]);

        $checkpoint->update([
            'certification_issued' => true,
        ]);
    }

    /**
     * Notify stakeholders about quality check results
     */
    protected function notifyStakeholders(QualityCheckpoint $checkpoint): void
    {
        $product = $checkpoint->foodProduct;

        // Notify farmer
        if ($product->farmer && $product->farmer->line_user_id) {
            try {
                app(LineService::class)->sendFlexMessage(
                    $product->farmer->line_user_id,
                    $this->buildQualityResultMessage($checkpoint)
                );
            } catch (\Exception $e) {
                \Log::error('LINE notification failed', [
                    'checkpoint_id' => $checkpoint->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Build LINE flex message for quality result
     */
    protected function buildQualityResultMessage(QualityCheckpoint $checkpoint): array
    {
        $product = $checkpoint->foodProduct;

        $emoji = match($checkpoint->overall_result) {
            'pass' => '✅',
            'conditional_pass' => '⚠️',
            'fail' => '❌',
            default => '📋',
        };

        $color = match($checkpoint->overall_result) {
            'pass' => '#00AA00',
            'conditional_pass' => '#FFA500',
            'fail' => '#FF0000',
            default => '#666666',
        };

        return [
            'type' => 'flex',
            'altText' => 'ผลตรวจสอบคุณภาพ',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => "{$emoji} ผลตรวจสอบคุณภาพ",
                            'weight' => 'bold',
                            'size' => 'xl',
                        ],
                        [
                            'type' => 'text',
                            'text' => $product->variety,
                            'margin' => 'md',
                            'color' => '#666666',
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
                                    'text' => 'คะแนน: ' . round($checkpoint->pass_score) . '/100',
                                    'size' => 'xxl',
                                    'weight' => 'bold',
                                    'color' => $color,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'ประเภท: ' . $checkpoint->checkpoint_name,
                                    'size' => 'sm',
                                    'margin' => 'md',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'ผู้ตรวจ: ' . $checkpoint->inspector_name,
                                    'size' => 'sm',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get quality trends over time
     */
    public function getQualityTrends(int $productId, string $period = '30days'): Collection
    {
        $days = match($period) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            default => 30,
        };

        return QualityCheckpoint::where('food_product_id', $productId)
            ->where('checked_at', '>=', now()->subDays($days))
            ->orderBy('checked_at')
            ->get()
            ->groupBy(fn($c) => $c->checked_at->format('Y-m-d'))
            ->map(fn($group) => [
                'date' => $group->first()->checked_at->format('Y-m-d'),
                'avg_score' => $group->avg('pass_score'),
                'checkpoints' => $group->count(),
            ])
            ->values();
    }
}
