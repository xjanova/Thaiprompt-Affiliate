<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TpixConfiguration;
use App\Services\TPIX\TpixDeploymentService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * TPIX Deployment Controller
 *
 * จัดการ TPIX Native Coin Deployment Wizard แบบ step-by-step
 *
 * @package App\Http\Controllers\Admin
 */
class TpixDeploymentController extends Controller
{
    /**
     * Deployment Service
     *
     * @var TpixDeploymentService
     */
    protected TpixDeploymentService $deploymentService;

    /**
     * Constructor
     *
     * @param TpixDeploymentService $deploymentService
     */
    public function __construct(TpixDeploymentService $deploymentService)
    {
        $this->deploymentService = $deploymentService;
        $this->middleware(['auth', 'admin']);
    }

    // =====================================
    // Index & Management
    // =====================================

    /**
     * แสดงรายการ configurations ทั้งหมด
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $query = TpixConfiguration::with('user')
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by step
        if ($request->filled('step')) {
            $query->where('current_step', $request->step);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('token_name', 'like', "%{$search}%")
                  ->orWhere('token_symbol', 'like', "%{$search}%")
                  ->orWhere('contract_address', 'like', "%{$search}%");
            });
        }

        $configurations = $query->paginate(20);

        // Statistics
        $stats = [
            'total' => TpixConfiguration::count(),
            'draft' => TpixConfiguration::draft()->count(),
            'deployed' => TpixConfiguration::deployed()->count(),
            'listed' => TpixConfiguration::listed()->count(),
        ];

        return view('admin.tpix.deployment.index', compact('configurations', 'stats'));
    }

    /**
     * สร้าง configuration ใหม่
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.tpix.deployment.create');
    }

    /**
     * บันทึก configuration ใหม่
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:tpix_configurations,name',
        ]);

        try {
            $config = $this->deploymentService->createConfiguration(
                auth()->user(),
                $request->name
            );

            return redirect()
                ->route('admin.tpix.deployment.wizard', $config->slug)
                ->with('success', 'สร้าง Configuration สำเร็จ! กรุณาทำตามขั้นตอน');
        } catch (Exception $e) {
            Log::error('Failed to create TPIX configuration', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()
                ->with('error', 'ไม่สามารถสร้าง Configuration ได้: ' . $e->getMessage())
                ->withInput();
        }
    }

    // =====================================
    // Wizard Navigation
    // =====================================

    /**
     * แสดง Wizard หลัก
     *
     * @param string $slug
     * @return View|RedirectResponse
     */
    public function wizard(string $slug)
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        // Redirect ไปยัง step ปัจจุบัน
        return redirect()->route("admin.tpix.deployment.step{$config->current_step}", $slug);
    }

    // =====================================
    // STEP 1: Prerequisites Check
    // =====================================

    /**
     * แสดงหน้า Step 1: Prerequisites Check
     *
     * @param string $slug
     * @return View
     */
    public function step1(string $slug): View
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        // ตรวจสอบ prerequisites
        $prerequisites = $this->deploymentService->checkPrerequisites();

        return view('admin.tpix.deployment.steps.step1', compact('config', 'prerequisites'));
    }

    /**
     * บันทึก Step 1 และไปต่อ
     *
     * @param Request $request
     * @param string $slug
     * @return RedirectResponse
     */
    public function saveStep1(Request $request, string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        try {
            // ตรวจสอบ prerequisites อีกครั้ง
            $prerequisites = $this->deploymentService->checkPrerequisites();

            if (!$prerequisites['all_passed']) {
                return back()->with('error', 'Prerequisites ยังไม่ผ่านทั้งหมด กรุณาแก้ไขปัญหาก่อน');
            }

            // บันทึก prerequisites results
            $config->prerequisites = $prerequisites;
            $config->save();

            // ไป step ถัดไป
            $config->nextStep();

            return redirect()
                ->route('admin.tpix.deployment.step2', $slug)
                ->with('success', 'ผ่าน Prerequisites Check แล้ว! ไปต่อที่ Step 2');
        } catch (Exception $e) {
            Log::error('Failed to save Step 1', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // =====================================
    // STEP 2: Token Configuration
    // =====================================

    /**
     * แสดงหน้า Step 2: Token Configuration
     *
     * @param string $slug
     * @return View
     */
    public function step2(string $slug): View
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        // Categories
        $categories = [
            'defi' => 'DeFi',
            'gamefi' => 'GameFi',
            'meme' => 'Meme',
            'utility' => 'Utility',
            'stablecoin' => 'Stablecoin',
            'nft' => 'NFT',
            'dao' => 'DAO',
            'other' => 'อื่นๆ',
        ];

        return view('admin.tpix.deployment.steps.step2', compact('config', 'categories'));
    }

    /**
     * บันทึก Step 2
     *
     * @param Request $request
     * @param string $slug
     * @return RedirectResponse
     */
    public function saveStep2(Request $request, string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'token_name' => 'required|string|min:3|max:100',
            'token_symbol' => 'required|string|min:2|max:20|uppercase',
            'total_supply' => 'required|numeric|min:1|max:1000000000000',
            'decimals' => 'required|integer|min:0|max:18',
            'description' => 'nullable|string|max:1000',
            'logo_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'category' => 'required|in:defi,gamefi,meme,utility,stablecoin,nft,dao,other',
            'social_links' => 'nullable|array',
            'social_links.twitter' => 'nullable|url',
            'social_links.telegram' => 'nullable|url',
            'social_links.discord' => 'nullable|url',
            'social_links.github' => 'nullable|url',
        ]);

        try {
            $this->deploymentService->saveBasicConfiguration($config, $validated);

            $config->nextStep();

            return redirect()
                ->route('admin.tpix.deployment.step3', $slug)
                ->with('success', 'บันทึก Token Configuration สำเร็จ!');
        } catch (Exception $e) {
            Log::error('Failed to save Step 2', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    // =====================================
    // STEP 3: Tokenomics
    // =====================================

    /**
     * แสดงหน้า Step 3: Tokenomics
     *
     * @param string $slug
     * @return View
     */
    public function step3(string $slug): View
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        return view('admin.tpix.deployment.steps.step3', compact('config'));
    }

    /**
     * บันทึก Step 3
     *
     * @param Request $request
     * @param string $slug
     * @return RedirectResponse
     */
    public function saveStep3(Request $request, string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'supply_distribution' => 'nullable|array',
            'initial_price_tpix' => 'nullable|numeric|min:0',
            'initial_price_usd' => 'nullable|numeric|min:0',
            'market_cap_target' => 'nullable|numeric|min:0',
            'is_mintable' => 'boolean',
            'is_burnable' => 'boolean',
            'is_pausable' => 'boolean',
            'is_freezable' => 'boolean',
            'max_supply' => 'nullable|numeric|min:0',
            'fee_structure' => 'nullable|array',
        ]);

        try {
            $this->deploymentService->saveTokenomics($config, $validated);

            $config->nextStep();

            return redirect()
                ->route('admin.tpix.deployment.step4', $slug)
                ->with('success', 'บันทึก Tokenomics สำเร็จ!');
        } catch (Exception $e) {
            Log::error('Failed to save Step 3', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    // =====================================
    // STEP 4: Smart Contract
    // =====================================

    /**
     * แสดงหน้า Step 4: Smart Contract
     *
     * @param string $slug
     * @return View
     */
    public function step4(string $slug): View
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        // Generate contract code ถ้ายังไม่มี
        if (empty($config->contract_code)) {
            $config->contract_code = $this->deploymentService->generateSmartContract($config);
        }

        // Available features
        $availableFeatures = [
            'burnable' => 'Burnable - สามารถเผาเหรียญได้',
            'mintable' => 'Mintable - สามารถสร้างเหรียญเพิ่มได้',
            'pausable' => 'Pausable - สามารถหยุดการทำงานชั่วคราวได้',
            'freezable' => 'Freezable - สามารถแช่แข็ง address ได้',
            'access_control' => 'Access Control - ระบบควบคุมการเข้าถึง',
            'snapshot' => 'Snapshot - บันทึกสถานะ balance',
        ];

        return view('admin.tpix.deployment.steps.step4', compact('config', 'availableFeatures'));
    }

    /**
     * บันทึก Step 4
     *
     * @param Request $request
     * @param string $slug
     * @return RedirectResponse
     */
    public function saveStep4(Request $request, string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'contract_features' => 'nullable|array',
            'contract_code' => 'required|string',
            'access_control' => 'required|in:owner,role_based,dao,multi_sig',
            'safety_features' => 'nullable|array',
            'is_upgradeable' => 'boolean',
        ]);

        try {
            $config->update($validated);

            $config->nextStep();

            return redirect()
                ->route('admin.tpix.deployment.step5', $slug)
                ->with('success', 'บันทึก Smart Contract Configuration สำเร็จ!');
        } catch (Exception $e) {
            Log::error('Failed to save Step 4', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    // =====================================
    // STEP 5: Deploy & Verify
    // =====================================

    /**
     * แสดงหน้า Step 5: Deploy & Verify
     *
     * @param string $slug
     * @return View
     */
    public function step5(string $slug): View
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        // ประมาณค่า gas
        $estimation = $this->deploymentService->estimateDeploymentCost($config);

        return view('admin.tpix.deployment.steps.step5', compact('config', 'estimation'));
    }

    /**
     * Deploy Contract
     *
     * @param Request $request
     * @param string $slug
     * @return RedirectResponse
     */
    public function deployContract(Request $request, string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        try {
            if ($config->isDeployed()) {
                return back()->with('warning', 'Contract ถูก deploy ไปแล้ว');
            }

            // Deploy contract
            $result = $this->deploymentService->deployContract($config);

            if ($result['success']) {
                $config->nextStep();

                return back()->with('success', 'Deploy Contract สำเร็จ! Contract Address: ' . $result['contract_address']);
            }

            return back()->with('error', 'Deploy ล้มเหลว');
        } catch (Exception $e) {
            Log::error('Failed to deploy contract', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Deploy ล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * Verify Contract
     *
     * @param string $slug
     * @return RedirectResponse
     */
    public function verifyContract(string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        try {
            if (!$config->isDeployed()) {
                return back()->with('error', 'กรุณา Deploy Contract ก่อน');
            }

            $result = $this->deploymentService->verifyContract($config);

            if ($result['success']) {
                return back()->with('success', 'Verify Contract สำเร็จ!');
            }

            return back()->with('warning', $result['message']);
        } catch (Exception $e) {
            Log::error('Failed to verify contract', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Verify ล้มเหลว: ' . $e->getMessage());
        }
    }

    // =====================================
    // STEP 6: DEX Integration
    // =====================================

    /**
     * แสดงหน้า Step 6: DEX Integration
     *
     * @param string $slug
     * @return View
     */
    public function step6(string $slug): View
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        return view('admin.tpix.deployment.steps.step6', compact('config'));
    }

    /**
     * สร้าง Liquidity Pool
     *
     * @param Request $request
     * @param string $slug
     * @return RedirectResponse
     */
    public function createLiquidityPool(Request $request, string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'initial_liquidity_tpix' => 'required|numeric|min:1',
            'initial_liquidity_token' => 'required|numeric|min:1',
            'liquidity_lock_days' => 'nullable|numeric|min:0',
        ]);

        try {
            if (!$config->isDeployed()) {
                return back()->with('error', 'กรุณา Deploy Contract ก่อน');
            }

            $result = $this->deploymentService->createLiquidityPool(
                $config,
                $validated['initial_liquidity_tpix'],
                $validated['initial_liquidity_token']
            );

            if ($result['success']) {
                return back()->with('success', 'สร้าง Liquidity Pool สำเร็จ!');
            }

            return back()->with('error', 'สร้าง Pool ล้มเหลว');
        } catch (Exception $e) {
            Log::error('Failed to create liquidity pool', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'สร้าง Pool ล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * เปิดการซื้อขาย
     *
     * @param string $slug
     * @return RedirectResponse
     */
    public function enableTrading(string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        try {
            $result = $this->deploymentService->enableTrading($config);

            if ($result['success']) {
                $config->nextStep();

                return redirect()
                    ->route('admin.tpix.deployment.step7', $slug)
                    ->with('success', 'เปิดการซื้อขายสำเร็จ! ไปต่อที่ Step 7');
            }

            return back()->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Failed to enable trading', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เปิดการซื้อขายล้มเหลว: ' . $e->getMessage());
        }
    }

    // =====================================
    // STEP 7: Listing & Marketing
    // =====================================

    /**
     * แสดงหน้า Step 7: Listing
     *
     * @param string $slug
     * @return View
     */
    public function step7(string $slug): View
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        return view('admin.tpix.deployment.steps.step7', compact('config'));
    }

    /**
     * ส่งไปยัง CoinMarketCap
     *
     * @param string $slug
     * @return RedirectResponse
     */
    public function submitToCMC(string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        try {
            $result = $this->deploymentService->submitToCoinMarketCap($config);

            if ($result['success']) {
                return back()->with('success', 'ส่งข้อมูลไปยัง CoinMarketCap สำเร็จ!');
            }

            return back()->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Failed to submit to CMC', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'ส่งข้อมูลล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * ส่งไปยัง CoinGecko
     *
     * @param string $slug
     * @return RedirectResponse
     */
    public function submitToCoinGecko(string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        try {
            $result = $this->deploymentService->submitToCoinGecko($config);

            if ($result['success']) {
                return back()->with('success', 'ส่งข้อมูลไปยัง CoinGecko สำเร็จ!');
            }

            return back()->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Failed to submit to CoinGecko', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'ส่งข้อมูลล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * เสร็จสิ้นกระบวนการ
     *
     * @param string $slug
     * @return RedirectResponse
     */
    public function complete(string $slug): RedirectResponse
    {
        $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

        try {
            $config->status = 'listed';
            $config->save();

            $config->addDeploymentLog('deployment_completed');

            return redirect()
                ->route('admin.tpix.deployment.index')
                ->with('success', 'ยินดีด้วย! คุณได้ทำการ Deploy TPIX Native Coin สำเร็จแล้ว!');
        } catch (Exception $e) {
            Log::error('Failed to complete deployment', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // =====================================
    // Utilities
    // =====================================

    /**
     * ลบ configuration
     *
     * @param string $slug
     * @return RedirectResponse
     */
    public function destroy(string $slug): RedirectResponse
    {
        try {
            $config = TpixConfiguration::where('slug', $slug)->firstOrFail();

            // ห้ามลบถ้า deploy แล้ว
            if ($config->isDeployed()) {
                return back()->with('error', 'ไม่สามารถลบ Configuration ที่ deploy แล้วได้');
            }

            $config->delete();

            return redirect()
                ->route('admin.tpix.deployment.index')
                ->with('success', 'ลบ Configuration สำเร็จ');
        } catch (Exception $e) {
            Log::error('Failed to delete configuration', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'ลบไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    /**
     * API: ตรวจสอบ prerequisites
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPrerequisitesApi()
    {
        try {
            $results = $this->deploymentService->checkPrerequisites();

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
