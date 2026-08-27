<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortunePage;
use App\Models\FortuneReading;
use App\Services\Fortune\FortunePageContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * 🏬 จัดการสาขา (เพจแม่หมอ) — ระบบหลายเพจ
 *
 * เจ้าของสั่ง (2026-08-10): "ต้องการสร้างเพจแม่หมอดูดวงเพิ่มหลายๆ เพจ แบบเป็นระบบสาขา
 *                            และหลังบ้านต้องรู้ว่าบิลนี้มาจากเพจไหน ลูกค้าเพจไหน"
 *
 * ⚠️ หลักที่ห้ามพัง:
 *   - ค่าที่ไม่ได้กรอกในสาขา = ใช้ค่ากลางจาก fortune_telling_settings (ห้ามก็อปค่ามาเก็บซ้ำ)
 *   - แก้ข้อมูลสาขาเสร็จต้องล้าง memo ทันที ไม่งั้น worker ถือ token เก่าไปอีกหลายวินาที
 */
class FortunePagesController extends Controller
{
    /**
     * สิทธิ์ที่ User Token ต้องมี ไม่งั้นระบบสาขาทำงานไม่ครบ
     *
     * ขาด pages_show_list → กด "ดึงเพจทั้งหมด" ได้ 0 เพจ แต่ token ผ่าน /me ปกติ
     * (อาการนี้หลอกมาก — เห็น "เชื่อมบัญชีสำเร็จ" แล้วนึกว่าเรียบร้อย)
     */
    protected const REQUIRED_USER_SCOPES = [
        'pages_show_list',
        'pages_messaging',
        'pages_manage_metadata',
        'pages_read_engagement',
        // 🛡️ (2026-08-15) 2 ตัวนี้ใช้กับระบบกรองคอมเมนต์สแปม — อ่านคอมเมนต์ + ซ่อนคอมเมนต์
        //    ปลดเป็น "พร้อมทดสอบ" แล้วหลังกดเพิ่มเข้า App Review ของ use case PAGES_API
        //    ขาดตัวไหน = ตรวจเจอสแปมแต่ซ่อนไม่ได้ (เงียบ ไม่มี error ให้เห็นในหน้าจอ)
        'pages_read_user_content',
        'pages_manage_engagement',
        // 📅 (2026-08-15) ใช้โพสดวงรายวัน/คอนเทนต์ลงหน้าเพจสาขา
        //    ขาดตัวนี้ = บอทตอบแชทได้ปกติ แต่ cron โพสลงเพจไม่ได้ (คนละเส้นกัน สังเกตยาก)
        'pages_manage_posts',
    ];

    /**
     * 📡 เหตุการณ์ที่เพจต้องส่งเข้าเว็บฮุกของเรา
     *
     * ⚠️ (2026-08-15) นี่คือจุดที่ทำให้ "เปิดสวิตช์แล้วบอทยังเงียบ"
     *    มี page token = **ส่งออก**ได้ แต่ไม่ได้แปลว่า **รับเข้า**ได้
     *    ถ้าไม่ได้ POST /{page-id}/subscribed_apps เอาไว้ Facebook จะไม่ส่งข้อความมาให้เลย
     *    และไม่มี error ให้เห็นที่ไหนทั้งสิ้น — log ว่างเปล่า เหมือนไม่มีใครทัก
     *
     *    ชุดนี้ต้องตรงกับเพจหลัก ไม่งั้นสาขาจะขาดความสามารถบางอย่างแบบเงียบๆ
     *    (ขาด feed = ไม่ตอบคอมเมนต์ · ขาด standby = ระบบแอดมินรับสายเองพัง)
     */
    protected const WEBHOOK_FIELDS = [
        'messages',
        'messaging_postbacks',
        'message_echoes',
        'message_reactions',
        'messaging_referrals',
        'messaging_handovers',
        'feed',
        'standby',
    ];

    /**
     * แสดงรายการสาขาทั้งหมด พร้อมยอดของแต่ละสาขา
     */
    public function index()
    {
        $pages = FortunePage::query()
            ->with('owner:id,name')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        // 📊 สรุปต่อสาขาในคิวรีเดียว — อย่ายิงทีละสาขาใน loop
        $stats = FortuneReading::query()
            ->select('fortune_page_id')
            ->selectRaw('COUNT(*) as total_bills')
            ->selectRaw('SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_bills')
            ->selectRaw('SUM(CASE WHEN is_paid = 1 THEN COALESCE(amount_received, amount_paid) ELSE 0 END) as revenue')
            ->selectRaw('COUNT(DISTINCT platform_user_id) as customers')
            ->whereNotNull('fortune_page_id')
            ->groupBy('fortune_page_id')
            ->get()
            ->keyBy('fortune_page_id');

        // บิลที่ยังไม่มีสาขา — ต้องมองเห็น ไม่ใช่ซ่อน
        // (แยกคิวรีออกมาเพราะ keyBy(null) กลายเป็นคีย์ '' อ่านยากและพลาดง่าย)
        $orphanBills = FortuneReading::query()->whereNull('fortune_page_id')->count();

        // 📡 สาขาที่เปิดอยู่ ต่อสายเว็บฮุกไว้จริงหรือยัง
        //    ตรวจเฉพาะสาขาที่เปิด (ปิดอยู่ก็ไม่ต้องรู้) และเฉพาะ Facebook (LINE ไม่มีระบบนี้)
        //    ⚠️ ต้องมีเพดาน — เปิดสาขาไว้ 20 สาขาแล้ว Graph อืด = หน้านี้ค้างยาวจน 504
        //       ที่เกินโควตาปล่อยเป็น null (= "ยังไม่ได้ตรวจ") ไม่ใช่ false ที่แปลว่า "พัง"
        $webhookStatus = [];
        $checkBudget = 10;

        foreach ($pages as $p) {
            if ($p->platform !== 'facebook' || ! $p->is_active) {
                continue;
            }

            // ที่แคชไว้แล้วอ่านฟรี ไม่ต้องกินโควตา (ไม่งั้นสาขาท้ายๆ จะขึ้น "ยังไม่ได้ตรวจ" ทั้งที่รู้คำตอบอยู่)
            $cached = Cache::get($this->webhookCacheKey($p));

            if ($cached !== null) {
                $webhookStatus[$p->id] = $cached;

                continue;
            }

            if ($checkBudget <= 0) {
                $webhookStatus[$p->id] = null;

                continue;
            }

            $webhookStatus[$p->id] = $this->webhookStatus($p);
            $checkBudget--;
        }

        return view('admin.fortune.pages.index', [
            'pages' => $pages,
            'stats' => $stats,
            'orphanBills' => $orphanBills,
            'webhookStatus' => $webhookStatus,
            // 🔑 เชื่อมบัญชีเจ้าของแล้วหรือยัง — ตัดสินว่าจะโชว์กล่อง "ใส่แค่ไอดีเพจ" หรือกล่องเชื่อมบัญชี
            'hasUserToken' => $this->userToken() !== null,
            'userTokenCheckedAt' => \App\Models\FortuneTellingSetting::getGlobalSettings()->facebook_user_token_checked_at,
            // ⏳ นาฬิกา 2 เรือนของ token — ต้องเห็นก่อนหมด ไม่ใช่รู้ตอนบอทเงียบไปแล้ว
            'tokenHealth' => $this->tokenHealth(),
            // ⚠️ สาขาที่เว้น token ไว้จะ fallback ไป token กลาง — ป้าย "ไม่มี Token"
            //    ต้องขึ้นเฉพาะตอนไม่มีทั้งคู่จริงๆ ไม่งั้นสาขาหลักจะติดป้ายแดงทั้งที่ใช้งานได้
            'hasGlobalPageToken' => ! empty(\App\Models\FortuneTellingSetting::getGlobalSettings()->facebook_page_token),
            'pageTitle' => 'สาขา / เพจแม่หมอ',
        ]);
    }

