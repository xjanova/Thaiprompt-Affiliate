<?php

namespace App\Console\Commands;

use App\Models\FortuneInviteMessage;
use App\Models\FortunePage;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\LineBotKeyword;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortunePageContext;
use App\Services\FortuneWebLinkService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * 🌐 (2026-11-03) ตรวจความพร้อมก่อนเปิดธง facebook_web_only (FB ห้ามดูดวง/รับเงินในแชท → ไปเว็บจันทราทั้งหมด)
 *
 * ทำไมต้องมี: ธงนี้ไม่มีทางถอยฝั่งลูกค้า (เจ้าของสั่ง) — เปิดแล้วลิงก์เว็บใช้ไม่ได้ = ลูกค้า FB ทุกคนเจอทางตัน
 * และถ้าเพจสาขายังเขียนทับค่าคอม/ตอบคำทำนายใต้คอมเมนต์ = สายงาน MLM เพี้ยน / ยังดูดวงบน FB อยู่
 * ⇒ ให้เครื่องตรวจทุกข้อใน design หัวข้อ 8 ก่อน ต้องได้ 0 blocker ถึงจะเปิดธงได้
 *
 * BLOCKER (ต้องแก้ก่อน): ลิงก์เว็บของ FB สร้างไม่ได้ · SSO จันทราไม่ตอบ · ชุดข้อความ DM แบบ web น้อยเกิน ·
 *   เพจสาขาเขียนทับค่าคอม/affiliate หรือเปิดตอบคำทำนายใต้คอมเมนต์ · fortune_commission_mode ไม่ใช่ static (ยกเว้น ack) ·
 *   ไม่เห็นบิล JW- ใน 24 ชม. · เพดานแชทเว็บ (jwchat) ต่ำกว่า peak ข้อความ FB × 1.5 · เมนู Messenger ยังมีปุ่มราคา ·
 *   คีย์เวิร์ด fortune/faq มีคำเรื่องเงิน (ยกเว้น ack) · respond_in_comment เปิดอยู่
 * WARN (นับจำนวน Q1-Q9 ของงานค้าง): บิลค้างจ่าย · จ่ายแล้วยังส่งของไม่ครบ · Pro Session · takeover · fb_fallback ·
 *   เครดิตฟรี · เงินกำพร้า · คำถามที่รอแอดมิน · linked_asid ซ้ำกัน
 *
 * ใช้: php artisan fortune:fb-web-only-preflight
 *      php artisan fortune:fb-web-only-preflight --ack-commission-mode --ack-money-keywords --peak-per-minute=40
 */
class FortuneFbWebOnlyPreflight extends Command
{
    protected $signature = 'fortune:fb-web-only-preflight
        {--ack-commission-mode : เจ้าของรับทราบแล้วว่า fortune_commission_mode ไม่ใช่ static (ลดเป็นคำเตือน)}
        {--ack-money-keywords : เจ้าของรับทราบคีย์เวิร์ด fortune/faq ที่มีคำเรื่องเงิน (บน FB ถูกข้ามอยู่แล้ว — ลดเป็นคำเตือน)}
        {--peak-per-minute= : peak ข้อความลูกค้า FB ต่อนาที (เว้นว่าง = คำนวณจากแชท 7 วันล่าสุด)}
        {--chat-ceiling= : เพดานแชทเว็บต่อนาที (เว้นว่าง = อ่านจาก route chat/send)}';

    protected $description = 'ตรวจความพร้อมก่อนเปิด FB web-only (ห้ามดูดวง/รับเงินในแชท FB — ย้ายไปเว็บจันทราทั้งหมด)';

    /** ชุดข้อความ DM แบบ web ที่ปลอดภัยและเปิดอยู่ ต่ำกว่านี้ = blocker (DM ซ้ำจนน่ารำคาญ/ว่าง) */
    public const MIN_WEB_INVITES = 3;

    /** ต่ำกว่านี้ = เตือนให้เพิ่มความหลากหลาย (design เสนอ 10) */
    public const RECOMMENDED_WEB_INVITES = 10;

    /** เพดานแชทเว็บต้องสูงกว่า peak ข้อความ FB อย่างน้อยกี่เท่า */
    public const CHAT_HEADROOM = 1.5;

    /** ต้องเห็นบิล JW- ภายในกี่ชั่วโมง (พิสูจน์ว่า affiliate:sync-bills ของจันทรายังทำงาน — M10) */
    public const JUNTRA_BILL_WINDOW_HOURS = 24;

    /** วันย้อนหลังที่ใช้หา peak ข้อความ FB ต่อนาที */
    public const PEAK_LOOKBACK_DAYS = 7;

