<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TPIXToken;
use App\Models\TPIXTokenBalance;
use App\Services\TPIX\TokenFactoryService;
use App\Services\TPIX\TokenWalletIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * User Token Controller
 */
class TokenController extends Controller
{
    /**
     * สถานะเหรียญที่สมาชิกทั่วไปเปิดดูได้ (สถานะอื่นเห็นเฉพาะผู้สร้าง)
     */
    public const PUBLIC_STATUSES = ['deployed', 'verified', 'active', 'paused'];

    protected TokenFactoryService $tokenFactory;

    protected TokenWalletIntegrationService $walletIntegration;

    public function __construct(
        TokenFactoryService $tokenFactory,
        TokenWalletIntegrationService $walletIntegration
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->walletIntegration = $walletIntegration;
        $this->middleware('auth');
    }

    /**
     * Token Marketplace
     */
    public function index(Request $request)
    {
        $query = TPIXToken::where('status', 'active')
            ->where('is_listed', true);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        if ($request->filled('verified')) {
            $query->where('is_verified', true);
        }

        // Sort
        $sort = $request->get('sort', 'market_cap');
        switch ($sort) {
            case 'price':
                $query->orderBy('current_price_tpix', 'desc');
                break;
            case 'volume':
                $query->orderBy('volume_24h_tpix', 'desc');
                break;
            case 'holders':
                $query->orderBy('holders_count', 'desc');
                break;
            default:
                $query->orderBy('market_cap_tpix', 'desc');
        }

        $tokens = $query->paginate(20);

        // Featured tokens
        $featuredTokens = TPIXToken::where('is_featured', true)
            ->where('status', 'active')
            ->limit(5)
            ->get();

        // เหรียญที่สมาชิกสร้างเอง (ทุกสถานะ) — เดิมสร้างแล้วหาไม่เจอ เพราะตลาดแสดงเฉพาะเหรียญ active ที่ลิสต์แล้ว
        $myTokens = TPIXToken::where('creator_id', Auth::id())
            ->latest()
            ->limit(20)
            ->get();

        return view('user.tokens.index', compact('tokens', 'featuredTokens', 'myTokens'));
    }

    /**
     * Token Details
     */
    public function show($id)
    {
        $token = TPIXToken::with('creator:id,name')->findOrFail($id);

        $user = Auth::user();

        // เหรียญที่ยังไม่ขึ้นเชน/ถูกระงับ ให้เห็นเฉพาะผู้สร้าง — เดิมใครเดา id ก็เปิดดูร่างของคนอื่นได้
        if (! in_array($token->status, self::PUBLIC_STATUSES, true) && (int) $token->creator_id !== (int) $user->id) {
            abort(404);
        }

        // User's balance of this token
        $myBalance = TPIXTokenBalance::where('token_id', $token->id)
            ->where('user_id', $user->id)
            ->first();

        // Recent transfers
        $recentTransfers = $token->transfers()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Top holders
        $topHolders = $token->balances()
            ->where('balance', '>', 0)
            ->orderBy('balance', 'desc')
            ->limit(10)
            ->get();

        return view('user.tokens.show', compact('token', 'myBalance', 'recentTransfers', 'topHolders'));
    }

    /**
     * Tutorial สอนการสร้างเหรียญลูก (Custom Token)
     *
     * หน้าคู่มือสอนขั้นตอนการสร้างเหรียญของตัวเองบน TPIX Blockchain
     * สำหรับผู้ใช้ที่ต้องการมีเหรียญ ERC-20 ของตัวเอง
     *
     * @return \Illuminate\View\View
     */
    public function tutorial()
    {
        return view('user.tokens.tutorial');
    }

    /**
     * Create Token Page
     */
    public function create()
    {
        // Estimate deployment cost
        $estimatedCost = $this->tokenFactory->estimateDeploymentCost();

        return view('user.tokens.create', compact('estimatedCost'));
    }

