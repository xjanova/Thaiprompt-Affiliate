<?php

namespace App\Http\Controllers\Admin\LazadaHub;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplacePlatform;
use App\Services\Marketplace\LazadaApiService;
use App\Services\Marketplace\MarketplaceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lazada Hub — การเชื่อมต่อ (กรอก/จัดการ API credential)
 *
 * รองรับ 2 ชนิดโปรแกรม (เก็บในตาราง marketplace_accounts เดิม):
 *  - seller    = Lazada Open Platform (app_key/app_secret/access_token) → ดึงสินค้า/ราคา/สต็อก/ออเดอร์
 *  - affiliate = ระบบพันธมิตร เช่น Involve Asia (api_key/secret/sub_id) → ลิงก์ affiliate + ค่าคอม
 *
 * หมายเหตุ: คอลัมน์ app_key/app_secret เป็น NOT NULL → ใช้เป็น "คู่คีย์หลัก" ของทั้งสองชนิด
 *   (affiliate: app_key=API key, app_secret=Secret, sub_id เก็บใน additional_credentials)
 * credential ถูกเข้ารหัสอัตโนมัติโดย accessor ใน MarketplaceAccount (Crypt)
 */
class ConnectionController extends Controller
{
    /** ลิงก์เอกสาร API ของแต่ละชนิด (โชว์เป็นคำแนะนำ) */
    private const DOC_LINKS = [
        'seller' => 'https://open.lazada.com/apps/doc/api?path=%2Fauth%2Ftoken%2Fcreate',
        'affiliate' => 'https://help.involve.asia/hc/en-us/articles/42549337855001-Lazada-Product-Link-Generation',
    ];

    /**
     * รายการการเชื่อมต่อ Lazada ทั้งหมด
     */
    public function index()
    {
        $platform = $this->ensureLazadaPlatform();

        $accounts = MarketplaceAccount::where('platform_id', $platform->id)
            ->latest()
            ->get();

        return view('admin.lazada-hub.connections.index', [
            'pageTitle' => 'การเชื่อมต่อ Lazada',
            'platform' => $platform,
            'accounts' => $accounts,
            'docLinks' => self::DOC_LINKS,
            // Callback URL ที่ต้องเอาไปกรอกในแอป Lazada (ตอนลงทะเบียน OAuth)
            'callbackUrl' => route('admin.lazada-hub.connections.callback'),
        ]);
    }

    /**
     * ฟอร์มเพิ่มการเชื่อมต่อใหม่
     */
    public function create()
    {
        return view('admin.lazada-hub.connections.form', [
            'pageTitle' => 'เพิ่มการเชื่อมต่อ Lazada',
            'account' => null,
            'docLinks' => self::DOC_LINKS,
        ]);
    }

    /**
     * บันทึกการเชื่อมต่อใหม่
     */
    public function store(Request $request)
    {
        $data = $this->validateInput($request, isCreate: true);
        $platform = $this->ensureLazadaPlatform();

        $account = new MarketplaceAccount;
        $account->user_id = auth()->id();
        $account->platform_id = $platform->id;
        $account->status = 'inactive'; // ยังไม่ทดสอบ → inactive (enum: active/inactive/error/expired)

        $this->fillAccount($account, $data, isCreate: true);
        $account->save();

        return redirect()
            ->route('admin.lazada-hub.connections.index')
            ->with('success', 'เพิ่มการเชื่อมต่อสำเร็จ — บัญชี Seller กดปุ่ม "เชื่อมต่อ" (OAuth) เพื่อรับ Access Token ก่อน แล้วค่อยกด "ทดสอบ"');
    }

    /**
     * ฟอร์มแก้ไขการเชื่อมต่อ
     */
    public function edit(MarketplaceAccount $account)
    {
        $this->authorizeLazada($account);

        return view('admin.lazada-hub.connections.form', [
            'pageTitle' => 'แก้ไขการเชื่อมต่อ Lazada',
            'account' => $account,
            'docLinks' => self::DOC_LINKS,
        ]);
    }

    /**
     * อัพเดทการเชื่อมต่อ — เว้นช่อง credential ว่างไว้ = ใช้ค่าเดิม (ไม่ทับ)
     */
    public function update(Request $request, MarketplaceAccount $account)
    {
        $this->authorizeLazada($account);

        $data = $this->validateInput($request, isCreate: false);

        $this->fillAccount($account, $data, isCreate: false);

        // เพิ่งแก้ credential → ล้างสถานะ error เก่า (กันโชว์ "มีปัญหา" + ข้อความเดิมค้างทั้งที่แก้ถูกแล้ว)
        if ($account->status === 'error') {
            $account->status = 'inactive';
            $account->last_error = null;
        }
        $account->save();

        return redirect()
            ->route('admin.lazada-hub.connections.index')
            ->with('success', 'อัพเดทการเชื่อมต่อสำเร็จ — กดทดสอบอีกครั้งเพื่อยืนยัน');
    }