    /** payload/ข้อความในเมนู Messenger ที่ถือว่า "ยังขาย/ยังพาไปสมัครแยกบัญชี" */
    public const PRICE_MENU_PAYLOADS = [
        'MENU_DEEP_FORTUNE', 'TIER_DEEP_39', 'TIER_CELTIC_99', 'INVITE_READ_NOW', 'FORTUNE_BASIC',
        'FORTUNE_DEEP', 'FORTUNE_CELTIC', 'MENU_PRICING', 'MENU_REGISTER',
    ];

    /** คีย์ settings_override ของเพจสาขาที่กระทบค่าคอม/สายงาน (M3) — มีคีย์พวกนี้ = blocker */
    public const AFFILIATE_OVERRIDE_REGEX = '/(affiliate|commission|^fortune_(pv_value|level[12]_|pkg_rates|deep_l[12]|celtic_l[12]|central_|juntra_|auto_register))/i';

    /** สถานะของที่จ่ายแล้วแต่ยังส่งไม่ครบ (Q2) */
    public const PAID_UNDELIVERED_STATUSES = [
        FortuneReading::STATUS_PAID,
        FortuneReading::STATUS_COLLECTING_BIRTHDATE,
        FortuneReading::STATUS_COLLECTING_QUESTIONS,
        FortuneReading::STATUS_COLLECTING_TAROT,
        FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
        FortuneReading::STATUS_CELTIC_PICKING,
        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
        FortuneReading::STATUS_CELTIC_GENERATING,
        FortuneReading::STATUS_CELTIC_QA_PROMPT,
    ];

    /** สถานะบิลค้างจ่ายของ FB (Q1) */
    public const PENDING_BILL_STATUSES = [
        FortuneReading::STATUS_PENDING_PAYMENT,
        FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
        FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
        FortuneReading::STATUS_PENDING_STRIPE_PAYMENT,
    ];

    /** จำนวนข้อที่ทำให้ "ยังเปิดไม่ได้" */
    protected int $blockers = 0;

    /** จำนวนข้อที่ควรรู้แต่ไม่บล็อก */
    protected int $warnings = 0;

    /** รายการ blocker (ไว้ให้เทสต์/สรุปท้าย) */
    protected array $blockerLabels = [];

    public function handle(): int
    {
        // อินสแตนซ์คำสั่งถูกใช้ซ้ำได้ใน process เดียว (Artisan::call หลายครั้ง) — เริ่มนับใหม่ทุกครั้ง
        $this->blockers = 0;
        $this->warnings = 0;
        $this->blockerLabels = [];

        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getSettings();

        $this->newLine();
        $this->line('🌐  <options=bold>ตรวจความพร้อม FB WEB-ONLY (ห้ามดูดวง/รับเงินในแชท FB → เว็บจันทรา)</>');
        $this->line(str_repeat('─', 70));

        $this->section('ธงและปลายทาง');
        $this->checkFlag($settings);
        $this->checkWebLink($settings);
        $this->checkSso($settings);

        $this->section('ข้อความและเมนูฝั่ง FB');
        $this->checkWebInvites();
        $this->checkMessengerMenu($settings);
        $this->checkRespondInComment($settings);
        $this->checkMoneyKeywords();

        $this->section('สายงาน MLM / ค่าคอม');
        $this->checkCommissionMode($settings);
        $this->checkPageOverrides();
        $this->checkJuntraBills();

        $this->section('ความจุแชทเว็บ');
        $this->checkChatCapacity();

        $this->section('งานค้างบน FB (ข้อมูลประกอบ cutover — ไม่บล็อก)');
        $this->checkInflight($settings);

        $this->line(str_repeat('─', 70));

        if ($this->blockers > 0) {
            $this->error("❌ ยังเปิดธงไม่ได้ — มี {$this->blockers} จุดที่ต้องแก้ก่อน");
            foreach ($this->blockerLabels as $label) {
                $this->line("   • {$label}");
            }

            return self::FAILURE;
        }

        if ($this->warnings > 0) {
            $this->warn("⚠️  0 blocker — เปิดได้ แต่มี {$this->warnings} จุดที่ควรรู้ไว้");
        } else {
            $this->info('✅ 0 blocker — พร้อมเปิดครบทุกจุด');
        }

        $this->newLine();
        $this->line('<options=bold>ขั้นต่อไป:</> php artisan fortune:web-only-cutover (ดูตัวเลขก่อน) → --execute → เปิดธงในหน้าแอดมิน');
        $this->line('            → php artisan fortune:web-only-cutover --refresh-profile (อัปเดตเมนู Messenger ทุกเพจ)');
        $this->newLine();

        return self::SUCCESS;
    }

    // ── ธงและปลายทาง ─────────────────────────────────────────────

