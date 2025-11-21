<?php

namespace App\Services\TPIX;

use App\Models\TpixConfiguration;
use Illuminate\Support\Facades\Config;

/**
 * TPIX Pricing Service
 *
 * คำนวณราคาค่าบริการสำหรับ TPIX Deployment Wizard
 */
class TpixPricingService
{
    /**
     * คำนวณราคารวมสำหรับ configuration
     *
     * @param TpixConfiguration $config
     * @return array
     */
    public function calculateTotalPrice(TpixConfiguration $config): array
    {
        $breakdown = [];
        $total = 0;

        // 1. Base Deployment Cost
        $basePrice = Config::get('tpix-pricing.base_deployment', 100);
        $breakdown['base_deployment'] = [
            'label' => 'การ Deploy พื้นฐาน',
            'price' => $basePrice,
            'included' => [
                'Token Configuration',
                'Basic Smart Contract',
                'Deployment',
                'Block Explorer Verification',
            ],
        ];
        $total += $basePrice;

        // 2. Contract Features
        if ($config->contract_features && is_array($config->contract_features)) {
            $featuresPrice = 0;
            $featuresList = [];

            foreach ($config->contract_features as $feature) {
                $price = Config::get("tpix-pricing.contract_features.{$feature}", 0);
                if ($price > 0) {
                    $featuresPrice += $price;
                    $featuresList[$feature] = $price;
                }
            }

            if ($featuresPrice > 0) {
                $breakdown['contract_features'] = [
                    'label' => 'Contract Features',
                    'price' => $featuresPrice,
                    'items' => $featuresList,
                ];
                $total += $featuresPrice;
            }
        }

        // 3. Access Control
        if ($config->access_control && $config->access_control !== 'owner') {
            $accessPrice = Config::get("tpix-pricing.access_control.{$config->access_control}", 0);
            if ($accessPrice > 0) {
                $breakdown['access_control'] = [
                    'label' => 'Access Control: ' . ucwords(str_replace('_', ' ', $config->access_control)),
                    'price' => $accessPrice,
                ];
                $total += $accessPrice;
            }
        }

        // 4. Upgradeable Contract
        if ($config->is_upgradeable) {
            $upgradePrice = Config::get('tpix-pricing.advanced_features.upgradeable', 200);
            $breakdown['upgradeable'] = [
                'label' => 'Upgradeable Contract',
                'price' => $upgradePrice,
            ];
            $total += $upgradePrice;
        }

        // 5. DEX Integration
        if ($config->dex_pool_address || $config->current_step >= 6) {
            $dexPrice = Config::get('tpix-pricing.dex_integration.create_pool', 200);
            $breakdown['dex_integration'] = [
                'label' => 'DEX Integration',
                'price' => $dexPrice,
                'included' => ['Liquidity Pool Creation'],
            ];
            $total += $dexPrice;
        }

        // 6. Premium Services (ถ้ามี)
        if ($config->premium_services && is_array($config->premium_services)) {
            $premiumPrice = 0;
            $premiumList = [];

            foreach ($config->premium_services as $service) {
                $price = Config::get("tpix-pricing.premium_services.{$service}", 0);
                if ($price > 0) {
                    $premiumPrice += $price;
                    $premiumList[$service] = $price;
                }
            }

            if ($premiumPrice > 0) {
                $breakdown['premium_services'] = [
                    'label' => 'Premium Services',
                    'price' => $premiumPrice,
                    'items' => $premiumList,
                ];
                $total += $premiumPrice;
            }
        }

        // 7. Discount (ถ้ามี)
        $discount = $this->calculateDiscount($config, $total);
        if ($discount > 0) {
            $breakdown['discount'] = [
                'label' => 'ส่วนลด',
                'price' => -$discount,
                'percentage' => round(($discount / $total) * 100, 2),
            ];
            $total -= $discount;
        }

        return [
            'breakdown' => $breakdown,
            'subtotal' => array_sum(array_column($breakdown, 'price')) + $discount,
            'discount' => $discount,
            'total' => $total,
            'currency' => 'TPIX',
        ];
    }

    /**
     * คำนวณส่วนลด
     *
     * @param TpixConfiguration $config
     * @param float $total
     * @return float
     */
    protected function calculateDiscount(TpixConfiguration $config, float $total): float
    {
        $discount = 0;

        // Early Adopter Discount (ถ้ามี flag)
        if ($config->is_early_adopter ?? false) {
            $percentage = Config::get('tpix-pricing.discounts.early_adopter', 20);
            $discount += ($total * $percentage / 100);
        }

        // Referral Discount (ถ้ามี referral code)
        if ($config->referral_code ?? false) {
            $percentage = Config::get('tpix-pricing.discounts.referral', 10);
            $discount += ($total * $percentage / 100);
        }

        return round($discount, 2);
    }