    /**
     * ลบการเชื่อมต่อ
     */
    public function destroy(MarketplaceAccount $account)
    {
        $this->authorizeLazada($account);

        $account->delete();

        return redirect()
            ->route('admin.lazada-hub.connections.index')
            ->with('success', 'ลบการเชื่อมต่อแล้ว');
    }

    /**
     * ทดสอบการเชื่อมต่อ (เรียก API จริง)
     */
    public function test(MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeLazada($account);

        // โปรแกรม affiliate (Involve Asia) ยังไม่มี endpoint ทดสอบในเฟสนี้
        if ($account->isAffiliateProgram()) {
            return response()->json([
                'success' => false,
                'message' => 'การทดสอบบัญชี Affiliate (Involve Asia) จะเปิดใช้งานใน Phase ถัดไป',
            ]);
        }

        // ยังไม่มี Access Token (เพิ่งกรอก key/secret ยังไม่ได้ทำ OAuth) → /seller/get จะ fail แน่
        // ไม่ตั้ง status=error (credential อาจถูก แค่ต้อง authorize ก่อน) — ชี้ทางให้กด "เชื่อมต่อ"
        if (empty($account->access_token)) {
            return response()->json([
                'success' => false,
                'message' => 'บัญชีนี้ยังไม่มี Access Token — กดปุ่ม "เชื่อมต่อ" เพื่อรับ token ผ่าน OAuth ก่อน แล้วค่อยกดทดสอบนะคะ',
            ]);
        }

        try {
            $account->loadMissing('platform');
            $service = MarketplaceFactory::create($account);
            $ok = $service->testConnection();

            if ($ok) {
                return response()->json([
                    'success' => true,
                    'message' => 'เชื่อมต่อสำเร็จ ✓ บัญชีพร้อมใช้งาน',
                ]);
            }

            $account->update(['status' => 'error', 'last_error' => 'ทดสอบไม่ผ่าน (ตรวจ key/token)']);

            return response()->json([
                'success' => false,
                'message' => 'เชื่อมต่อไม่สำเร็จ — ตรวจสอบ App Key / Secret / Access Token อีกครั้ง',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Lazada Hub: ทดสอบการเชื่อมต่อล้มเหลว', ['account_id' => $account->id, 'error' => $e->getMessage()]);
            $account->update(['status' => 'error', 'last_error' => mb_substr($e->getMessage(), 0, 1000)]);

            return response()->json([
                'success' => false,
                'message' => 'เชื่อมต่อไม่สำเร็จ ระบบขัดข้องหรือคีย์ไม่ถูกต้อง ลองใหม่อีกครั้งนะคะ',
            ], 200);
        }
    }

    /**
     * เริ่ม OAuth — พาไปหน้าอนุญาตของ Lazada (Seller เท่านั้น)
     *
     * ต้องกรอก App Key/App Secret ในบัญชีก่อน แล้วกดปุ่มนี้
     */
    public function connect(MarketplaceAccount $account)
    {
        $this->authorizeLazada($account);

        if ($account->isAffiliateProgram()) {
            return redirect()
                ->route('admin.lazada-hub.connections.index')
                ->with('error', 'OAuth ใช้กับบัญชี Seller (Open Platform) เท่านั้น');
        }

        if (empty($account->app_key)) {
            return redirect()
                ->route('admin.lazada-hub.connections.edit', $account)
                ->with('error', 'กรุณากรอก App Key / App Secret ก่อนเริ่มเชื่อมต่อ');
        }

        // state กัน CSRF — เก็บแบบ map (state => account_id) รองรับเชื่อมหลายบัญชี/หลายแท็บพร้อมกัน
        $state = Str::random(40);
        $map = session('lazada_oauth', []);
        if (! is_array($map)) {
            $map = [];
        }
        $map[$state] = $account->id;
        if (count($map) > 8) {            // เก็บไม่เกิน 8 flow ล่าสุด กัน session บวม
            $map = array_slice($map, -8, null, true);
        }
        session(['lazada_oauth' => $map]);

        $url = LazadaApiService::buildAuthorizationUrl(
            $account->app_key,
            route('admin.lazada-hub.connections.callback'),
            $state
        );

        return redirect()->away($url);
    }

    /**
     * รับ callback จาก Lazada (?code=...&state=...) → แลกเป็น access_token
     */
    public function callback(Request $request)
    {
        $index = route('admin.lazada-hub.connections.index');

        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');
        // 🩺 Lazada เด้งกลับพร้อม error แทน code (callback URL ไม่ตรง / ผู้ใช้ปฏิเสธ / แอปไม่มีสิทธิ์ / region ไม่ตรง)
        //    เดิมกลืนเป็น "ถูกยกเลิก" หมด → มองไม่เห็นเหตุจริง. เก็บไว้โชว์/log ให้ debug ได้
        $oauthError = trim((string) ($request->query('error_description') ?: $request->query('error') ?: ''));

        $map = session('lazada_oauth', []);
        $accountId = is_array($map) ? ($map[$state] ?? null) : null;
        if ($accountId !== null) {
            unset($map[$state]);                  // state ใช้ครั้งเดียว (one-time)
            session(['lazada_oauth' => $map]);
        }

        if ($code === '') {
            // เก็บเหตุจริงจาก Lazada ลงบัญชี (ถ้า resolve ได้) ให้แอดมินเห็นในรายการเชื่อมต่อ
            if ($oauthError !== '' && $accountId && ($errAcc = MarketplaceAccount::find($accountId))) {
                $this->authorizeLazada($errAcc);
                $errAcc->update(['status' => 'error', 'last_error' => 'Lazada ปฏิเสธ OAuth: '.mb_substr($oauthError, 0, 800)]);
            }
            if ($oauthError !== '') {
                Log::warning('Lazada Hub: OAuth authorize ส่ง error', ['account_id' => $accountId, 'error' => $oauthError]);

                return redirect($index)->with('error', 'เชื่อมต่อไม่สำเร็จ: '.Str::limit($oauthError, 160).' — ตรวจให้ Callback URL ในแอป Lazada ตรงกับด้านบนเป๊ะ แล้วลองใหม่');
            }

            return redirect($index)->with('error', 'การเชื่อมต่อถูกยกเลิก (Lazada ไม่ได้ส่ง code กลับมา) — ตรวจ Callback URL ในแอปให้ตรงเป๊ะ แล้วกด "เชื่อมต่อ" ใหม่');
        }

        if (! $accountId) {
            return redirect($index)->with('error', 'เซสชันเชื่อมต่อหมดอายุ (state ไม่ตรง) — กดปุ่ม "เชื่อมต่อ" ใหม่จากหน้านี้อีกครั้ง (อย่าเปิดลิงก์ค้างข้ามนาน)');
        }

        $account = MarketplaceAccount::find($accountId);
        if (! $account) {
            return redirect($index)->with('error', 'ไม่พบบัญชีที่จะเชื่อมต่อ');
        }
        $this->authorizeLazada($account);

        try {
            $account->loadMissing('platform');
            $service = MarketplaceFactory::create($account);

            $token = method_exists($service, 'createTokenFromCode')
                ? $service->createTokenFromCode($code)
                : null;

            if (! is_array($token) || empty($token['access_token'])) {
                // 🩺 เก็บเหตุจริงจาก Lazada (type/code/message) — หน้านี้แอดมินเท่านั้น แสดงเหตุได้เพื่อ debug
                //    (เช่น IllegalSignature=secret ผิด, invalid code=callback ช้า/ใช้ซ้ำ, ApiCallLimit ฯลฯ)
                $reason = is_array($token)
                    ? trim(((string) ($token['type'] ?? '')).' '.((string) ($token['code'] ?? '')).' '.((string) ($token['message'] ?? '')))
                    : 'ไม่มีการตอบกลับจาก Lazada (transport ล้มเหลว)';
                $reason = $reason !== '' ? $reason : 'แลก token ไม่สำเร็จ';
                Log::warning('Lazada Hub: createTokenFromCode ไม่สำเร็จ', ['account_id' => $account->id, 'reason' => $reason]);
                $account->update(['status' => 'error', 'last_error' => mb_substr($reason, 0, 1000)]);

                return redirect($index)->with('error', 'เชื่อมต่อไม่สำเร็จ: '.Str::limit($reason, 160).' — ตรวจ App Key/Secret + Callback URL ในแอป Lazada แล้วลองใหม่');
            }

            $account->access_token = $token['access_token'];
            if (! empty($token['refresh_token'])) {
                $account->refresh_token = $token['refresh_token'];
            }
            $account->token_expires_at = now()->addSeconds((int) ($token['expires_in'] ?? 3600));
            if (! empty($token['account']) && is_string($token['account'])) {
                $account->shop_name = $token['account'];
            }
            $account->status = 'active';
            $account->last_error = null;
            $account->save();

            return redirect($index)->with('success', 'เชื่อมต่อ Lazada สำเร็จ ✓ ดึง Access Token แล้ว');
        } catch (\Throwable $e) {
            Log::error('Lazada Hub: callback ล้มเหลว', ['account_id' => $account->id, 'error' => $e->getMessage()]);

            return redirect($index)->with('error', 'เชื่อมต่อไม่สำเร็จ ระบบขัดข้องชั่วคราว ลองใหม่อีกครั้งนะคะ');
        }
    }

    // ════════════════════════════════════════════════════════════
    //  Helpers
    // ════════════════════════════════════════════════════════════

    /**
     * ตรวจ + เตรียมข้อมูลจากฟอร์ม
     */
    private function validateInput(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'account_name' => ['required', 'string', 'max:100'],
            'program_type' => ['required', 'in:seller,affiliate'],
            // คู่คีย์หลัก: บังคับตอนสร้าง, ตอนแก้ไขเว้นว่าง = คงค่าเดิม
            'app_key' => [$isCreate ? 'required' : 'nullable', 'string', 'max:500'],
            'app_secret' => [$isCreate ? 'required' : 'nullable', 'string', 'max:1000'],
            'access_token' => ['nullable', 'string', 'max:2000'],
            'refresh_token' => ['nullable', 'string', 'max:2000'],
            'shop_id' => ['nullable', 'string', 'max:100'],
            'sub_id' => ['nullable', 'string', 'max:100'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'auto_sync_products' => ['nullable', 'boolean'],
            'auto_sync_orders' => ['nullable', 'boolean'],
            'sync_frequency' => ['nullable', 'in:hourly,daily,weekly'],
        ]);
    }

    /**
     * เซ็ตค่าลง account (จัดการ credential ว่าง + additional_credentials)
     */
    private function fillAccount(MarketplaceAccount $account, array $data, bool $isCreate): void
    {
        $account->program_type = $data['program_type'];
        $account->account_name = $data['account_name'];
        $account->shop_id = $data['shop_id'] ?? $account->shop_id;
        $account->commission_rate = $data['commission_rate'] ?? 0;
        $account->auto_sync_products = (bool) ($data['auto_sync_products'] ?? false);
        $account->auto_sync_orders = (bool) ($data['auto_sync_orders'] ?? false);
        $account->sync_frequency = $data['sync_frequency'] ?? 'daily';

        // คู่คีย์หลัก — เซ็ตเฉพาะเมื่อกรอกมา (เว้นว่าง = คงค่าเดิม)
        if (! empty($data['app_key'])) {
            $account->app_key = $data['app_key'];
        }
        if (! empty($data['app_secret'])) {
            $account->app_secret = $data['app_secret'];
        }
        if (! empty($data['access_token'])) {
            $account->access_token = $data['access_token'];
        }
        if (! empty($data['refresh_token'])) {
            $account->refresh_token = $data['refresh_token'];
        }

        // เก็บ sub_id (affiliate tracking) ใน additional_credentials โดยไม่ลบคีย์อื่น
        $extra = is_array($account->additional_credentials) ? $account->additional_credentials : [];
        if (array_key_exists('sub_id', $data)) {
            $extra['sub_id'] = $data['sub_id'];
        }
        $account->additional_credentials = $extra;
    }

    /**
     * หา/สร้างแพลตฟอร์ม Lazada (เผื่อ seeder ยังไม่รัน)
     */
    private function ensureLazadaPlatform(): MarketplacePlatform
    {
        return MarketplacePlatform::firstOrCreate(
            ['slug' => 'lazada'],
            [
                'name' => 'Lazada',
                'api_documentation_url' => self::DOC_LINKS['seller'],
                'is_active' => true,
                'requires_app_key' => true,
                'requires_app_secret' => true,
                'requires_access_token' => true,
                'requires_shop_id' => false,
                'default_commission_rate' => 5.00,
                'supports_product_sync' => true,
                'supports_order_sync' => true,
                'supports_real_time_webhook' => true,
            ]
        );
    }

    /**
     * กันแก้บัญชีของแพลตฟอร์มอื่นผ่าน route ของ Lazada Hub
     */
    private function authorizeLazada(MarketplaceAccount $account): void
    {
        $platform = $this->ensureLazadaPlatform();

        abort_unless($account->platform_id === $platform->id, 404);
    }
}