    /**
     * สถานะธงปัจจุบัน — ยังไม่ migrate = blocker (เปิดจากหน้าแอดมินไม่ได้)
     */
    protected function checkFlag(FortuneTellingSetting $settings): void
    {
        if (! $this->hasColumn('fortune_telling_settings', 'facebook_web_only')) {
            $this->fail_('คอลัมน์ facebook_web_only', 'ยังไม่ได้ migrate (php artisan migrate)');

            return;
        }

        $on = (bool) ($settings->facebook_web_only ?? false);
        $since = $settings->fb_web_only_since ?? null;
        $this->info_(
            'ธง facebook_web_only',
            $on ? 'เปิดอยู่'.($since ? ' ตั้งแต่ '.$since->format('Y-m-d H:i') : '') : 'ปิดอยู่ (ตรวจล่วงหน้าได้ตามปกติ)'
        );

        // LINE ต้องไม่เปลี่ยน — แสดงค่าปุ่มเว็บเดิมให้เห็น (ธงนี้ไม่แตะค่านี้)
        $this->info_(
            'LINE (enable_web_fortune_button)',
            ((bool) ($settings->enable_web_fortune_button ?? false) ? 'เปิด' : 'ปิด').' — ธง FB web-only ไม่เปลี่ยนค่านี้ LINE ทำงานเหมือนเดิม'
        );
    }

    /**
     * ลิงก์เว็บของ FB ต้องสร้างได้และ verify กลับได้ (ถ้าธงยังปิด = จำลองว่าเปิดธงแล้ว)
     */
    protected function checkWebLink(FortuneTellingSetting $settings): void
    {
        $label = 'ลิงก์เว็บของ FB (isEnabledFor facebook)';

        try {
            $probe = $settings->replicate();
            // จำลองสภาพหลังเปิดธง — ไม่ save (replicate ไม่มี id)
            $probe->setAttribute('facebook_web_only', true);
            $svc = $this->makeLinkService($probe);

            if (! $svc->isEnabledFor('facebook')) {
                $this->fail_($label, 'เปิดธงแล้วก็ยังสร้างลิงก์เว็บให้ลูกค้า FB ไม่ได้ → ลูกค้าเจอทางตัน');

                return;
            }

            $token = $svc->generateToken('facebook', '9000000000000001', '/chat');
            $payload = $svc->verifyToken($token);
            if (! is_array($payload) || ($payload['p'] ?? null) !== 'facebook') {
                $this->fail_($label, 'สร้าง token แล้ว verify กลับไม่ได้ (ตรวจ APP_KEY)');

                return;
            }

            $current = (bool) ($settings->facebook_web_only ?? false)
                ? 'ใช้ได้'
                : 'ใช้ได้เมื่อเปิดธง (ตอนนี้ธงยังปิด)';
            $this->ok($label, $current);
        } catch (\Throwable $e) {
            $this->fail_($label, 'ตรวจไม่ได้: '.$e->getMessage());
        }
    }

    /**
     * SSO ของเว็บจันทราต้องตอบ (HEAD ไม่ตาม redirect — 2xx/3xx/4xx = เซิร์ฟเวอร์ยังอยู่)
     */
    protected function checkSso(FortuneTellingSetting $settings): void
    {
        $label = 'SSO เว็บจันทรา';

        try {
            $url = $this->makeLinkService($settings)->getSsoUrl();
        } catch (\Throwable $e) {
            $this->fail_($label, 'อ่าน URL ไม่ได้: '.$e->getMessage());

            return;
        }

        if (! str_starts_with(strtolower($url), 'https://')) {
            $this->fail_($label, "URL ต้องเป็น https ({$url})");

            return;
        }

        try {
            $response = Http::timeout(8)->withOptions(['allow_redirects' => false])->head($url);
            $status = $response->status();

            if ($status >= 500 || $status === 0) {
                $this->fail_($label, "{$url} ตอบ HTTP {$status}");

                return;
            }

            $this->ok($label, "{$url} ตอบ HTTP {$status}");
        } catch (\Throwable $e) {
            $this->fail_($label, "{$url} ไม่ตอบ ({$e->getMessage()})");
        }
    }

    // ── ข้อความและเมนูฝั่ง FB ────────────────────────────────────

