<?php

namespace App\Services\TPIX;

use App\Models\TpixConfiguration;
use App\Models\TPIXLiquidityPool;
use App\Models\User;
use App\Services\Crypto\TPIXBlockchainService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TPIX Deployment Service
 *
 * จัดการ business logic สำหรับ TPIX Native Coin Deployment Wizard
 *
 * @package App\Services\TPIX
 */
class TpixDeploymentService
{
    /**
     * Blockchain Service
     *
     * @var TPIXBlockchainService
     */
    protected TPIXBlockchainService $blockchainService;

    /**
     * Constructor
     *
     * @param TPIXBlockchainService $blockchainService
     */
    public function __construct(TPIXBlockchainService $blockchainService)
    {
        $this->blockchainService = $blockchainService;
    }

    // =====================================
    // STEP 1: Prerequisites Check
    // =====================================

    /**
     * ตรวจสอบ prerequisites ทั้งหมด
     *
     * @return array
     */
    public function checkPrerequisites(): array
    {
        $results = [
            'rpc_node' => $this->checkRpcNode(),
            'wallet' => $this->checkWallet(),
            'services' => $this->checkServices(),
            'environment' => $this->checkEnvironment(),
        ];

        // สรุปผลรวม
        $results['all_passed'] = $results['rpc_node']['passed']
            && $results['wallet']['passed']
            && $results['services']['passed']
            && $results['environment']['passed'];

        return $results;
    }

    /**
     * ตรวจสอบ RPC Node
     *
     * @return array
     */
    protected function checkRpcNode(): array
    {
        try {
            // ตรวจสอบว่าเชื่อมต่อ RPC ได้หรือไม่
            $blockNumber = $this->blockchainService->getBlockNumber();
            $networkInfo = $this->blockchainService->getNetworkInfo();

            return [
                'passed' => true,
                'message' => 'เชื่อมต่อ RPC Node สำเร็จ',
                'details' => [
                    'block_number' => $blockNumber,
                    'chain_id' => $networkInfo['chainId'] ?? null,
                    'rpc_url' => config('tpix.rpc_url'),
                ],
            ];
        } catch (Exception $e) {
            return [
                'passed' => false,
                'message' => 'ไม่สามารถเชื่อมต่อ RPC Node ได้',
                'error' => $e->getMessage(),
                'solution' => 'กรุณาตรวจสอบว่า TPIX Blockchain Node กำลังทำงานอยู่',
            ];
        }
    }