    /**
     * ฟอร์มเพิ่มสาขาใหม่
     */
    public function create()
    {
        return view('admin.fortune.pages.form', [
            'page' => new FortunePage(['platform' => 'facebook', 'is_active' => true]),
            'pageTitle' => 'เพิ่มสาขาใหม่',
        ]);
    }

    /**
     * ฟอร์มแก้ไขสาขา
     */
    public function edit(FortunePage $fortunePage)
    {
        return view('admin.fortune.pages.form', [
            'page' => $fortunePage,
            'pageTitle' => 'แก้ไขสาขา: '.$fortunePage->name,
        ]);
    }

    /**
     * บันทึกสาขาใหม่
     */
    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        // 🔑 ไม่ได้กรอก token แต่เชื่อมบัญชีไว้แล้ว → ไปดึงมาให้เอง
        //    (เจ้าของสั่ง: ใส่แค่ไอดีเพจก็พอ)
        if (empty($validated['page_access_token']) && ($validated['platform'] ?? 'facebook') === 'facebook') {
            $fetched = $this->fetchPageToken($validated['external_page_id']);

            if ($fetched) {
                $validated['page_access_token'] = $fetched['access_token'];

                if (empty($request->input('name')) && ! empty($fetched['name'])) {
                    $validated['name'] = $fetched['name'];
                }
            }
        }

        $page = FortunePage::create($validated);

        $this->syncDefaultFlag($page);
        FortunePageContext::flushMemo();

        Log::info('🏬 เพิ่มสาขาใหม่', ['fortune_page_id' => $page->id, 'code' => $page->code]);