    /**
     * ชุดข้อความ DM mode=web ต้องมีพอ และทุกแถวที่เปิดต้องไม่มีเรื่องเงิน/ลิงก์ดิบ
     */
    protected function checkWebInvites(): void
    {
        $label = 'ข้อความ DM ชุด web';

        try {
            $rows = FortuneInviteMessage::query()
                ->where('mode', FortuneInviteMessage::MODE_WEB)
                ->where('is_active', true)
                ->get(['id', 'message']);
        } catch (\Throwable $e) {
            $this->fail_($label, 'ตรวจไม่ได้: '.$e->getMessage());

            return;
        }

        $safe = $rows->filter(fn ($r) => FortuneInviteMessage::isWebSafeText($r->message));
        $unsafe = $rows->reject(fn ($r) => FortuneInviteMessage::isWebSafeText($r->message));

        if ($safe->count() < self::MIN_WEB_INVITES) {
            $this->fail_(
                $label,
                "ใช้ได้ {$safe->count()} ข้อความ (ต้องมีอย่างน้อย ".self::MIN_WEB_INVITES.') → '
                    .'รัน php artisan db:seed --class=FortuneWebOnlyInviteMessageSeeder หรือเปิดเพิ่มในหน้าแอดมิน'
            );
        } elseif ($safe->count() < self::RECOMMENDED_WEB_INVITES) {
            $this->warn_($label, "ใช้ได้ {$safe->count()} ข้อความ — แนะนำ ".self::RECOMMENDED_WEB_INVITES.' ข้อความขึ้นไปให้ DM ไม่ซ้ำ');
        } else {
            $this->ok($label, "ใช้ได้ {$safe->count()} ข้อความ");
        }

        if ($unsafe->isNotEmpty()) {
            $this->warn_(
                'ข้อความ DM ชุด web ที่มีเรื่องเงิน/ลิงก์',
                $unsafe->count().' แถว (id: '.$unsafe->pluck('id')->implode(', ').') — ระบบข้ามให้ แต่ควรแก้หรือปิด'
            );
        }
    }

    /**
     * เมนู Messenger: (1) เวอร์ชันเว็บในโค้ดต้องไม่มีปุ่มราคา (2) ถ้าเปิดธงแล้ว เมนูจริงบน FB ทุกเพจต้องเป็นเวอร์ชันเว็บ
     */
    protected function checkMessengerMenu(FortuneTellingSetting $settings): void
    {
        $label = 'เมนู Messenger แบบเว็บ (ในโค้ด)';

        try {
            $svc = new FacebookWebhookService($settings);
            $menu = $this->callProtected($svc, 'buildWebPersistentMenuActions');
            $ice = $this->callProtected($svc, 'buildWebIceBreakerActions');
            $bad = $this->findPriceItems(array_merge($menu, $ice));

            if ($bad !== []) {
                $this->fail_($label, 'ยังมีปุ่มราคา/สมัครแยกบัญชี: '.implode(', ', $bad));
            } else {
                $this->ok($label, 'ไม่มีปุ่มราคา');
            }
        } catch (\Throwable $e) {
            $this->fail_($label, 'ตรวจไม่ได้: '.$e->getMessage());
        }

        if (! (bool) ($settings->facebook_web_only ?? false)) {
            $this->info_(
                'เมนู Messenger บน FB จริง',
                'ธงยังปิด — หลังเปิดธงต้องรัน fortune:web-only-cutover --refresh-profile แล้วรัน preflight อีกรอบ'
            );

            return;
        }

        foreach ($this->facebookPages() as $page) {
            $name = $page ? 'เพจ '.$page->display_label.' (#'.$page->id.')' : 'เพจหลัก';
            $pageLabel = "เมนู Messenger บน FB — {$name}";

            try {
                $result = FortunePageContext::run($page, function () {
                    return (new FacebookWebhookService(FortuneTellingSetting::getSettings()))->getMessengerProfile();
                });
            } catch (\Throwable $e) {
                $this->warn_($pageLabel, 'อ่านไม่ได้: '.$e->getMessage());

                continue;
            }

            if (! ($result['success'] ?? false)) {
                $this->warn_($pageLabel, 'อ่านไม่ได้: '.($result['message'] ?? 'ไม่ทราบสาเหตุ'));

                continue;
            }

            $items = [];
            foreach ((array) ($result['data'] ?? []) as $profile) {
                foreach ((array) ($profile['persistent_menu'] ?? []) as $menuLocale) {
                    $items = array_merge($items, $this->flattenMenu((array) ($menuLocale['call_to_actions'] ?? [])));
                }
            }

            $bad = $this->findPriceItems($items);
            if ($bad !== []) {
                $this->fail_($pageLabel, 'ยังเป็นเมนูเก่า ('.implode(', ', $bad).') → รัน fortune:web-only-cutover --refresh-profile');
            } else {
                $this->ok($pageLabel, 'ไม่มีปุ่มราคา');
            }
        }
    }