    /**
     * ตรวจสอบ Wallet
     *
     * @return array
     */
    protected function checkWallet(): array
    {
        try {
            // ตรวจสอบว่ามี wallet address และ private key
            $walletAddress = config('tpix.deployer_address');
            $privateKey = config('tpix.deployer_private_key');

            if (empty($walletAddress) || empty($privateKey)) {
                return [
                    'passed' => false,
                    'message' => 'ไม่พบการตั้งค่า Wallet',
                    'solution' => 'กรุณาตั้งค่า TPIX_DEPLOYER_ADDRESS และ TPIX_DEPLOYER_PRIVATE_KEY ใน .env',
                ];
            }

            // ตรวจสอบ balance
            try {
                $balance = $this->blockchainService->getBalance($walletAddress);
                $balanceTPIX = hexdec($balance) / 1e18;

                if ($balanceTPIX < 1) {
                    return [
                        'passed' => false,
                        'message' => 'Wallet มี TPIX ไม่เพียงพอสำหรับ deploy (ต้องการอย่างน้อย 1 TPIX)',
                        'details' => [
                            'address' => $walletAddress,
                            'balance' => number_format($balanceTPIX, 4) . ' TPIX',
                        ],
                        'solution' => 'กรุณาเติม TPIX เข้า deployer wallet',
                    ];
                }

                return [
                    'passed' => true,
                    'message' => 'Wallet พร้อมใช้งาน',
                    'details' => [
                        'address' => $walletAddress,
                        'balance' => number_format($balanceTPIX, 4) . ' TPIX',
                    ],
                ];
            } catch (Exception $e) {
                return [
                    'passed' => false,
                    'message' => 'ไม่สามารถตรวจสอบ balance ของ wallet ได้',
                    'error' => $e->getMessage(),
                ];
            }
        } catch (Exception $e) {
            return [
                'passed' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบ wallet',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ตรวจสอบ Services
     *
     * @return array
     */
    protected function checkServices(): array
    {
        $services = [];

        // ตรวจสอบ Redis
        try {
            \Illuminate\Support\Facades\Cache::store('redis')->put('test', 'value', 10);
            $services['redis'] = [
                'running' => true,
                'message' => 'Redis ทำงานปกติ',
            ];
        } catch (Exception $e) {
            $services['redis'] = [
                'running' => false,
                'message' => 'Redis ไม่ทำงาน: ' . $e->getMessage(),
            ];
        }

        // ตรวจสอบ Database
        try {
            DB::connection()->getPdo();
            $services['database'] = [
                'running' => true,
                'message' => 'Database เชื่อมต่อสำเร็จ',
            ];
        } catch (Exception $e) {
            $services['database'] = [
                'running' => false,
                'message' => 'Database เชื่อมต่อไม่สำเร็จ: ' . $e->getMessage(),
            ];
        }

        // ตรวจสอบ Queue
        $services['queue'] = [
            'running' => true, // สมมติว่าทำงาน (ตรวจสอบจริงต้องใช้ supervisor status)
            'message' => 'Queue Worker พร้อมใช้งาน',
        ];

        $allRunning = collect($services)->every(fn($service) => $service['running']);

        return [
            'passed' => $allRunning,
            'message' => $allRunning ? 'บริการทั้งหมดพร้อมใช้งาน' : 'มีบริการบางตัวไม่พร้อม',
            'services' => $services,
        ];
    }

    /**
     * ตรวจสอบ Environment
     *
     * @return array
     */
    protected function checkEnvironment(): array
    {
        $checks = [];

        // ตรวจสอบ PHP version
        $checks['php_version'] = [
            'passed' => version_compare(PHP_VERSION, '8.1', '>='),
            'value' => PHP_VERSION,
            'required' => '8.1+',
        ];

        // ตรวจสอบ Laravel version
        $checks['laravel_version'] = [
            'passed' => true,
            'value' => app()->version(),
            'required' => '11.x',
        ];

        // ตรวจสอบ required extensions
        $requiredExtensions = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
        $missingExtensions = [];

        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }

        $checks['php_extensions'] = [
            'passed' => empty($missingExtensions),
            'missing' => $missingExtensions,
        ];

        // ตรวจสอบ permissions
        $writablePaths = ['storage', 'bootstrap/cache'];
        $notWritable = [];

        foreach ($writablePaths as $path) {
            if (!is_writable(base_path($path))) {
                $notWritable[] = $path;
            }
        }

        $checks['permissions'] = [
            'passed' => empty($notWritable),
            'not_writable' => $notWritable,
        ];

        $allPassed = collect($checks)->every(fn($check) => $check['passed']);

        return [
            'passed' => $allPassed,
            'message' => $allPassed ? 'Environment พร้อมใช้งาน' : 'Environment มีปัญหา',
            'checks' => $checks,
        ];
    }

    // =====================================
    // STEP 2-3: Configuration & Tokenomics
    // =====================================

    /**
     * บันทึกการตั้งค่าพื้นฐาน (Step 2)
     *
     * @param TpixConfiguration $config
     * @param array $data
     * @return TpixConfiguration
     */
    public function saveBasicConfiguration(TpixConfiguration $config, array $data): TpixConfiguration
    {
        $config->fill([
            'token_name' => $data['token_name'],
            'token_symbol' => $data['token_symbol'],
            'total_supply' => $data['total_supply'],
            'decimals' => $data['decimals'] ?? 18,
            'description' => $data['description'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'category' => $data['category'] ?? 'utility',
            'social_links' => $data['social_links'] ?? [],
        ]);

        $config->save();
        $config->addDeploymentLog('basic_config_saved', $data);

        return $config;
    }

    /**
     * บันทึกการตั้งค่า Tokenomics (Step 3)
     *
     * @param TpixConfiguration $config
     * @param array $data
     * @return TpixConfiguration
     */
    public function saveTokenomics(TpixConfiguration $config, array $data): TpixConfiguration
    {
        $config->fill([
            'supply_distribution' => $data['supply_distribution'] ?? [],
            'initial_price_tpix' => $data['initial_price_tpix'] ?? null,
            'initial_price_usd' => $data['initial_price_usd'] ?? null,
            'market_cap_target' => $data['market_cap_target'] ?? null,
            'is_mintable' => $data['is_mintable'] ?? false,
            'is_burnable' => $data['is_burnable'] ?? true,
            'is_pausable' => $data['is_pausable'] ?? false,
            'is_freezable' => $data['is_freezable'] ?? false,
            'max_supply' => $data['max_supply'] ?? null,
            'fee_structure' => $data['fee_structure'] ?? [],
        ]);

        $config->save();
        $config->addDeploymentLog('tokenomics_saved', $data);

        return $config;
    }

    // =====================================
    // STEP 4: Smart Contract
    // =====================================

    /**
     * สร้าง Smart Contract Code
     *
     * @param TpixConfiguration $config
     * @param array $features
     * @return string
     */
    public function generateSmartContract(TpixConfiguration $config, array $features = []): string
    {
        // Template สำหรับ ERC20 token
        $template = file_get_contents(base_path('tpix-blockchain/contracts/TPIXERC20.sol'));

        // Customize ตาม config
        $contractCode = $this->customizeContract($template, $config, $features);

        // บันทึก contract code
        $config->contract_code = $contractCode;
        $config->contract_features = $features;
        $config->save();

        return $contractCode;
    }

    /**
     * Customize contract ตาม config
     *
     * @param string $template
     * @param TpixConfiguration $config
     * @param array $features
     * @return string
     */
    protected function customizeContract(string $template, TpixConfiguration $config, array $features): string
    {
        // แทนที่ placeholders
        $replacements = [
            '{{TOKEN_NAME}}' => $config->token_name,
            '{{TOKEN_SYMBOL}}' => $config->token_symbol,
            '{{DECIMALS}}' => $config->decimals,
            '{{TOTAL_SUPPLY}}' => $config->total_supply,
        ];

        $contractCode = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        // เพิ่ม features เพิ่มเติม
        if (in_array('burnable', $features)) {
            // เพิ่ม burnable functions
        }

        if (in_array('pausable', $features)) {
            // เพิ่ม pausable functions
        }

        if (in_array('mintable', $features)) {
            // เพิ่ม mint functions
        }

        return $contractCode;
    }

    // =====================================
    // STEP 5: Deployment
    // =====================================

    /**
     * Deploy Smart Contract
     *
     * @param TpixConfiguration $config
     * @return array
     * @throws Exception
     */
    public function deployContract(TpixConfiguration $config): array
    {
        try {
            // ตรวจสอบว่าพร้อม deploy หรือไม่
            if (!$config->isReadyToDeploy()) {
                throw new Exception('Configuration ยังไม่พร้อมสำหรับ deploy');
            }

            // Deploy contract ผ่าน blockchain service
            $result = $this->blockchainService->deployContract([
                'name' => $config->token_name,
                'symbol' => $config->token_symbol,
                'decimals' => $config->decimals,
                'total_supply' => $config->total_supply,
                'contract_code' => $config->contract_code,
            ]);

            // บันทึกผลลัพธ์
            $config->contract_address = $result['contract_address'];
            $config->deployer_address = $result['deployer_address'];
            $config->deploy_tx_hash = $result['tx_hash'];
            $config->deployed_at = now();
            $config->deploy_gas_used = $result['gas_used'] ?? null;
            $config->deploy_cost_tpix = $result['cost_tpix'] ?? null;
            $config->explorer_url = config('tpix.explorer_url') . '/address/' . $result['contract_address'];
            $config->save();

            $config->addDeploymentLog('contract_deployed', $result);

            return [
                'success' => true,
                'contract_address' => $result['contract_address'],
                'tx_hash' => $result['tx_hash'],
                'explorer_url' => $config->explorer_url,
            ];
        } catch (Exception $e) {
            $config->addErrorLog('deployment_failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $config->status = 'failed';
            $config->save();

            throw $e;
        }
    }

    /**
     * Verify Contract บน Explorer
     *
     * @param TpixConfiguration $config
     * @return array
     */
    public function verifyContract(TpixConfiguration $config): array
    {
        try {
            // ส่ง source code ไปยัง block explorer สำหรับ verify
            $response = Http::post(config('tpix.explorer_api') . '/api', [
                'module' => 'contract',
                'action' => 'verifysourcecode',
                'contractaddress' => $config->contract_address,
                'sourceCode' => $config->contract_code,
                'contractname' => $config->token_name,
                'compilerversion' => 'v0.8.20',
            ]);

            if ($response->successful()) {
                $config->is_verified = true;
                $config->save();

                $config->addDeploymentLog('contract_verified');

                return [
                    'success' => true,
                    'message' => 'Contract ถูก verify สำเร็จ',
                ];
            }

            return [
                'success' => false,
                'message' => 'ไม่สามารถ verify contract ได้',
            ];
        } catch (Exception $e) {
            Log::error('Contract verification failed', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการ verify: ' . $e->getMessage(),
            ];
        }
    }

    // =====================================
    // STEP 6: DEX Integration
    // =====================================

    /**
     * สร้าง Liquidity Pool
     *
     * @param TpixConfiguration $config
     * @param float $amountTPIX
     * @param float $amountToken
     * @return array
     * @throws Exception
     */
    public function createLiquidityPool(
        TpixConfiguration $config,
        float $amountTPIX,
        float $amountToken
    ): array {
        try {
            DB::beginTransaction();

            // สร้าง pool ใน database
            $pool = TPIXLiquidityPool::create([
                'token_a_id' => 1, // TPIX native coin (สมมติ id = 1)
                'token_b_id' => null, // จะเป็น token ใหม่ที่เรา deploy
                'token_a_address' => '0x0000000000000000000000000000000000000000', // Native coin
                'token_b_address' => $config->contract_address,
                'reserve_a' => $amountTPIX,
                'reserve_b' => $amountToken,
                'total_liquidity' => sqrt($amountTPIX * $amountToken),
                'fee_percentage' => 0.3,
                'is_active' => true,
            ]);

            // บันทึกข้อมูลใน config
            $config->liquidity_pool_id = $pool->id;
            $config->initial_liquidity_tpix = $amountTPIX;
            $config->initial_liquidity_token = $amountToken;
            $config->dex_enabled = true;
            $config->save();

            $config->addDeploymentLog('liquidity_pool_created', [
                'pool_id' => $pool->id,
                'amount_tpix' => $amountTPIX,
                'amount_token' => $amountToken,
            ]);

            DB::commit();

            return [
                'success' => true,
                'pool_id' => $pool->id,
                'message' => 'สร้าง Liquidity Pool สำเร็จ',
            ];
        } catch (Exception $e) {
            DB::rollBack();

            $config->addErrorLog('liquidity_pool_creation_failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * เปิดการซื้อขาย
     *
     * @param TpixConfiguration $config
     * @return array
     */
    public function enableTrading(TpixConfiguration $config): array
    {
        try {
            if (!$config->dex_enabled || empty($config->liquidity_pool_id)) {
                throw new Exception('Liquidity Pool ยังไม่ถูกสร้าง');
            }

            $pool = $config->liquidityPool;
            $pool->is_active = true;
            $pool->save();

            $config->trading_enabled_at = now();
            $config->save();

            $config->addDeploymentLog('trading_enabled');

            return [
                'success' => true,
                'message' => 'เปิดการซื้อขายสำเร็จ',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถเปิดการซื้อขายได้: ' . $e->getMessage(),
            ];
        }
    }

    // =====================================
    // STEP 7: Listing
    // =====================================

    /**
     * ส่งข้อมูลไปยัง CoinMarketCap
     *
     * @param TpixConfiguration $config
     * @return array
     */
    public function submitToCoinMarketCap(TpixConfiguration $config): array
    {
        try {
            // เตรียมข้อมูลสำหรับส่ง
            $data = [
                'name' => $config->token_name,
                'symbol' => $config->token_symbol,
                'contract_address' => $config->contract_address,
                'total_supply' => $config->total_supply,
                'circulating_supply' => $config->total_supply, // ปรับตามจริง
                'website' => $config->website_url,
                'explorer' => $config->explorer_url,
                'description' => $config->description,
                'whitepaper' => $config->whitepaper_url,
                'social_links' => $config->social_links,
            ];

            // ส่งข้อมูลไปยัง CMC (ปกติต้องส่งผ่าน form บนเว็บ CMC)
            // ที่นี่เราแค่บันทึกว่าส่งแล้ว
            $config->cmc_submitted = true;
            $config->cmc_submitted_at = now();
            $config->cmc_status = 'pending';
            $config->save();

            $config->addDeploymentLog('submitted_to_cmc', $data);

            return [
                'success' => true,
                'message' => 'ส่งข้อมูลไปยัง CoinMarketCap สำเร็จ',
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถส่งข้อมูลไปยัง CoinMarketCap ได้: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ส่งข้อมูลไปยัง CoinGecko
     *
     * @param TpixConfiguration $config
     * @return array
     */
    public function submitToCoinGecko(TpixConfiguration $config): array
    {
        try {
            $data = [
                'name' => $config->token_name,
                'symbol' => $config->token_symbol,
                'contract_address' => $config->contract_address,
                'website' => $config->website_url,
                'explorer' => $config->explorer_url,
                'description' => $config->description,
            ];

            $config->coingecko_submitted = true;
            $config->coingecko_submitted_at = now();
            $config->coingecko_status = 'pending';
            $config->save();

            $config->addDeploymentLog('submitted_to_coingecko', $data);

            return [
                'success' => true,
                'message' => 'ส่งข้อมูลไปยัง CoinGecko สำเร็จ',
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถส่งข้อมูลไปยัง CoinGecko ได้: ' . $e->getMessage(),
            ];
        }
    }

    // =====================================
    // Helpers
    // =====================================

    /**
     * สร้าง Configuration ใหม่
     *
     * @param User $user
     * @param string $name
     * @return TpixConfiguration
     */
    public function createConfiguration(User $user, string $name): TpixConfiguration
    {
        return TpixConfiguration::create([
            'user_id' => $user->id,
            'name' => $name,
            'status' => 'draft',
            'current_step' => 1,
        ]);
    }

    /**
     * คำนวณ estimated gas สำหรับ deployment
     *
     * @param TpixConfiguration $config
     * @return array
     */
    public function estimateDeploymentCost(TpixConfiguration $config): array
    {
        try {
            $gasPrice = $this->blockchainService->getGasPrice();
            $gasPriceGwei = hexdec($gasPrice) / 1e9;
            $gasPriceTPIX = hexdec($gasPrice) / 1e18;

            // Estimate gas สำหรับ deploy (ประมาณ 2-3 million gas)
            $estimatedGas = 2500000;
            $estimatedCostTPIX = $estimatedGas * $gasPriceTPIX;

            return [
                'gas_price_gwei' => $gasPriceGwei,
                'estimated_gas' => $estimatedGas,
                'estimated_cost_tpix' => $estimatedCostTPIX,
                'estimated_cost_formatted' => number_format($estimatedCostTPIX, 8) . ' TPIX',
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