        return redirect()
            ->route('admin.fortune.pages.index')
            ->with('success', 'เพิ่มสาขา “'.$page->name.'” สำเร็จ'.$this->autoSubscribe($page));
    }

    /**
     * อัพเดทสาขา
     */
    public function update(Request $request, FortunePage $fortunePage)
    {
        $validated = $this->validatePayload($request, $fortunePage);

        // 🔒 เว้นช่องความลับไว้ = ไม่เปลี่ยน (ไม่ใช่ล้างทิ้ง)
        //    ฟอร์มไม่เคยแสดงค่าเดิมของ token/secret ออกมา ถ้ารับค่าว่างตรงๆ
        //    แอดมินกดบันทึกแก้แค่ชื่อ = token หายทั้งสาขา บอทเงียบทันที
        foreach (['page_access_token', 'app_secret', 'verify_token'] as $secret) {
            if (($validated[$secret] ?? '') === '' || ! array_key_exists($secret, $validated)) {
                unset($validated[$secret]);
            }
        }

        $fortunePage->update($validated);

        $this->syncDefaultFlag($fortunePage);
        FortunePageContext::flushMemo();

        // 📡 ติ๊กเปิดจากหน้าแก้ไข ต้องต่อสายให้เหมือนกดสวิตช์ในตาราง
        //    (ทางเข้า 2 ทางไปสถานะเดียวกัน ต้องได้ผลเหมือนกัน ไม่งั้นเปิดจากหน้านี้แล้วบอทเงียบ)
        $webhookNote = $fortunePage->wasChanged('is_active') ? $this->autoSubscribe($fortunePage) : '';

        return redirect()
            ->route('admin.fortune.pages.index')
            ->with('success', 'บันทึกสาขา “'.$fortunePage->name.'” สำเร็จ'.$webhookNote);
    }

    /**
     * เปิด/ปิดสาขา
     *
     * ⚠️ ไม่มีปุ่มลบสาขา — บิลและลูกค้าหลายพันแถวอ้าง fortune_page_id อยู่
     *    ลบแล้วรายงานย้อนหลังจะกลายเป็น "ไม่ระบุสาขา" ทั้งก้อน
     */
    public function toggle(FortunePage $fortunePage)
    {
        $turningOn = ! $fortunePage->is_active;

        $fortunePage->update(['is_active' => $turningOn]);

        FortunePageContext::flushMemo();

        $label = ($turningOn ? 'เปิด' : 'ปิด').'สาขา “'.$fortunePage->name.'” แล้ว';

        // 📡 สวิตช์นี้ต้องเป็นตัวต่อสาย/ตัดสายเว็บฮุกเอง
        //    เจ้าของถามตรงๆ (2026-08-15): "เราจะมีปุ่มเปิดปิดไว้ทำไม ถ้าต้องมาไล่ต่อสายเอง"
        //    ถูกต้อง — เปิดแล้วต้องใช้งานได้จริง ไม่ใช่เปิดแล้วต้องไปกดอย่างอื่นต่อ
        if ($fortunePage->platform !== 'facebook') {
            return back()->with('success', $label);
        }

        if ($turningOn) {
            $result = $this->subscribeWebhook($fortunePage);

            if ($result['ok']) {
                return back()->with('success', $label.' · 📡 ต่อสายเว็บฮุกให้แล้ว — ทักเพจได้เลย');
            }

            // ❗ ต่อสายไม่ได้ = ยังเงียบอยู่ ต้องบอกให้ดังๆ ตรงนี้
            //    (ปล่อยผ่านเงียบๆ คือบั๊กเดิมที่ทำให้ "เปิดแล้วแต่บอทไม่ตอบ")
            //    ไม่ปิดสวิตช์กลับ เพราะ Graph ล่มชั่วคราวไม่ควรทำให้เปิดสาขาไม่ได้เลย
            //    — แต่ป้ายในตารางจะขึ้น "ยังไม่ต่อสาย" สีแดงให้เห็นจนกว่าจะสำเร็จ
            return back()->with(
                'error',
                $label.' แต่ ⚠️ ต่อสายเว็บฮุกไม่สำเร็จ — บอทจะยังไม่ตอบจนกว่าจะต่อสายได้: '
                    .$result['error'].' (กด “ต่อสายใหม่” ที่ป้ายสีแดงในตารางเพื่อลองอีกครั้ง)'
            );
        }

        // 🔒 สาขาหลักไม่ตัดสาย — เว็บฮุกเส้นเดียวกันนี้ยังเลี้ยงระบบคอมเมนต์/สแกนโพสต์อยู่
        //    ปิดสวิตช์สาขาหลักโดยไม่ตั้งใจแล้วไปตัดสาย = พังกว้างกว่าที่ตั้งใจมาก
        //    (ฝั่งเราเช็ค is_active อยู่แล้ว บอทจึงเงียบตามที่สั่ง)
        if ($fortunePage->is_default) {
            return back()->with(
                'success',
                $label.' · 🔒 คงสายเว็บฮุกไว้เพราะเป็นสาขาหลัก (ระบบคอมเมนต์/สแกนโพสต์ใช้เส้นเดียวกัน)'
            );
        }

        $result = $this->unsubscribeWebhook($fortunePage);

        return back()->with(
            'success',
            $label.($result['ok']
                ? ' · 📡 ตัดสายเว็บฮุกแล้ว (Facebook จะไม่ส่งข้อความของเพจนี้มาอีก)'
                : ' · ⚠️ ตัดสายเว็บฮุกไม่สำเร็จ ('.$result['error'].') แต่ฝั่งเราปิดแล้ว บอทไม่ตอบแน่นอน')
        );
    }

    /**
     * 📡 ต่อสายเว็บฮุกใหม่ด้วยมือ — ปุ่มซ่อมจากป้าย "ยังไม่ต่อสาย" ในตาราง
     *
     * ใช้ตอนที่สวิตช์เปิดอยู่แล้วแต่ Facebook ไม่ส่งอะไรมา
     * (เช่นเพจที่เพิ่มไว้ก่อนระบบนี้ หรือตอนต่อสายพลาดเพราะ Graph ล่ม)
     */
    public function subscribe(FortunePage $fortunePage)
    {
        if ($fortunePage->platform !== 'facebook') {
            return back()->with('error', 'ต่อสายเว็บฮุกได้เฉพาะสาขา Facebook');
        }

        $result = $this->subscribeWebhook($fortunePage);

        return $result['ok']
            ? back()->with('success', '✅ ต่อสายเว็บฮุกเพจ “'.$fortunePage->name.'” แล้ว — ทักเพจได้เลย')
            : back()->with('error', 'ต่อสายไม่สำเร็จ: '.$result['error']);
    }

    /**
     * 🧪 ทดสอบ Page Access Token ของสาขา
     *
     * ยิง /me ด้วย token ของสาขานั้นแล้วดูว่าได้ page id ตรงกับที่กรอกไว้ไหม
     * (กรอก token ของเพจ A ใส่สาขา B = ข้อความไปโผล่ผิดเพจ — ต้องจับให้ได้ตั้งแต่ตอนตั้งค่า)
     */
    public function test(FortunePage $fortunePage)
    {
        // ⚠️ ต้องทดสอบด้วย token ตัวเดียวกับที่บอทใช้จริง ไม่ใช่คอลัมน์ดิบ
        //    สาขาที่เว้น token ไว้ (เช่นสาขาหลักที่ backfill มา) ใช้ token กลาง
        //    ถ้าอ่านคอลัมน์ตรงๆ ปุ่มนี้จะฟ้อง "ไม่มี token" ทั้งที่บอททำงานได้ปกติ
        $token = $this->pageToken($fortunePage);

        if (empty($token)) {
            return back()->with('error', 'สาขานี้ยังไม่มี Page Access Token (และไม่มี token กลางให้ใช้)');
        }

        try {
            // อ้างเวอร์ชัน Graph จากจุดเดียวกับที่บอทใช้ (เดิมฮาร์ดโค้ดไว้ พอ bump เวอร์ชันแล้วตกหล่น)
            $response = Http::timeout(15)->get($this->graph('/me'), [
                'fields' => 'id,name',
                'access_token' => $token,
            ]);

            $body = $response->json();

            if (! $response->successful()) {
                return back()->with(
                    'error',
                    'Token ใช้ไม่ได้: '.($body['error']['message'] ?? 'ไม่ทราบสาเหตุ')
                );
            }

            $returnedId = (string) ($body['id'] ?? '');

            if ($returnedId !== (string) $fortunePage->external_page_id) {
                return back()->with(
                    'error',
                    '⚠️ Token นี้เป็นของเพจ '.($body['name'] ?? '?').' (id '.$returnedId.') '
                        .'ไม่ตรงกับ Page ID ที่กรอกไว้ ('.$fortunePage->external_page_id.') — แก้ให้ตรงก่อนใช้งาน'
                );
            }

            return back()->with('success', '✅ Token ใช้ได้ เพจ: '.($body['name'] ?? $returnedId));
        } catch (\Throwable $e) {
            return back()->with('error', 'ทดสอบไม่สำเร็จ: '.$e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════
    // 🔑 เพิ่มเพจแบบ "ใส่แค่ไอดีเพจ" (2026-08-10)
    //
    //    Facebook บังคับว่าส่งข้อความต้องใช้ page token ของเพจนั้นเอง
    //    เลี่ยงไม่ได้ — แต่ไม่ต้องให้เจ้าของก็อปเองทีละเพจ:
    //    เก็บ User Token ของเจ้าของไว้ตัวเดียว แล้วให้ระบบไปขอ page token มาเอง
    // ════════════════════════════════════════════════════════════

    /**
     * บันทึก User Access Token ของเจ้าของเพจ (ใส่ครั้งเดียว)
     *
     * ตรวจก่อนบันทึกเสมอ — token ผิด/หมดอายุแล้วบันทึกผ่าน จะไปพังตอนเพิ่มเพจ
     * แล้วแอดมินจะงงว่าพังที่ขั้นไหน
     *
     * ⏳ (2026-08-11) แลกเป็น token อายุยาวให้อัตโนมัติ
     *    token ที่ก็อปจาก Graph API Explorer สดๆ อายุแค่ ~1 ชั่วโมง
     *    และ **page token ที่ขอด้วย token สั้น ก็อายุสั้นตามไปด้วย**
     *    → เพจใหม่จะตอบได้ชั่วโมงเดียวแล้วเงียบ หาสาเหตุยากมาก
     *    เดิมต้องให้เจ้าของไปกด Extend เองที่ Access Token Debugger (ลืมเมื่อไหร่ = โดน)
     */
    public function connectUserToken(Request $request)
    {
        $token = trim((string) $request->input('facebook_user_token', ''));

        if ($token === '') {
            return back()->with('error', 'ยังไม่ได้ใส่ User Access Token');
        }

        try {
            $me = Http::timeout(15)->get($this->graph('/me'), [
                'fields' => 'id,name',
                'access_token' => $token,
            ]);

            if (! $me->successful()) {
                return back()->with('error', 'Token ใช้ไม่ได้: '.($me->json('error.message') ?? 'ไม่ทราบสาเหตุ'));
            }

            // ⏳ แลกเป็นตัวยาวก่อนเก็บเสมอ — แลกไม่ได้ก็ได้ตัวเดิมคืนมา (ไม่ block การเชื่อม)
            $exchange = $this->exchangeForLongLivedToken($token);
            $token = $exchange['token'];

            // 🔍 ถามอายุจริงจาก Graph — ห้ามเดาจาก expires_in ของขั้นแลก
            //    (token ที่ยาวอยู่แล้วจะไม่มี expires_in กลับมา แต่ debug_token ตอบได้เสมอ)
            $info = $this->inspectToken($token);

            $payload = [
                'facebook_user_token' => $token,
                'facebook_user_token_checked_at' => now(),
            ];

            // 🛡️ ช่วงดีพลอย โค้ดขึ้นก่อน migration ได้ — ยัดคอลัมน์ที่ยังไม่มีลงไป
            //    update() จะพังทั้งคำสั่ง = เชื่อมบัญชีไม่ได้เลยทั้งที่ token ดี
            if ($this->hasExpiryColumns()) {
                $payload['facebook_user_token_expires_at'] = $info['expires_at'];
                $payload['facebook_user_token_data_access_expires_at'] = $info['data_access_expires_at'];
            }

            $settings = \App\Models\FortuneTellingSetting::getGlobalSettings();
            $settings->update($payload);
            \App\Models\FortuneTellingSetting::clearSettingsCache();

            // 🔒 log ได้เฉพาะ "ผลลัพธ์" ห้ามมีค่า token โผล่ใน log เด็ดขาด
            Log::info('🔑 เชื่อมบัญชีเจ้าของเพจ', [
                'exchanged_to_long_lived' => $exchange['exchanged'],
                'expires_at' => optional($info['expires_at'])->toDateTimeString(),
                'data_access_expires_at' => optional($info['data_access_expires_at'])->toDateTimeString(),
            ]);

            $success = '✅ เชื่อมบัญชีสำเร็จ: '.($me->json('name') ?? $me->json('id'))
                .' · '.$this->describeLifetime($exchange, $info)
                .' — ต่อไปเพิ่มเพจใหม่ใส่แค่ไอดีเพจได้เลย';

            $warning = $this->lifetimeWarning($exchange, $info);

            return back()
                ->with('success', $success)
                ->with('error', $warning);   // null = ไม่โชว์การ์ดเตือน
        } catch (\Throwable $e) {
            return back()->with('error', 'เชื่อมบัญชีไม่สำเร็จ: '.$e->getMessage());
        }
    }

    /**
     * แลก short-lived user token → long-lived (60 วัน)
     *
     * แลกไม่ได้ไม่ถือว่าล้มเหลวทั้งกระบวนการ — คืน token เดิมไปพร้อมเหตุผล
     * ให้แอดมินเห็นเป็นคำเตือน ดีกว่าบล็อกจนเชื่อมบัญชีไม่ได้เลย
     *
     * @return array{token: string, exchanged: bool, error: ?string}
     */
    protected function exchangeForLongLivedToken(string $token): array
    {
        [$appId, $appSecret] = $this->appCredentials();

        if ($appId === '' || $appSecret === '') {
            return [
                'token' => $token,
                'exchanged' => false,
                'error' => 'ยังไม่ได้ตั้ง App ID / App Secret ในหน้า “ช่องทางรับข้อความ”',
            ];
        }

        try {
            $response = Http::timeout(15)->get($this->graph('/oauth/access_token'), [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $token,
            ]);

            $longLived = (string) ($response->json('access_token') ?? '');

            if (! $response->successful() || $longLived === '') {
                return [
                    'token' => $token,
                    'exchanged' => false,
                    'error' => $response->json('error.message') ?: 'ไม่ทราบสาเหตุ',
                ];
            }

            return ['token' => $longLived, 'exchanged' => true, 'error' => null];
        } catch (\Throwable $e) {
            return ['token' => $token, 'exchanged' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ถามอายุ + สิทธิ์ของ token จาก Graph (/debug_token)
     *
     * @return array{expires_at: ?Carbon, data_access_expires_at: ?Carbon, scopes: array<string>, checked: bool}
     */
    protected function inspectToken(string $token): array
    {
        $blank = ['expires_at' => null, 'data_access_expires_at' => null, 'scopes' => [], 'checked' => false];

        [$appId, $appSecret] = $this->appCredentials();

        if ($appId === '' || $appSecret === '') {
            return $blank;
        }

        try {
            // timeout สั้นกว่าขั้นอื่น — ขั้นนี้เป็นของแถม ถ้าช้าก็ข้ามได้
            // (ต่อกันสามคำขอ ถ้าตั้ง 15 วิเท่ากันหมด แล้ว Graph ค้าง = หน้าแอดมิน 504 แทนที่จะขึ้นข้อความ)
            $response = Http::timeout(8)->get($this->graph('/debug_token'), [
                'input_token' => $token,
                'access_token' => $appId.'|'.$appSecret,
            ]);

            if (! $response->successful()) {
                return $blank;
            }

            $data = $response->json('data') ?? [];

            return [
                'expires_at' => $this->graphTimestamp($data['expires_at'] ?? null),
                'data_access_expires_at' => $this->graphTimestamp($data['data_access_expires_at'] ?? null),
                'scopes' => array_values(array_filter((array) ($data['scopes'] ?? []))),
                'checked' => true,
            ];
        } catch (\Throwable $e) {
            Log::warning('🔑 ตรวจอายุ token ไม่สำเร็จ', ['error' => $e->getMessage()]);

            return $blank;
        }
    }

    /**
     * แปลงเวลาแบบ unix ที่ Graph ส่งมาเป็น Carbon
     *
     * ⚠️ Graph ใช้ 0 แทน "ไม่มีวันหมดอายุ" — ต้องคืน null
     *    ถ้าเผลอแปลง 0 ตรงๆ จะได้ปี 1970 = หน้าจอฟ้อง "หมดอายุแล้ว" ตลอดกาล
     */
    protected function graphTimestamp(mixed $value): ?Carbon
    {
        $timestamp = (int) $value;

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp, config('app.timezone')) : null;
    }

    /**
     * App ID / App Secret ของแอป Meta ที่ใช้อยู่
     *
     * @return array{0: string, 1: string}
     */
    protected function appCredentials(): array
    {
        try {
            $settings = \App\Models\FortuneTellingSetting::getGlobalSettings();

            return [
                trim((string) ($settings->facebook_app_id ?? '')),
                trim((string) ($settings->facebook_app_secret ?? '')),
            ];
        } catch (\Throwable $e) {
            return ['', ''];
        }
    }

    /**
     * คอลัมน์วันหมดอายุพร้อมใช้แล้วหรือยัง (migration ลงหรือยัง)
     */
    protected function hasExpiryColumns(): bool
    {
        return Schema::hasColumn('fortune_telling_settings', 'facebook_user_token_expires_at')
            && Schema::hasColumn('fortune_telling_settings', 'facebook_user_token_data_access_expires_at');
    }

    /**
     * ประโยคบอกอายุ token ที่แอดมินอ่านรู้เรื่อง
     *
     * @param  array{token: string, exchanged: bool, error: ?string}  $exchange
     * @param  array{expires_at: ?Carbon, data_access_expires_at: ?Carbon, scopes: array<string>, checked: bool}  $info
     */
    protected function describeLifetime(array $exchange, array $info): string
    {
        $prefix = $exchange['exchanged'] ? 'ต่ออายุอัตโนมัติแล้ว' : 'เก็บ token ตามที่วางมา';

        if (! $info['checked']) {
            return $prefix;
        }

        if ($info['expires_at'] === null) {
            return $prefix.' (ไม่มีวันหมดอายุ)';
        }

        return $prefix.' (หมดอายุ '.$info['expires_at']->format('d/m/y H:i').')';
    }

    /**
     * คำเตือนที่ต้องขึ้นการ์ดแดง — คืน null ถ้าไม่มีอะไรต้องเตือน
     *
     * @param  array{token: string, exchanged: bool, error: ?string}  $exchange
     * @param  array{expires_at: ?Carbon, data_access_expires_at: ?Carbon, scopes: array<string>, checked: bool}  $info
     */
    protected function lifetimeWarning(array $exchange, array $info): ?string
    {
        $warnings = [];

        if (! $exchange['exchanged']) {
            $warnings[] = '⚠️ ต่ออายุ token อัตโนมัติไม่สำเร็จ ('.$exchange['error'].') '
                .'— ถ้าตัวที่วางมาเป็นของสดจาก Graph API Explorer จะหมดอายุใน ~1 ชม. '
                .'และกุญแจของเพจที่ดึงไปจะหมดตามด้วย';
        }

        // 🔍 ขาดสิทธิ์ = ผ่าน /me แต่ทำงานจริงไม่ได้ ต้องบอกให้ชัด
        if ($info['checked'] && $info['scopes'] !== []) {
            $missing = array_diff(self::REQUIRED_USER_SCOPES, $info['scopes']);

            if ($missing !== []) {
                $warnings[] = '⚠️ token ขาดสิทธิ์: '.implode(', ', $missing)
                    .' — กลับไปติ๊กเพิ่มที่ Graph API Explorer แล้ววางใหม่';
            }
        }

        return $warnings === [] ? null : implode(' · ', $warnings);
    }

    /**
     * สถานะอายุ token สำหรับโชว์หน้ารายการสาขา
     *
     * ⏳ นาฬิกา 2 เรือน หมดคนละเวลา พังคนละแบบ:
     *   - expires_at            หมด = ดึง/รีเฟรชกุญแจเพจไม่ได้อีก
     *   - data_access_expires_at หมด = token ยังส่งข้อความได้ แต่อ่านข้อมูลผู้ใช้ไม่ได้
     *                            (error_subcode 33 — เคยทำลูกค้าเข้าวอลเลตตัวเองไม่ได้ 2026-07-27)
     *
     * @return array{expiresAt: ?Carbon, expiresInDays: ?int, dataAccessAt: ?Carbon, dataAccessInDays: ?int}
     */
    protected function tokenHealth(): array
    {
        try {
            $settings = \App\Models\FortuneTellingSetting::getGlobalSettings();
            $expires = $settings->facebook_user_token_expires_at;
            $dataAccess = $settings->facebook_user_token_data_access_expires_at;
        } catch (\Throwable $e) {
            $expires = $dataAccess = null;
        }

        return [
            'expiresAt' => $expires,
            'expiresInDays' => $this->daysLeft($expires),
            'dataAccessAt' => $dataAccess,
            'dataAccessInDays' => $this->daysLeft($dataAccess),
        ];
    }

    /**
     * เหลืออีกกี่วัน (ติดลบ = หมดไปแล้ว, null = ไม่มีวันหมด/ยังไม่รู้)
     *
     * คิดจาก timestamp ตรงๆ เพื่อไม่ต้องไปพึ่งทิศทางเครื่องหมายของ diffInDays
     * ซึ่งเปลี่ยนความหมายระหว่าง Carbon เวอร์ชัน
     */
    protected function daysLeft(?Carbon $date): ?int
    {
        return $date === null ? null : (int) floor(($date->getTimestamp() - time()) / 86400);
    }

    /**
     * ดึงรายชื่อเพจทั้งหมดที่เจ้าของดูแล แล้วเพิ่มทีเดียว
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function discover(Request $request)
    {
        $userToken = $this->userToken();

        if (! $userToken) {
            return back()->with('error', 'ยังไม่ได้เชื่อมบัญชี — ใส่ User Access Token ก่อน');
        }

        try {
            $response = Http::timeout(20)->get($this->graph('/me/accounts'), [
                'fields' => 'id,name,access_token',
                'limit' => 100,   // กัน pagination เงียบๆ ตอนมีเพจเยอะ
                'access_token' => $userToken,
            ]);

            if (! $response->successful()) {
                return back()->with('error', 'ดึงรายชื่อเพจไม่สำเร็จ: '.($response->json('error.message') ?? 'ไม่ทราบสาเหตุ'));
            }

            $found = $response->json('data') ?? [];

            if ($found === []) {
                return back()->with('error', 'ไม่พบเพจในบัญชีนี้ — ตรวจว่า token มี scope pages_show_list + pages_messaging');
            }

            $added = 0;
            $updated = 0;
            $wired = 0;
            $wireFailed = 0;

            /** @var list<FortunePage> $toWire สาขาที่เปิดอยู่ ต้องต่อสายเว็บฮุกให้ครบ */
            $toWire = [];

            foreach ($found as $item) {
                $externalId = (string) ($item['id'] ?? '');
                $pageToken = (string) ($item['access_token'] ?? '');

                if ($externalId === '' || $pageToken === '') {
                    continue;
                }

                $existing = FortunePage::where('platform', 'facebook')
                    ->where('external_page_id', $externalId)
                    ->first();

                if ($existing) {
                    // เพจที่มีอยู่แล้ว → เติม/รีเฟรช token ให้ (token หมดอายุคือเหตุบอทเงียบอันดับต้นๆ)
                    $existing->update(['page_access_token' => $pageToken]);
                    $updated++;

                    // 📡 ปุ่มนี้คือ "ปุ่มซ่อมรวม" — สาขาที่เปิดอยู่แต่ยังไม่ต่อสาย ต้องได้ต่อด้วย
                    if ($existing->is_active) {
                        $toWire[] = $existing;
                    }

                    continue;
                }

                $created = FortunePage::create([
                    'code' => $this->makeCode($externalId, $item['name'] ?? null),
                    'name' => $item['name'] ?? ('เพจ '.$externalId),
                    'platform' => 'facebook',
                    'external_page_id' => $externalId,
                    'page_access_token' => $pageToken,
                    'is_active' => true,
                    'is_default' => false,
                    'notes' => 'เพิ่มอัตโนมัติจากรายชื่อเพจของเจ้าของ',
                ]);
                $added++;
                $toWire[] = $created;
            }

            FortunePageContext::flushMemo();

            // ⚠️ ต่อสายหลัง flushMemo — ต้องอ่าน token ที่เพิ่งบันทึก ไม่ใช่ของที่ค้างใน memo
            foreach ($toWire as $page) {
                if ($this->subscribeWebhook($page)['ok']) {
                    $wired++;
                } else {
                    $wireFailed++;
                }
            }

            $message = "✅ เพิ่มเพจใหม่ {$added} เพจ · อัปเดต token เพจเดิม {$updated} เพจ · 📡 ต่อสายเว็บฮุก {$wired} สาขา";

            if ($wireFailed > 0) {
                $message .= " · ⚠️ ต่อสายไม่สำเร็จ {$wireFailed} สาขา (ดูป้ายสีแดงในตาราง)";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('🏬 ดึงรายชื่อเพจไม่สำเร็จ', ['error' => $e->getMessage()]);

            return back()->with('error', 'ดึงรายชื่อเพจไม่สำเร็จ: '.$e->getMessage());
        }
    }

    /**
     * เพิ่มเพจด้วย "ไอดีเพจ" อย่างเดียว — ระบบไปดึงชื่อ + page token มาเอง
     */
    public function quickAdd(Request $request)
    {
        $externalId = trim((string) $request->input('external_page_id', ''));

        if ($externalId === '') {
            return back()->with('error', 'ยังไม่ได้ใส่ไอดีเพจ');
        }

        if (FortunePage::where('platform', 'facebook')->where('external_page_id', $externalId)->exists()) {
            return back()->with('error', 'เพจนี้ถูกเพิ่มไว้แล้ว');
        }

        $userToken = $this->userToken();

        if (! $userToken) {
            return back()->with('error', 'ยังไม่ได้เชื่อมบัญชี — ใส่ User Access Token ก่อน แล้วค่อยใส่ไอดีเพจ');
        }

        $fetched = $this->fetchPageToken($externalId);

        if (! $fetched) {
            return back()->with(
                'error',
                'ดึงข้อมูลเพจไม่สำเร็จ — ตรวจว่าไอดีเพจถูกต้อง และบัญชีที่เชื่อมไว้เป็นแอดมินของเพจนี้ '
                    .'(ดูสาเหตุจริงได้ใน log)'
            );
        }

        $page = FortunePage::create([
            'code' => $this->makeCode($externalId, $fetched['name']),
            'name' => $fetched['name'] ?: ('เพจ '.$externalId),
            'platform' => 'facebook',
            'external_page_id' => $externalId,
            'page_access_token' => $fetched['access_token'],
            'is_active' => true,
            'is_default' => false,
        ]);

        FortunePageContext::flushMemo();

        return back()->with('success', '✅ เพิ่มเพจ “'.$page->name.'” เรียบร้อย'.$this->autoSubscribe($page));
    }

    /**
     * ขอ page token + ชื่อเพจ จากไอดีเพจ โดยใช้ User Token ที่เชื่อมไว้
     *
     * @return array{name: ?string, access_token: string}|null null = ขอไม่ได้ (ไม่ได้เชื่อมบัญชี / ไม่ใช่แอดมินเพจนั้น)
     */
    protected function fetchPageToken(string $externalPageId): ?array
    {
        $userToken = $this->userToken();

        if (! $userToken) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get($this->graph('/'.$externalPageId), [
                'fields' => 'name,access_token',
                'access_token' => $userToken,
            ]);

            $pageToken = (string) ($response->json('access_token') ?? '');

            if (! $response->successful() || $pageToken === '') {
                Log::warning('🏬 ดึง page token อัตโนมัติไม่ได้', [
                    'external_page_id' => $externalPageId,
                    'error' => $response->json('error.message'),
                ]);

                return null;
            }

            return ['name' => $response->json('name'), 'access_token' => $pageToken];
        } catch (\Throwable $e) {
            Log::warning('🏬 ดึง page token อัตโนมัติไม่ได้', [
                'external_page_id' => $externalPageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ════════════════════════════════════════════════════════════
    // 📡 เว็บฮุกรายสาขา (2026-08-15)
    //
    //    บทเรียน: page token = "ส่งออกได้" · subscribed_apps = "รับเข้าได้"
    //    เพจสาขา 5 มี token ครบ เปิดสวิตช์แล้ว แต่ subscribed_apps ว่าง
    //    → Facebook ไม่ส่งอะไรมาเลย log ว่างเปล่า หาสาเหตุไม่เจอเพราะไม่มี error
    // ════════════════════════════════════════════════════════════

    /**
     * Token ตัวเดียวกับที่บอทใช้จริงของสาขานี้
     *
     * ⚠️ ห้ามอ่านคอลัมน์ดิบ — สาขาที่เว้น token ไว้ใช้ token กลาง
     *    อ่านดิบแล้วจะฟ้อง "ไม่มี token" ทั้งที่ใช้งานได้ (กับดักเดิมของปุ่มทดสอบ)
     */
    protected function pageToken(FortunePage $page): ?string
    {
        $token = FortunePageContext::run(
            $page,
            fn () => \App\Models\FortuneTellingSetting::getSettings()->facebook_page_token
        );

        return ! empty($token) ? (string) $token : null;
    }

    /**
     * ต่อสายเว็บฮุก — บอก Facebook ให้ส่งข้อความของเพจนี้มาที่ระบบเรา
     *
     * @return array{ok: bool, error: ?string}
     */
    protected function subscribeWebhook(FortunePage $page): array
    {
        return $this->webhookCall($page, true);
    }

    /**
     * ตัดสายเว็บฮุก — Facebook หยุดส่งข้อความของเพจนี้
     *
     * @return array{ok: bool, error: ?string}
     */
    protected function unsubscribeWebhook(FortunePage $page): array
    {
        return $this->webhookCall($page, false);
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    protected function webhookCall(FortunePage $page, bool $subscribe): array
    {
        $token = $this->pageToken($page);

        if ($token === null) {
            return ['ok' => false, 'error' => 'สาขานี้ยังไม่มี Page Access Token'];
        }

        $url = $this->graph('/'.$page->external_page_id.'/subscribed_apps');

        try {
            if ($subscribe) {
                $response = Http::timeout(15)->asForm()->post($url, [
                    'subscribed_fields' => implode(',', self::WEBHOOK_FIELDS),
                    'access_token' => $token,
                ]);
            } else {
                // ⚠️ DELETE ของ Graph รับค่าทาง query เท่านั้น — ส่งเป็น body แล้วมันมองไม่เห็น
                $response = Http::timeout(15)->send('DELETE', $url, [
                    'query' => ['access_token' => $token],
                ]);
            }

            // ตรวจใหม่ครั้งหน้าเสมอ ไม่ให้ป้ายในตารางค้างของเก่า
            $this->forgetWebhookStatus($page);

            if (! $response->successful() || $response->json('success') !== true) {
                $error = $response->json('error.message')
                    ?? 'Facebook ตอบกลับผิดปกติ (HTTP '.$response->status().')';

                Log::warning('📡 '.($subscribe ? 'ต่อ' : 'ตัด').'สายเว็บฮุกไม่สำเร็จ', [
                    'fortune_page_id' => $page->id,
                    'external_page_id' => $page->external_page_id,
                    'error' => $error,   // 🔐 ห้าม log token
                ]);

                return ['ok' => false, 'error' => $error];
            }

            Log::info('📡 '.($subscribe ? 'ต่อ' : 'ตัด').'สายเว็บฮุกสำเร็จ', [
                'fortune_page_id' => $page->id,
                'external_page_id' => $page->external_page_id,
            ]);

            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            $this->forgetWebhookStatus($page);

            Log::warning('📡 '.($subscribe ? 'ต่อ' : 'ตัด').'สายเว็บฮุกล้มเหลว', [
                'fortune_page_id' => $page->id,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ต่อสายให้อัตโนมัติตอนเพิ่ม/เปิดสาขา — คืนข้อความต่อท้ายให้แอดมินเห็นผลทันที
     *
     * เพิ่มเพจแล้วขึ้น "ทักเพจได้เลย" ทั้งที่ยังไม่ต่อสาย = คำโกหกที่ทำให้หาบั๊กไม่เจอ
     */
    protected function autoSubscribe(FortunePage $page): string
    {
        if ($page->platform !== 'facebook' || ! $page->is_active) {
            return '';
        }

        $result = $this->subscribeWebhook($page);

        return $result['ok']
            ? ' · 📡 ต่อสายเว็บฮุกแล้ว — ทักเพจได้เลย'
            : ' · ⚠️ ยังต่อสายเว็บฮุกไม่ได้ ('.$result['error'].') — บอทจะยังไม่ตอบ '
                .'กด “ต่อสายใหม่” ที่ป้ายสีแดงในตารางเพื่อลองอีกครั้ง';
    }

    /**
     * เพจนี้ต่อสายเว็บฮุกไว้จริงหรือยัง
     *
     * @return bool|null true=ต่อแล้ว · false=ยังไม่ต่อ · null=ตรวจไม่ได้
     *
     * ⚠️ null ≠ false — "ตรวจไม่ได้" ต้องไม่แสดงเป็น "ยังไม่ต่อสาย"
     *    (ความพังแบบเดียวกับการโชว์ว่าปลอดภัยทั้งที่ไม่รู้)
     */
    protected function webhookStatus(FortunePage $page): ?bool
    {
        if ($page->platform !== 'facebook') {
            return null;
        }

        // แคชไว้ 10 นาที — หน้านี้เปิดบ่อย ยิง Graph ทุกครั้งต่อทุกสาขาคือหน้าค้าง
        // (Cache::remember ไม่เก็บค่า null อยู่แล้ว → "ตรวจไม่ได้" จะลองใหม่รอบหน้าเอง)
        return Cache::remember($this->webhookCacheKey($page), 600, function () use ($page) {
            $token = $this->pageToken($page);

            if ($token === null) {
                return false;
            }

            try {
                $response = Http::timeout(8)->get(
                    $this->graph('/'.$page->external_page_id.'/subscribed_apps'),
                    ['access_token' => $token]
                );

                if (! $response->successful()) {
                    return null;
                }

                return ! empty($response->json('data'));
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    /**
     * ล้างสถานะที่แคชไว้ (เรียกทุกครั้งหลังต่อ/ตัดสาย)
     */
    protected function forgetWebhookStatus(FortunePage $page): void
    {
        Cache::forget($this->webhookCacheKey($page));
    }

    protected function webhookCacheKey(FortunePage $page): string
    {
        return 'fortune_page_webhook_status:'.$page->id;
    }

    /**
     * User Access Token ที่บันทึกไว้ (null = ยังไม่ได้เชื่อม)
     */
    protected function userToken(): ?string
    {
        try {
            $token = \App\Models\FortuneTellingSetting::getGlobalSettings()->facebook_user_token;
        } catch (\Throwable $e) {
            // 🔐 คอลัมน์นี้เข้ารหัสไว้ — ถ้า APP_KEY ถูกเปลี่ยน decrypt จะ throw
            //    ปล่อยให้หลุดขึ้นไป = หน้าจัดการสาขา 500 ทั้งหน้า ทั้งที่แค่ต้อง "วาง token ใหม่"
            Log::warning('🔑 อ่าน facebook_user_token ไม่ได้ (APP_KEY เปลี่ยน?)', ['error' => $e->getMessage()]);

            return null;
        }

        return ! empty($token) ? (string) $token : null;
    }

    /**
     * URL ของ Graph API — ใช้เวอร์ชันเดียวกับที่บอทใช้จริง (แหล่งความจริงเดียว)
     */
    protected function graph(string $path): string
    {
        return 'https://graph.facebook.com/'.\App\Services\FacebookWebhookService::GRAPH_API_VERSION.$path;
    }

    /**
     * สร้างรหัสสาขาให้เอง — แอดมินไม่ต้องคิด
     */
    protected function makeCode(string $externalId, ?string $name = null): string
    {
        $base = $name ? \Illuminate\Support\Str::slug($name) : '';

        // ชื่อเพจไทยล้วน → slug ได้สตริงว่าง → ตกมาใช้ไอดีท้าย 8 หลัก
        if ($base === '' || ! preg_match('/[a-z0-9]/', $base)) {
            $base = 'page-'.substr($externalId, -8);
        }

        $base = substr($base, 0, 34);
        $code = $base;
        $i = 2;

        while (FortunePage::withTrashed()->where('code', $code)->exists()) {
            $code = substr($base, 0, 34).'-'.$i;
            $i++;
        }

        return $code;
    }

    /**
     * ตรวจข้อมูลที่ส่งมา
     *
     * @return array<string, mixed>
     */
    protected function validatePayload(Request $request, ?FortunePage $existing = null): array
    {
        $validated = $request->validate([
            // 🔧 ไม่บังคับ — ถ้าเว้นว่างระบบตั้งให้เอง (แอดมินไม่ต้องคิดรหัสสาขา)
            'code' => [
                'nullable', 'string', 'max:40', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('fortune_pages', 'code')->ignore($existing?->id),
            ],
            'name' => 'nullable|string|max:120',
            'brand_name' => 'nullable|string|max:120',
            'platform' => 'required|in:facebook,line',
            'external_page_id' => [
                'required', 'string', 'max:64',
                Rule::unique('fortune_pages', 'external_page_id')->ignore($existing?->id),
            ],
            'page_access_token' => 'nullable|string',
            'app_secret' => 'nullable|string|max:255',
            'verify_token' => 'nullable|string|max:255',
            'owner_user_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
            'auto_post_enabled' => 'nullable|boolean',
            // 💬 (2026-08-27) สวิตช์ตอบคอมเมนต์รายเพจ — 3 สถานะ (ไม่ส่งค่า/null = ตามค่ากลาง)
            //    middleware ConvertEmptyStringsToNull แปลง '' เป็น null ให้ก่อนถึงตรงนี้แล้ว
            'comment_reply_enabled' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
            'settings_override_json' => 'nullable|string',
        ], [
            'code.regex' => 'รหัสสาขาใช้ได้เฉพาะ a-z, 0-9 และขีดกลาง',
            'external_page_id.unique' => 'Page ID นี้ถูกใช้กับสาขาอื่นแล้ว',
        ]);

        // 🔧 settings_override รับมาเป็น JSON string จากฟอร์ม
        //    ⚠️ ถ้าฟอร์มไม่ได้ส่งช่องนี้มาเลย (โหมดง่าย/ฟอร์มย่อ) ห้ามเซ็ต null
        //       ไม่งั้นกดบันทึกแก้แค่ชื่อ = ค่า override ที่ตั้งไว้หายทั้งชุด
        $hasOverrideField = $request->has('settings_override_json');
        $overrideRaw = trim((string) ($validated['settings_override_json'] ?? ''));
        unset($validated['settings_override_json']);

        if (! $hasOverrideField) {
            // ไม่แตะของเดิม
        } elseif ($overrideRaw === '') {
            $validated['settings_override'] = null;
        } else {
            $decoded = json_decode($overrideRaw, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'settings_override_json' => 'รูปแบบ JSON ไม่ถูกต้อง — ต้องเป็น object เช่น {"deep_reading_price": 49}',
                ]);
            }

            $validated['settings_override'] = $decoded;
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_default'] = $request->boolean('is_default');

        // 🏬 ช่องนี้มีเฉพาะในฟอร์มเต็ม — ฟอร์มย่อ (เพิ่มด้วยไอดีเพจ) ไม่ส่งมา
        //    ถ้าเซ็ตตรงๆ จะกลายเป็นปิดสวิตช์โพสทุกครั้งที่บันทึกจากฟอร์มย่อ
        if ($request->has('auto_post_enabled') || $request->isMethod('put')) {
            $validated['auto_post_enabled'] = $request->boolean('auto_post_enabled');
        } else {
            unset($validated['auto_post_enabled']);
        }

        // 💬 (2026-08-27) สวิตช์ตอบคอมเมนต์ — 3 สถานะ ห้ามใช้ boolean() แปลง
        //    boolean() จะแปลง '' (ตามค่ากลาง) เป็น false = "ปิดเฉพาะเพจนี้" ซึ่งคนละความหมาย
        //    และเหมือน auto_post_enabled: ฟอร์มย่อไม่ส่งช่องนี้มา ⇒ ห้ามเซ็ตตรงๆ
        if ($request->has('comment_reply_enabled')) {
            $raw = $request->input('comment_reply_enabled');
            $validated['comment_reply_enabled'] = ($raw === '' || $raw === null) ? null : (bool) $raw;
        } else {
            unset($validated['comment_reply_enabled']);
        }

        // 🔧 เว้นว่างได้ทั้งคู่ — ระบบเติมให้เอง (เจ้าของสั่ง: ใส่แค่ไอดีเพจก็พอ)
        if (empty($validated['code'])) {
            $validated['code'] = $existing?->code
                ?: $this->makeCode($validated['external_page_id'], $validated['name'] ?? null);
        }

        if (empty($validated['name'])) {
            $validated['name'] = $existing?->name ?: ('เพจ '.$validated['external_page_id']);
        }

        return $validated;
    }

    /**
     * ให้มีสาขาหลักได้แค่ 1 สาขาต่อ 1 ช่องทาง
     */
    protected function syncDefaultFlag(FortunePage $page): void
    {
        if (! $page->is_default) {
            return;
        }

        DB::table('fortune_pages')
            ->where('platform', $page->platform)
            ->where('id', '!=', $page->id)
            ->update(['is_default' => false]);
    }
}