    /**
     * respond_in_comment = ส่งคำทำนายใต้คอมเมนต์ (ดูดวงบน FB ตรง ๆ) — ทั้งค่ากลางและที่เพจสาขาเขียนทับ
     */
    protected function checkRespondInComment(FortuneTellingSetting $settings): void
    {
        $label = 'ตอบคำทำนายใต้คอมเมนต์ (respond_in_comment)';

        if ((bool) ($settings->respond_in_comment ?? false)) {
            $this->fail_($label, 'เปิดอยู่ที่ค่ากลาง → ปิดในหน้าตั้งค่าก่อน');
        } else {
            $this->ok($label, 'ปิดอยู่');
        }

        foreach ($this->activePages() as $page) {
            $override = is_array($page->settings_override) ? $page->settings_override : [];
            if (array_key_exists('respond_in_comment', $override) && (bool) $override['respond_in_comment']) {
                $this->fail_($label.' — เพจ '.$page->display_label.' (#'.$page->id.')', 'สาขาเขียนทับเป็นเปิด → ลบคีย์นี้ใน settings_override');
            }
        }
    }

    /**
     * คีย์เวิร์ด fortune/faq ที่มีคำเรื่องเงิน — ใช้ร่วมกับ LINE (ห้ามแก้ตารางเพื่อ FB) บน FB ระบบข้ามให้แล้ว
     */
    protected function checkMoneyKeywords(): void
    {
        $label = 'คีย์เวิร์ด fortune/faq ที่มีคำเรื่องเงิน';

        try {
            $rows = LineBotKeyword::query()
                ->where('is_active', true)
                ->whereIn('category', ['fortune', 'faq'])
                ->get();
        } catch (\Throwable $e) {
            $this->warn_($label, 'ตรวจไม่ได้: '.$e->getMessage());

            return;
        }

        $hits = $rows->filter(function ($kw) {
            foreach (['response_text', 'response_flex_json', 'quick_reply_options'] as $field) {
                $value = $kw->getAttribute($field);
                $text = is_string($value) ? $value : (string) json_encode($value ?? '', JSON_UNESCAPED_UNICODE);
                if (FacebookWebhookService::containsMoneyText($text)) {
                    return true;
                }
            }

            return false;
        });

        if ($hits->isEmpty()) {
            $this->ok($label, 'ไม่มี');

            return;
        }

        $detail = $hits->count().' คำ ('.$hits->pluck('keyword')->take(10)->implode(', ').') — บน FB ระบบข้ามให้แล้ว แต่ LINE ยังใช้';

        if ($this->option('ack-money-keywords')) {
            $this->warn_($label, $detail.' [รับทราบแล้ว]');
        } else {
            $this->fail_($label, $detail.' → ตรวจทีละคำ แล้วรันใหม่พร้อม --ack-money-keywords');
        }
    }

    // ── สายงาน MLM / ค่าคอม ──────────────────────────────────────

    /**
     * บิลเว็บจ่ายเป็น % ของ juntra — โหมด pv ทำให้ยอดสองเส้นต่างกันมาก (M3)
     */
    protected function checkCommissionMode(FortuneTellingSetting $settings): void
    {
        $label = 'โหมดค่าคอม (fortune_commission_mode)';
        $mode = (string) ($settings->fortune_commission_mode ?? 'static');

        if ($mode === 'static') {
            $this->ok($label, 'static');

            return;
        }

        if ($this->option('ack-commission-mode')) {
            $this->warn_($label, "{$mode} [เจ้าของรับทราบแล้ว]");
        } else {
            $this->fail_($label, "{$mode} — ต้องเป็น static หรือรันใหม่พร้อม --ack-commission-mode");
        }
    }

    /**
     * เพจสาขาที่เขียนทับคีย์ affiliate/ค่าคอม = blocker · คีย์โหมด/ธง = เตือน (ธงเขียนทับไม่ได้อยู่แล้ว)
     */
    protected function checkPageOverrides(): void
    {
        $label = 'เพจสาขาเขียนทับค่าคอม/affiliate';
        $found = false;

        foreach ($this->activePages() as $page) {
            $override = is_array($page->settings_override) ? $page->settings_override : [];
            if ($override === []) {
                continue;
            }

            $name = 'เพจ '.$page->display_label.' (#'.$page->id.')';
            $affiliateKeys = array_values(array_filter(
                array_keys($override),
                fn ($k) => preg_match(self::AFFILIATE_OVERRIDE_REGEX, (string) $k) === 1
            ));

            if ($affiliateKeys !== []) {
                $found = true;
                $this->fail_("{$label} — {$name}", implode(', ', $affiliateKeys).' → ลบออกจาก settings_override');
            }

            $modeKeys = array_values(array_intersect(
                array_keys($override),
                array_merge(FortuneTellingSetting::PAGE_OVERRIDE_DENY_LIST, ['fortune_bot_mode', 'enable_web_fortune_button'])
            ));
            if ($modeKeys !== []) {
                $this->warn_("คีย์โหมด/ธงที่เพจสาขาเขียนทับ — {$name}", implode(', ', $modeKeys).' (ธง FB web-only เขียนทับไม่ได้ แต่ควรลบให้สะอาด)');
            }
        }

        if (! $found) {
            $this->ok($label, 'ไม่มี');
        }
    }