    /**
     * Store New Token
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'symbol' => 'required|string|max:20|alpha_num',
            'total_supply' => 'required|numeric|min:1',
            'decimals' => 'required|integer|min:0|max:18',
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'whitepaper_url' => 'nullable|url|max:500',
            'github' => 'nullable|url|max:255',
            'twitter' => 'nullable|string|max:255',
            'telegram' => 'nullable|url|max:255',
            // คอลัมน์เป็น decimal(20,8) — เกินนี้ MySQL ปฏิเสธแล้วสมาชิกเห็นแค่ข้อความรวม
            'initial_price_tpix' => 'nullable|numeric|min:0|max:999999999999',
            'is_mintable' => 'boolean',
            'is_burnable' => 'boolean',
            'is_pausable' => 'boolean',
            'is_freezable' => 'boolean',
        ], [
            'name.required' => 'กรุณากรอกชื่อเหรียญ',
            'symbol.required' => 'กรุณากรอกสัญลักษณ์เหรียญ',
            'symbol.alpha_num' => 'สัญลักษณ์ใช้ได้เฉพาะตัวอักษรอังกฤษและตัวเลข',
            'total_supply.required' => 'กรุณากรอกจำนวนเหรียญทั้งหมด',
            'total_supply.min' => 'จำนวนเหรียญทั้งหมดต้องอย่างน้อย 1',
            'initial_price_tpix.numeric' => 'ราคาเริ่มต้นต้องเป็นตัวเลข',
            'initial_price_tpix.min' => 'ราคาเริ่มต้นต้องไม่ติดลบ',
            'initial_price_tpix.max' => 'ราคาเริ่มต้นสูงสุดไม่เกิน 999,999,999,999 TPIX',
            'url' => 'ลิงก์ไม่ถูกต้อง ต้องขึ้นต้นด้วย https://',
        ]);

        // ส่งเฉพาะฟิลด์ที่ตรวจแล้ว — เดิมส่ง $request->all() ทำให้แนบ referred_by / kyc_required ฯลฯ มาเองได้
        $data = $validated;
        foreach (['is_mintable', 'is_burnable', 'is_pausable', 'is_freezable'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        try {
            $token = $this->tokenFactory->createToken(Auth::user(), $data);

            return redirect()->route('user.tokens.show', $token->id)
                ->with('success', 'บันทึกเหรียญแล้ว เหรียญอยู่ในสถานะร่าง รอทีมงานตรวจสอบก่อนนำขึ้นเครือข่าย');
        } catch (\Exception $e) {
            Log::error('User token create failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'สร้างเหรียญไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * Deploy Token to Blockchain
     */
    public function deploy($id)
    {
        $token = TPIXToken::where('id', $id)
            ->where('creator_id', Auth::id())
            ->firstOrFail();

        if ($token->status !== 'draft') {
            return redirect()->back()->with('error', 'เหรียญนี้ไม่ได้อยู่ในสถานะร่าง');
        }

        try {
            $result = $this->tokenFactory->deployToken($token);

            return redirect()->route('user.tokens.show', $token->id)
                ->with('success', 'นำเหรียญขึ้นเครือข่ายแล้ว สัญญา: '.$result['contract_address']);
        } catch (\Exception $e) {
            Log::error('User token deploy failed', ['token_id' => $token->id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'นำเหรียญขึ้นเครือข่ายไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * Transfer Token to Another User
     */
    public function transfer(Request $request, $id)
    {
        $request->validate([
            'to_address_or_email' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $token = TPIXToken::findOrFail($id);

        try {
            $result = $this->walletIntegration->transferToken(
                $token,
                $user,
                $request->to_address_or_email,
                $request->amount
            );

            return redirect()->back()->with('success', 'โอนเหรียญสำเร็จ TX: '.$result['tx_hash']);
        } catch (\Exception $e) {
            Log::warning('User token transfer failed', ['token_id' => $token->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'โอนเหรียญไม่สำเร็จ กรุณาตรวจสอบผู้รับและยอดคงเหลือแล้วลองใหม่');
        }
    }

    /**
     * ประวัติธุรกรรมของเหรียญ — อีเมลแจ้งรับเหรียญลิงก์มาที่นี่ (เดิม route ชี้ method ที่ไม่มี = 500)
     * พาไปส่วนธุรกรรมล่าสุดในหน้ารายละเอียด ซึ่งตรวจสิทธิ์การมองเห็นให้แล้ว
     */
    public function transactions($id)
    {
        return redirect()->to(route('user.tokens.show', $id).'#transfers');
    }
}
