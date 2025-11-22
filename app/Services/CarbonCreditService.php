<?php

namespace App\Services;

use App\Models\FoodProduct;
use App\Models\ProductJourney;
use App\Models\CarbonFootprintRecord;
use App\Models\CarbonCredit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CarbonCreditService
{
    /**
     * Emission factors (kg CO2 per unit)
     */
    protected array $emissionFactors = [
        'transport' => [
            'diesel_truck_light' => 0.25,  // per km
            'diesel_truck_medium' => 0.68,
            'diesel_truck_heavy' => 1.2,
            'electric_vehicle' => 0.15,
            'ship' => 0.01,  // per ton-km
            'air_freight' => 1.2,  // per ton-km
        ],
        'farming' => [
            'organic_vegetables' => 1.0,  // per kg product
            'conventional_vegetables' => 3.0,
            'organic_rice' => 2.5,
            'conventional_rice' => 4.0,
        ],
        'processing' => [
            'cold_storage' => 0.2,  // per kg per day
            'packaging_plastic' => 6.0,  // per kg packaging
            'packaging_paper' => 2.5,
        ],
    ];

    /**
     * Baseline emissions for product types (kg CO2 per ton)
     */
    protected array $baselines = [
        'vegetables' => 30.0,
        'fruits' => 25.0,
        'rice' => 40.0,
        'meat' => 150.0,
        'seafood' => 35.0,
    ];

    public function __construct(
        protected BlockchainRecordService $blockchain
    ) {}

    /**
     * Calculate total carbon footprint for a product
     */
    public function calculateFootprint(int $productId): float
    {
        $product = FoodProduct::with('journeyStages')->findOrFail($productId);

        // Sum all recorded emissions
        $total = CarbonFootprintRecord::where('food_product_id', $productId)
            ->sum('total_co2_equivalent');

        // Update product
        $product->update(['total_carbon_footprint' => $total]);

        // Calculate carbon score
        $score = $this->calculateCarbonScore($product);
        $product->update(['carbon_score' => $score]);

        return $total;
    }

    /**
     * Calculate transport emission for a journey stage
     */
    public function calculateTransportEmission(ProductJourney $journey): float
    {
        $method = $journey->transport_method;
        $distance = $journey->distance_km;

        if (!$distance || !$method) {
            return 0;
        }

        // Map method to emission factor
        $factorKey = $this->mapTransportMethod($method);
        $factor = $this->emissionFactors['transport'][$factorKey] ?? 0.5;

        $emission = $distance * $factor;

        // Record this emission
        CarbonFootprintRecord::create([
            'food_product_id' => $journey->food_product_id,
            'journey_stage_id' => $journey->id,
            'emission_category' => 'transportation',
            'emission_source' => "{$method} - {$distance} km",
            'activity_data' => [
                'distance_km' => $distance,
                'vehicle_type' => $method,
            ],
            'emission_factor' => $factor,
            'co2_emission' => $emission,
            'total_co2_equivalent' => $emission,
            'net_emission' => $emission,
            'calculation_method' => 'Distance-based',
            'calculation_date' => now(),
            'verification_status' => 'pending',
        ]);

        return $emission;
    }

    /**
     * Calculate farming emission
     */
    public function calculateFarmingEmission(FoodProduct $product, bool $isOrganic = false): float
    {
        $productType = $product->product_type;
        $quantity = $product->estimated_quantity;

        $factorKey = $isOrganic ? "organic_{$productType}" : "conventional_{$productType}";
        $factor = $this->emissionFactors['farming'][$factorKey] ?? 2.0;

        $emission = $quantity * $factor;

        CarbonFootprintRecord::create([
            'food_product_id' => $product->id,
            'emission_category' => 'farming',
            'emission_source' => $isOrganic ? 'Organic farming' : 'Conventional farming',
            'activity_data' => [
                'quantity' => $quantity,
                'unit' => $product->unit,
                'farming_method' => $isOrganic ? 'organic' : 'conventional',
            ],
            'emission_factor' => $factor,
            'co2_emission' => $emission,
            'total_co2_equivalent' => $emission,
            'net_emission' => $emission,
            'calculation_method' => 'Quantity-based',
            'calculation_date' => now(),
            'verification_status' => 'pending',
        ]);

        return $emission;
    }

    /**
     * Calculate carbon score (A+ to E)
     */
    public function calculateCarbonScore(FoodProduct $product): string
    {
        $emission = $product->total_carbon_footprint;
        $quantity = $product->estimated_quantity;

        if ($quantity == 0) return 'N/A';

        $emissionPerKg = $emission / $quantity;

        // Grading
        if ($emissionPerKg <= 0) return 'A+';
        if ($emissionPerKg < 1) return 'A';
        if ($emissionPerKg < 3) return 'B';
        if ($emissionPerKg < 5) return 'C';
        if ($emissionPerKg < 10) return 'D';
        return 'E';
    }

    /**
     * Issue carbon credit for a product
     */
    public function issueCarbonCredit(int $userId, int $productId): ?CarbonCredit
    {
        $product = FoodProduct::findOrFail($productId);

        // Get baseline
        $baseline = $this->baselines[$product->product_type] ?? 30.0;
        $baselineTotal = ($baseline / 1000) * $product->estimated_quantity;

        $actual = $product->total_carbon_footprint;

        // Must be lower than baseline
        if ($actual >= $baselineTotal) {
            \Log::info('No carbon credit issued - emission not below baseline', [
                'product_id' => $productId,
                'baseline' => $baselineTotal,
                'actual' => $actual,
            ]);
            return null;
        }

        $saved = $baselineTotal - $actual;
        $savedTons = $saved / 1000;  // Convert to tons

        // Create carbon credit
        $credit = CarbonCredit::create([
            'user_id' => $userId,
            'food_product_id' => $productId,
            'credit_amount' => $savedTons,
            'credit_type' => 'reduction',
            'baseline_emission' => $baselineTotal,
            'actual_emission' => $actual,
            'reduction_percentage' => (($saved / $baselineTotal) * 100),
            'credit_value_per_ton' => 250,  // THB
            'total_value' => $savedTons * 250,
            'issued_date' => now(),
            'expiry_date' => now()->addYear(),
            'status' => 'active',
            'tradeable' => true,
            'verified_by' => 'System Auto-calculation',
            'verification_standard' => 'ISO 14064',
            'certificate_number' => $this->generateCertificateNumber(),
        ]);

        // Record on blockchain
        try {
            $hash = $this->blockchain->recordCarbonDataOnChain($credit);
            $credit->update(['blockchain_hash' => $hash]);
        } catch (\Exception $e) {
            \Log::error('Blockchain carbon credit recording failed', [
                'credit_id' => $credit->id,
                'error' => $e->getMessage()
            ]);
        }

        // Send notification
        $this->notifyCreditIssuedIssuance($credit);

        return $credit;
    }

    /**
     * Verify carbon emission data
     */
    public function verifyEmissionData(int $recordId, int $verifierId): bool
    {
        $record = CarbonFootprintRecord::findOrFail($recordId);

        $record->update([
            'verification_status' => 'verified',
            'verified_by_id' => $verifierId,
        ]);

        return true;
    }

    /**
     * Trade carbon credit
     */
    public function tradeCarbonCredit(int $creditId, int $buyerId, float $price): bool
    {
        return DB::transaction(function () use ($creditId, $buyerId, $price) {
            $credit = CarbonCredit::findOrFail($creditId);

            // Check if can be traded
            if (!$credit->canTrade()) {
                throw new \Exception('This credit cannot be traded');
            }

            $sellerId = $credit->user_id;

            // Update credit ownership
            $credit->update([
                'traded_to_user_id' => $buyerId,
                'traded_at' => now(),
                'trade_price' => $price,
                'status' => 'traded',
                'tradeable' => false,  // Cannot be traded again
            ]);

            // Process payment (integrate with existing wallet system)
            // Deduct from buyer, credit to seller
            // Commission: 5%
            $commission = $price * 0.05;
            $sellerAmount = $price - $commission;

            // TODO: Integrate with WalletService
            // app(WalletService::class)->transfer($buyerId, $sellerId, $sellerAmount);

            // Record transaction on blockchain
            try {
                $this->blockchain->recordCarbonTradeOnChain($credit, $buyerId, $price);
            } catch (\Exception $e) {
                \Log::error('Blockchain carbon trade recording failed', [
                    'credit_id' => $creditId,
                    'error' => $e->getMessage()
                ]);
            }

            return true;
        });
    }

    /**
     * Get carbon credit leaderboard
     */
    public function getLeaderboard(string $period = 'month'): Collection
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        return CarbonCredit::where('issued_date', '>=', $startDate)
            ->selectRaw('user_id, SUM(credit_amount) as total_credits, SUM(total_value) as total_value, COUNT(*) as products_count')
            ->groupBy('user_id')
            ->with('user')
            ->orderBy('total_credits', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Calculate rewards for a user
     */
    public function calculateRewards(int $userId): array
    {
        $credits = CarbonCredit::byUser($userId)->active()->get();

        $totalCredits = $credits->sum('credit_amount');
        $totalValue = $credits->sum('total_value');
        $avgReduction = $credits->avg('reduction_percentage');

        // Tier system
        $tier = match(true) {
            $totalCredits >= 500 => 'Platinum',
            $totalCredits >= 100 => 'Gold',
            $totalCredits >= 50 => 'Silver',
            $totalCredits >= 10 => 'Bronze',
            default => 'Starter',
        };

        // Bonus multiplier
        $bonus = match($tier) {
            'Platinum' => 1.20,
            'Gold' => 1.15,
            'Silver' => 1.10,
            'Bronze' => 1.05,
            default => 1.00,
        };

        return [
            'total_credits' => $totalCredits,
            'total_value' => $totalValue,
            'avg_reduction_percentage' => round($avgReduction, 2),
            'tier' => $tier,
            'bonus_multiplier' => $bonus,
            'bonus_value' => $totalValue * ($bonus - 1),
            'total_with_bonus' => $totalValue * $bonus,
        ];
    }

    /**
     * Get carbon breakdown by category
     */
    public function getCarbonBreakdown(int $productId): array
    {
        return CarbonFootprintRecord::where('food_product_id', $productId)
            ->selectRaw('emission_category, SUM(total_co2_equivalent) as total, AVG(emission_factor) as avg_factor')
            ->groupBy('emission_category')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->emission_category => [
                    'total_emission' => $item->total,
                    'avg_factor' => $item->avg_factor,
                ]
            ])
            ->toArray();
    }

    /**
     * Map transport method to emission factor key
     */
    protected function mapTransportMethod(string $method): string
    {
        $method = strtolower($method);

        $map = [
            'truck' => 'diesel_truck_medium',
            'diesel_truck' => 'diesel_truck_medium',
            'light_truck' => 'diesel_truck_light',
            'heavy_truck' => 'diesel_truck_heavy',
            'electric_vehicle' => 'electric_vehicle',
            'ev' => 'electric_vehicle',
            'ship' => 'ship',
            'boat' => 'ship',
            'air' => 'air_freight',
            'plane' => 'air_freight',
        ];

        return $map[$method] ?? 'diesel_truck_medium';
    }

    /**
     * Generate certificate number
     */
    protected function generateCertificateNumber(): string
    {
        return 'CC-' . date('Y') . '-' . str_pad(CarbonCredit::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Notify user about carbon credit issuance
     */
    protected function notifyCreditIssuance(CarbonCredit $credit): void
    {
        $user = $credit->user;

        if ($user && $user->line_user_id) {
            try {
                app(LineService::class)->sendFlexMessage(
                    $user->line_user_id,
                    $this->buildCarbonCreditMessage($credit)
                );
            } catch (\Exception $e) {
                \Log::error('LINE notification failed', [
                    'credit_id' => $credit->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Build LINE flex message for carbon credit
     */
    protected function buildCarbonCreditMessage(CarbonCredit $credit): array
    {
        return [
            'type' => 'flex',
            'altText' => 'คุณได้รับ Carbon Credit!',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🎉 Carbon Credit',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#00AA00',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'คุณได้รับ Carbon Credit!',
                            'margin' => 'md',
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
                                    'text' => round($credit->credit_amount, 2) . ' tons CO2',
                                    'size' => 'xxl',
                                    'weight' => 'bold',
                                    'color' => '#00AA00',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'มูลค่า: ฿' . number_format($credit->total_value, 2),
                                    'size' => 'lg',
                                    'margin' => 'md',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'ลดได้: ' . round($credit->reduction_percentage, 1) . '%',
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