    /**
     * ต้องเห็นบิล JW- ภายใน 24 ชม. (พิสูจน์ว่า juntraweb ส่งบิลเข้าผังจริง — M10)
     */
    protected function checkJuntraBills(): void
    {
        $label = 'บิลเว็บจันทรา (JW-) ใน '.self::JUNTRA_BILL_WINDOW_HOURS.' ชม.';

        try {
            $latest = FortuneReading::query()
                ->where('reading_type', FortuneReading::READING_TYPE_JUNTRA)
                ->where('bill_reference', 'like', FortuneReading::JUNTRA_BILL_PREFIX.'%')
                ->where('created_at', '>=', now()->subHours(self::JUNTRA_BILL_WINDOW_HOURS))
                ->latest('created_at')
                ->first(['id', 'bill_reference', 'created_at']);
        } catch (\Throwable $e) {
            $this->fail_($label, 'ตรวจไม่ได้: '.$e->getMessage());

            return;
        }

        if (! $latest) {
            $this->fail_($label, 'ไม่เห็นเลย → ตรวจ affiliate:sync-bills ฝั่ง juntraweb (deploy จาก origin/main)');

            return;
        }

        $this->ok($label, 'ล่าสุด '.$latest->bill_reference.' เมื่อ '.$latest->created_at->format('Y-m-d H:i'));
    }

    // ── ความจุแชทเว็บ ────────────────────────────────────────────

    /**
     * เพดาน jwchat ต้อง ≥ peak ข้อความ FB/นาที × 1.5 — ไม่งั้นลูกค้าที่ย้ายมาชน 429 แล้วได้คำตอบที่ไม่รู้ประวัติ
     */
    protected function checkChatCapacity(): void
    {
        $label = 'เพดานแชทเว็บ (jwchat)';

        $ceiling = $this->option('chat-ceiling') !== null && $this->option('chat-ceiling') !== ''
            ? (float) $this->option('chat-ceiling')
            : $this->detectChatCeiling();

        $peak = $this->option('peak-per-minute') !== null && $this->option('peak-per-minute') !== ''
            ? (float) $this->option('peak-per-minute')
            : $this->detectFbPeakPerMinute();

        if ($ceiling === null) {
            $this->warn_($label, 'อ่านเพดานไม่ได้ — ระบุเองด้วย --chat-ceiling=');

            return;
        }

        if ($peak === null) {
            $this->warn_($label, 'เพดาน '.$this->num($ceiling).'/นาที · หา peak ข้อความ FB ไม่ได้ — ระบุเองด้วย --peak-per-minute=');

            return;
        }

        $need = $peak * self::CHAT_HEADROOM;
        $detail = 'เพดาน '.$this->num($ceiling).'/นาที · peak FB '.$this->num($peak).'/นาที × '.self::CHAT_HEADROOM.' = '.$this->num($need);

        if ($ceiling < $need) {
            $this->fail_($label, $detail.' → เพดานต่ำเกิน');
        } else {
            $this->ok($label, $detail);
        }
    }