    /**
     * คำนวณราคาตาม Step ที่กำลังทำ
     *
     * @param int $step
     * @param array $data
     * @return float
     */
    public function calculateStepPrice(int $step, array $data): float
    {
        $price = 0;

        switch ($step) {
            case 1: // Prerequisites - ฟรี
                $price = 0;
                break;

            case 2: // Token Configuration - Base Price
                $price = Config::get('tpix-pricing.base_deployment', 100);
                break;

            case 3: // Tokenomics
                // คำนวณจาก tokenomics features ที่เลือก
                if (isset($data['tokenomics_features']) && is_array($data['tokenomics_features'])) {
                    foreach ($data['tokenomics_features'] as $feature) {
                        $price += Config::get("tpix-pricing.tokenomics_features.{$feature}", 0);
                    }
                }
                break;

            case 4: // Smart Contract
                // คำนวณจาก contract features + access control
                if (isset($data['contract_features']) && is_array($data['contract_features'])) {
                    foreach ($data['contract_features'] as $feature) {
                        $price += Config::get("tpix-pricing.contract_features.{$feature}", 0);
                    }
                }
                if (isset($data['access_control']) && $data['access_control'] !== 'owner') {
                    $price += Config::get("tpix-pricing.access_control.{$data['access_control']}", 0);
                }
                if (isset($data['is_upgradeable']) && $data['is_upgradeable']) {
                    $price += Config::get('tpix-pricing.advanced_features.upgradeable', 200);
                }
                break;

            case 5: // Deploy & Verify - รวมใน base price แล้ว
                $price = 0;
                break;

            case 6: // DEX Integration
                $price = Config::get('tpix-pricing.dex_integration.create_pool', 200);
                break;

            case 7: // Listing - Optional
                if (isset($data['submit_cmc']) && $data['submit_cmc']) {
                    $price += Config::get('tpix-pricing.verification_listing.coinmarketcap', 500);
                }
                if (isset($data['submit_coingecko']) && $data['submit_coingecko']) {
                    $price += Config::get('tpix-pricing.verification_listing.coingecko', 500);
                }
                break;
        }

        return round($price, 2);
    }

    /**
     * ตรวจสอบว่ามี TPIX เพียงพอหรือไม่
     *
     * @param string $walletAddress
     * @param float $requiredAmount
     * @return array
     */
    public function checkBalance(string $walletAddress, float $requiredAmount): array
    {
        // TODO: เชื่อมต่อกับ TPIX Blockchain เพื่อดึง balance จริง
        // ตอนนี้ใช้ mock data
        $balance = 1000.00; // Mock balance

        $hasEnough = $balance >= $requiredAmount;
        $shortage = $hasEnough ? 0 : ($requiredAmount - $balance);

        return [
            'has_enough' => $hasEnough,
            'balance' => $balance,
            'required' => $requiredAmount,
            'shortage' => $shortage,
            'currency' => 'TPIX',
        ];
    }

    /**
     * สร้าง Payment Transaction
     *
     * @param TpixConfiguration $config
     * @param string $walletAddress
     * @return array
     */
    public function createPaymentTransaction(TpixConfiguration $config, string $walletAddress): array
    {
        $pricing = $this->calculateTotalPrice($config);

        // TODO: สร้าง transaction บน blockchain
        // ตอนนี้ใช้ mock data
        $txHash = '0x' . bin2hex(random_bytes(32));

        return [
            'success' => true,
            'transaction_hash' => $txHash,
            'amount' => $pricing['total'],
            'currency' => 'TPIX',
            'from' => $walletAddress,
            'to' => Config::get('tpix-pricing.service_fee_wallet'),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * ตรวจสอบสถานะการชำระเงิน
     *
     * @param string $txHash
     * @return array
     */
    public function verifyPayment(string $txHash): array
    {
        // TODO: ตรวจสอบ transaction จริงบน blockchain
        // ตอนนี้ใช้ mock data
        return [
            'verified' => true,
            'transaction_hash' => $txHash,
            'status' => 'confirmed',
            'confirmations' => 12,
            'timestamp' => now()->subMinutes(5)->toIso8601String(),
        ];
    }

    /**
     * Format ราคาสำหรับแสดงผล
     *
     * @param float $price
     * @param bool $includeSymbol
     * @return string
     */
    public function formatPrice(float $price, bool $includeSymbol = true): string
    {
        $formatted = number_format($price, 2);
        return $includeSymbol ? $formatted . ' TPIX' : $formatted;
    }

    /**
     * ดึงรายการราคาทั้งหมด (สำหรับแสดงใน UI)
     *
     * @return array
     */
    public function getPricingTable(): array
    {
        return [
            'base' => [
                'deployment' => Config::get('tpix-pricing.base_deployment'),
            ],
            'features' => Config::get('tpix-pricing.contract_features'),
            'access_control' => Config::get('tpix-pricing.access_control'),
            'advanced' => Config::get('tpix-pricing.advanced_features'),
            'dex' => Config::get('tpix-pricing.dex_integration'),
            'verification' => Config::get('tpix-pricing.verification_listing'),
            'tokenomics' => Config::get('tpix-pricing.tokenomics_features'),
            'premium' => Config::get('tpix-pricing.premium_services'),
            'discounts' => Config::get('tpix-pricing.discounts'),
        ];
    }
}