    /**
     * อ่านเพดานต่อนาทีของ route chat/send — รองรับทั้ง throttle:N,M,prefix และ named limiter (เอาค่าสูงสุด = เพดานรวม)
     */
    protected function detectChatCeiling(): ?float
    {
        try {
            $route = collect(Route::getRoutes()->getRoutes())
                ->first(fn ($r) => str_contains($r->uri(), 'juntra/server/chat/send'));
            if (! $route) {
                return null;
            }

            // throttle หลายชั้น (กลุ่ม api + กลุ่ม chat) — ชั้นที่แคบที่สุดคือเพดานจริง
            $ceilings = [];

            foreach ($route->gatherMiddleware() as $mw) {
                if (! is_string($mw) || ! str_starts_with($mw, 'throttle:')) {
                    continue;
                }

                $args = explode(',', substr($mw, strlen('throttle:')));

                if (is_numeric($args[0] ?? null)) {
                    $decay = max(1, (int) ($args[1] ?? 1));
                    $ceilings[] = (float) $args[0] / $decay;

                    continue;
                }

                $limiter = RateLimiter::limiter((string) $args[0]);
                if (! $limiter) {
                    continue;
                }

                $request = Request::create('/'.$route->uri(), 'POST', ['user_ref' => 'preflight']);
                $limits = $limiter($request);
                $limits = is_array($limits) ? $limits : [$limits];

                // named limiter อาจมีทั้งเพดานต่อคนและเพดานรวม — เพดานรวม = ค่าสูงสุดในชุด
                $perMinute = [];
                foreach ($limits as $limit) {
                    if (! is_object($limit) || ! isset($limit->maxAttempts)) {
                        continue;
                    }
                    $seconds = isset($limit->decaySeconds)
                        ? (int) $limit->decaySeconds
                        : (int) (($limit->decayMinutes ?? 1) * 60);
                    $perMinute[] = (float) $limit->maxAttempts * 60 / max(1, $seconds);
                }

                if ($perMinute !== []) {
                    $ceilings[] = max($perMinute);
                }
            }

            return $ceilings === [] ? null : min($ceilings);
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * peak ข้อความลูกค้า FB ต่อนาทีใน 7 วันล่าสุด (จาก line_bot_messages ของบทสนทนา platform=facebook)
     */
    protected function detectFbPeakPerMinute(): ?float
    {
        try {
            if (! Schema::hasTable('line_bot_messages') || ! Schema::hasTable('line_bot_conversations')
                || ! Schema::hasColumn('line_bot_conversations', 'platform')) {
                return null;
            }

            $row = DB::table('line_bot_messages as m')
                ->join('line_bot_conversations as c', 'c.id', '=', 'm.conversation_id')
                ->where('c.platform', 'facebook')
                ->where('m.role', 'user')
                ->where('m.created_at', '>=', now()->subDays(self::PEAK_LOOKBACK_DAYS))
                ->selectRaw("DATE_FORMAT(m.created_at, '%Y-%m-%d %H:%i') as minute_bucket, COUNT(*) as n")
                ->groupBy('minute_bucket')
                ->orderByDesc('n')
                ->first();

            return $row ? (float) $row->n : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ── งานค้างบน FB (Q1-Q9) ─────────────────────────────────────

    /**
     * นับงานค้างทั้งหมด — เป็นคำเตือนพร้อมจำนวนให้เจ้าของเห็นก่อน cutover
     */
    protected function checkInflight(FortuneTellingSetting $settings): void
    {
        $timeout = FortuneReading::billTimeoutMinutes();

        $this->countWarn('Q1 บิล FB ค้างจ่าย (หมดอายุเองภายใน '.$timeout.' นาที)', fn () => $this->fbReadings()
            ->where('is_paid', false)
            ->whereIn('conversation_status', self::PENDING_BILL_STATUSES)
            ->where('updated_at', '>=', now()->subMinutes($timeout))
            ->count());

        $this->countWarn('Q2 จ่ายแล้วแต่ยังส่งของไม่ครบ (ส่งต่อบน FB จนจบ)', fn () => $this->fbReadings()
            ->where('is_paid', true)
            ->whereIn('conversation_status', self::PAID_UNDELIVERED_STATUSES)
            ->count());

        $this->countWarn('Q3 Pro Session ที่ยังเปิด', fn () => $this->fbReadings()
            ->where('is_paid', true)
            ->where('conversation_state->pro_session_active', true)
            ->count());

        $this->countWarn('Q4 แอดมินเทคโอเวอร์อยู่ (cutover ไม่แตะ)', fn () => $this->fbReadings()
            ->whereNotNull('admin_takeover_until')
            ->where('admin_takeover_until', '>', now())
            ->count());

        $this->countWarn('Q6 สิทธิ์ "ขอดูในแชท FB" ที่ต้องเพิกถอน', fn () => DB::table('fortune_user_credits')
            ->whereRaw("COALESCE(platform, 'facebook') = 'facebook'")
            ->whereNotNull('fb_fallback_granted_at')
            ->count());

        $this->countWarn('Q7 เครดิตฟรี/unlimited ฝั่ง FB (ไม่ย้ายไปเว็บ)', fn () => DB::table('fortune_user_credits')
            ->whereRaw("COALESCE(platform, 'facebook') = 'facebook'")
            ->where(function ($q) {
                $q->whereRaw('COALESCE(bonus_credits, 0) - COALESCE(credits_used, 0) > 0')
                    ->orWhere(function ($q2) {
                        $q2->where('is_unlimited', true)
                            ->where(fn ($q3) => $q3->whereNull('unlimited_until')->orWhere('unlimited_until', '>=', now()->toDateString()));
                    });
            })
            ->count());

        $this->countWarn('Q8 เงินรอแอดมินตรวจ 7 วัน (เงินกำพร้า)', fn () => DB::table('sms_payment_notifications')
            ->where('status', 'requires_admin_review')
            ->where('created_at', '>=', now()->subDays(7))
            ->count());

        $this->countWarn('Q9 คำถามลูกค้า FB ที่รอแอดมินตอบ', fn () => DB::table('fortune_saved_questions')
            ->where('platform', 'facebook')
            ->whereNull('replied_at')
            ->count());

        $this->countWarn('linked_asid ที่ผูกกับหลาย PSID (ตัวตนแตก)', function () {
            if (! $this->hasColumn('fortune_customer_personas', 'linked_asid')) {
                return 0;
            }

            return DB::table('fortune_customer_personas')
                ->whereNotNull('linked_asid')
                ->where('linked_asid', '!=', '')
                ->select('linked_asid')
                ->groupBy('linked_asid')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();
        });
    }

    // ── ตัวช่วย ──────────────────────────────────────────────────

    /**
     * แถว fortune_readings ของ FB (แถวเก่า platform ว่าง = FB) ไม่รวมบิลจันทรา
     */
    protected function fbReadings()
    {
        return FortuneReading::query()
            ->whereRaw("COALESCE(platform, 'facebook') = 'facebook'")
            ->withoutJuntra();
    }

    /**
     * สร้าง FortuneWebLinkService ผ่าน container (เทสต์ bind ตัวปลอมได้)
     */
    protected function makeLinkService(FortuneTellingSetting $settings): FortuneWebLinkService
    {
        return app()->makeWith(FortuneWebLinkService::class, ['settings' => $settings]);
    }

    /**
     * เพจสาขาที่เปิดใช้งาน (ตารางยังไม่มี = ว่าง)
     *
     * @return \Illuminate\Support\Collection<int, FortunePage>
     */
    protected function activePages()
    {
        try {
            return FortunePage::query()->active()->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * เพจ FB ที่ต้องเช็คเมนู — null = เพจหลัก (token ในแถวกลาง) + สาขา FB ที่มี token
     *
     * @return array<int, FortunePage|null>
     */
    protected function facebookPages(): array
    {
        $pages = [null];
        foreach ($this->activePages() as $page) {
            if (($page->platform ?? 'facebook') === 'facebook' && ! empty($page->page_access_token)) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /**
     * แตกเมนูซ้อน (nested) ให้เป็นรายการแบน
     */
    protected function flattenMenu(array $items): array
    {
        $flat = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $flat[] = $item;
            if (! empty($item['call_to_actions']) && is_array($item['call_to_actions'])) {
                $flat = array_merge($flat, $this->flattenMenu($item['call_to_actions']));
            }
        }

        return $flat;
    }

    /**
     * หาปุ่มที่ยังขาย: payload ราคา · ลิงก์ /auth/facebook (สมัครแยกบัญชี) · ข้อความเรื่องเงิน
     *
     * @return array<int, string> ป้ายของปุ่มที่ไม่ผ่าน
     */
    protected function findPriceItems(array $items): array
    {
        $bad = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $payload = (string) ($item['payload'] ?? '');
            $url = (string) ($item['url'] ?? '');
            $title = (string) ($item['title'] ?? $item['question'] ?? '');

            if (in_array($payload, self::PRICE_MENU_PAYLOADS, true)
                || preg_match('/^TIER_/', $payload) === 1
                || str_contains($url, '/auth/facebook')
                || FacebookWebhookService::containsMoneyText($title)) {
                $bad[] = $title !== '' ? $title : ($payload !== '' ? $payload : $url);
            }
        }

        return $bad;
    }

    /**
     * เรียกเมธอด protected ของ service (ใช้ตัวสร้างเมนูตัวจริง — ไม่คัดลอกมาเทียบ)
     */
    protected function callProtected(object $target, string $method): array
    {
        $ref = new \ReflectionMethod($target, $method);
        $ref->setAccessible(true);

        return (array) $ref->invoke($target);
    }

    protected function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * นับแล้วเตือนถ้ามากกว่า 0 · นับไม่ได้ = เตือนพร้อมสาเหตุ
     */
    protected function countWarn(string $label, callable $counter): void
    {
        try {
            $n = (int) $counter();
        } catch (\Throwable $e) {
            $this->warn_($label, 'ตรวจไม่ได้: '.mb_substr($e->getMessage(), 0, 160));

            return;
        }

        if ($n > 0) {
            $this->warn_($label, number_format($n).' รายการ');
        } else {
            $this->ok($label, '0');
        }
    }

    protected function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ','), '0'), '.');
    }

    protected function section(string $title): void
    {
        $this->newLine();
        $this->line("<fg=cyan;options=bold>▸ {$title}</>");
    }

    protected function ok(string $label, string $detail): void
    {
        $this->line("  <fg=green>✅</> <options=bold>{$label}</>: {$detail}");
    }

    protected function warn_(string $label, string $detail): void
    {
        $this->warnings++;
        $this->line("  <fg=yellow>⚠️ </> <options=bold>{$label}</>: {$detail}");
    }

    protected function fail_(string $label, string $detail): void
    {
        $this->blockers++;
        $this->blockerLabels[] = $label;
        $this->line("  <fg=red>❌</> <options=bold>BLOCKER {$label}</>: {$detail}");
    }

    protected function info_(string $label, string $detail): void
    {
        $this->line("  <fg=cyan>ℹ️ </> <options=bold>{$label}</>: {$detail}");
    }
}
