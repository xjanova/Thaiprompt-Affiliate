<?php

namespace App\Console\Commands;

use App\Models\FortunePage;
use App\Models\FortunePageRepost;
use App\Services\Fortune\FortuneRepostService;
use App\Support\FacebookGraphApi;
use App\Support\FacebookPagePostingGuard;
use App\Support\SafeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * 🛑 หยุด / ปลดการหยุด การโพสต์ลงเพจ Facebook ทีละเพจ
 *
 * เจ้าของสั่ง (2026-11-13): "หยุดโพสต์คอนเทนต์ทั้งหมดไปยังเพจจันทราเมืองเลยก่อน"
 *
 * หลักการ:
 *   - ยืนยันตัวเพจกับ Facebook สด ๆ ก่อนแก้อะไรทุกครั้ง (ไอดี + ชื่อตรงตัวอักษร)
 *     ชื่อ/ไอดีในโน้ตหรือในตารางเป็นแค่เบาะแส ไม่ใช่หลักฐาน
 *   - ปฏิเสธเพจหลัก (is_default) เสมอ
 *   - ไม่ปิดแชท ไม่ปิดตอบคอมเมนต์ ไม่แตะเพจอื่น ไม่ลบโพสต์ที่ขึ้นหน้าเพจไปแล้ว
 *   - โพสต์ตั้งเวลาที่ยกเลิก ถูกเก็บสำเนา (ไอดี/เวลา/ข้อความ) ไว้ก่อนลบ
 *   - ไม่มีทางไหนในคำสั่งนี้ที่ "โพสต์" — มีแต่อ่าน (GET) กับยกเลิก (DELETE)
 *
 * ตัวอย่าง:
 *   php artisan fortune:facebook-posting-pause                      # ดูชื่อ/ไอดีทุกเพจ เทียบกับ Facebook
 *   php artisan fortune:facebook-posting-pause --page-id=123        # ดูอย่างเดียว ไม่แก้
 *   php artisan fortune:facebook-posting-pause --page-id=123 --expected-name="ชื่อเพจ" --apply
 *   php artisan fortune:facebook-posting-pause --page-id=123 --expected-name="ชื่อเพจ" --resume
 *
 * @tip --resume ปลดแค่ "ตัวล็อก" — สวิตช์โพสต์อัตโนมัติ/รีโพสยังปิดอยู่ เจ้าของเปิดเองที่หลังบ้าน
 */
class FortunePauseFacebookPosting extends Command
{
    /** หมายเหตุที่เขียนลงสมุดรีโพสเมื่อยกเลิกเพราะคำสั่งหยุด */
    private const LEDGER_NOTE = 'เจ้าของสั่งหยุดโพสต์เพจนี้ — ยกเลิกแล้ว';

    protected $signature = 'fortune:facebook-posting-pause
                            {--page-id= : ไอดีเพจ Facebook (ตัวเลข) — ไม่ใช่รหัสสาขาหรือ id ในระบบ}
                            {--expected-name= : ชื่อเพจปัจจุบันบน Facebook ตรงตัวอักษร (บังคับเมื่อ --apply / --resume)}
                            {--apply : หยุดโพสต์จริง + ยกเลิกโพสต์ที่ตั้งเวลาค้างไว้บน Facebook}
                            {--resume : ปลดการหยุด (ไม่เปิดสวิตช์โพสต์ให้ — เจ้าของเปิดเองที่หลังบ้าน)}';

    protected $description = '🛑 หยุดโพสต์ลงเพจ Facebook หนึ่งเพจ โดยยืนยันตัวเพจกับ Facebook ก่อน (ไม่ใส่ --apply = ดูอย่างเดียว)';

    /**
     * จุดเข้า — ดักทุกข้อผิดพลาดแล้วรายงานเป็นข้อความ (ตัด token ออกเสมอ)
     *
     * @return int รหัสจบคำสั่ง (0 = สำเร็จและไม่มีอะไรค้าง)
     */
    public function handle(): int
    {
        try {
            return $this->executePause();
        } catch (\Throwable $e) {
            $this->error(SafeLog::redactSecrets($e->getMessage()));
            $this->warn('ถ้าบันทึกการหยุดไปแล้ว การหยุดยังมีผลอยู่ · การยกเลิกโพสต์ตั้งเวลาอาจยังไม่ครบ — แก้สาเหตุแล้วรันคำสั่งเดิมซ้ำ');

            return self::FAILURE;
        }
    }

    /**
     * ลำดับงานหลัก: ตรวจตัวเลือก → ยืนยันตัวเพจ → หยุด/ปลด → เก็บกวาดคิว → รายงาน
     *
     * @return int รหัสจบคำสั่ง
     *
     * @throws RuntimeException เมื่อตัวเลือกผิด หรือยืนยันตัวเพจไม่ผ่าน
     */
    private function executePause(): int
    {
        $pageId = trim((string) $this->option('page-id'));
        $expectedName = trim((string) $this->option('expected-name'));
        $apply = (bool) $this->option('apply');
        $resume = (bool) $this->option('resume');
        $writes = $apply || $resume;

        if ($apply && $resume) {
            throw new RuntimeException('เลือกได้อย่างเดียว: --apply (หยุด) หรือ --resume (ปลดการหยุด)');
        }

        if ($pageId === '') {
            if ($writes) {
                throw new RuntimeException('--apply / --resume ต้องระบุ --page-id และ --expected-name');
            }

            return $this->listPages();
        }

        if (! ctype_digit($pageId) || ($writes && $expectedName === '')) {
            throw new RuntimeException('ต้องใช้ไอดีเพจ Facebook ที่เป็นตัวเลข และ --expected-name ตรงตัวอักษรเมื่อจะ --apply / --resume');
        }

        $pages = FortunePage::query()->where('platform', 'facebook')->where('external_page_id', $pageId)->get();
        if ($pages->isEmpty() || $pages->contains(fn ($page) => $page->is_default)) {
            throw new RuntimeException('ไม่พบเพจนี้ในระบบ หรือเป็นเพจหลัก — ไม่ได้แก้ไขอะไร');
        }

        // ยืนยันจาก Facebook สด ๆ: token ของแถวนี้ต้องเป็นของเพจไอดีนี้ และชื่อต้องตรงที่สั่ง
        $token = (string) $pages->first()->page_access_token;
        $live = $this->graphGet('me', $token, ['fields' => 'id,name']);
        if ((string) ($live['id'] ?? '') !== $pageId
            || ($expectedName !== '' && ($live['name'] ?? '') !== $expectedName)) {
            throw new RuntimeException('ไอดีหรือชื่อเพจบน Facebook ไม่ตรงกับที่ระบุ — ไม่ได้แก้ไขอะไร');
        }
        $this->info('ยืนยันตัวเพจกับ Facebook แล้ว: '.json_encode($live, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        if ($resume) {
            return $this->resume($pageId, $live);
        }

        if ($apply) {
            $this->savePause($pageId, $live);
        }

        // โพสต์ที่ตั้งเวลาค้างอยู่ฝั่ง Facebook — ปิดสวิตช์ใน DB อย่างเดียวไม่หยุดของพวกนี้
        $scheduled = $this->scheduledPosts($pageId, $token);
        $this->line('โพสต์ตั้งเวลาที่ค้างบน Facebook: '.count($scheduled));
        $errors = 0;

        foreach ($scheduled as $post) {
            if (! $apply) {
                $this->line(json_encode($post, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

                continue;
            }

            try {
                $this->cancelScheduledPost($post, $pageId, $token, $pages);
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(SafeLog::redactSecrets($e->getMessage()));
            }
        }

        if (! $apply) {
            $this->comment('โหมดดูอย่างเดียว — ไม่ได้แก้ไขอะไร (ใส่ --apply พร้อม --expected-name เพื่อหยุดจริง)');

            return self::SUCCESS;
        }

        // นับซ้ำจาก Facebook — เหลือ = ยังหยุดไม่ครบ
        $remaining = $this->scheduledPosts($pageId, $token);
        $this->line('โพสต์ตั้งเวลาที่ยังเหลือบน Facebook หลังยกเลิก: '.count($remaining));
        $errors += count($remaining);

        $uncertain = $this->settleLedger($pages, count($remaining) === 0);
        if ($uncertain > 0) {
            $this->warn("แถวในสมุดรีโพสที่ยังไม่รู้ผล (กำลังส่ง/ตั้งเวลา): {$uncertain} — รันคำสั่งนี้ซ้ำภายหลังเพื่อตรวจกับ Facebook อีกรอบ");
        }
        $errors += $uncertain;

        $this->warn('คำขอที่กำลังส่งอยู่ ณ วินาทีที่สั่งหยุด และคิวนอกระบบนี้ (เช่น Meta Business Suite หรือแอปอื่น) คำสั่งนี้พิสูจน์ให้ไม่ได้ — ดูหน้าเพจซ้ำอีกครั้ง');

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * แสดงชื่อ/ไอดีที่เก็บในระบบ เทียบกับที่ Facebook ตอบจริง ของทุกเพจ (อ่านอย่างเดียว)
     *
     * @return int รหัสจบคำสั่ง (สำเร็จเสมอ — เพจที่ตรวจไม่ได้จะเตือนเป็นรายตัว)
     */
    private function listPages(): int
    {
        foreach (FortunePage::query()->where('platform', 'facebook')->orderBy('id')->get() as $page) {
            try {
                $live = $this->graphGet('me', (string) $page->page_access_token, ['fields' => 'id,name']);
                $this->line(json_encode([
                    'stored_name' => $page->name,
                    'stored_page_id' => $page->external_page_id,
                    'live_id' => $live['id'] ?? null,
                    'live_name' => $live['name'] ?? null,
                    'identity_matches' => (string) ($live['id'] ?? '') === (string) $page->external_page_id,
                    'paused' => (bool) (($page->settings_override ?? [])[FacebookPagePostingGuard::PAUSED_KEY] ?? false),
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            } catch (\Throwable $e) {
                $this->warn("ตรวจเพจ {$page->external_page_id} ({$page->name}) ไม่ได้: ".SafeLog::redactSecrets($e->getMessage()));
            }
        }

        return self::SUCCESS;
    }

    /**
     * บันทึกการหยุด: ตั้งธง + ปิดสวิตช์โพสต์อัตโนมัติ/รีโพส + จำค่าเดิมไว้
     *
     * รันซ้ำได้ — ค่าเดิมที่จำไว้ครั้งแรกจะไม่ถูกเขียนทับ
     *
     * @param  string  $pageId  ไอดีเพจ Facebook ที่ยืนยันแล้ว
     * @param  array  $live  ข้อมูลเพจที่ Facebook ตอบ (id, name)
     *
     * @throws RuntimeException เมื่อเพจกลายเป็นเพจหลักระหว่างทาง
     */
    private function savePause(string $pageId, array $live): void
    {
        DB::transaction(function () use ($pageId, $live) {
            $locked = FortunePage::query()->where('platform', 'facebook')
                ->where('external_page_id', $pageId)->lockForUpdate()->get();

            foreach ($locked as $page) {
                if ($page->is_default) {
                    throw new RuntimeException('เพจนี้กลายเป็นเพจหลักระหว่างทำงาน — ไม่หยุดให้');
                }

                $overrides = $page->settings_override ?? [];
                $overrides[FacebookPagePostingGuard::SNAPSHOT_KEY] ??= [
                    'verified_name' => $live['name'],
                    'verified_page_id' => $pageId,
                    'paused_at' => now()->toIso8601String(),
                    'auto_post_enabled' => (bool) $page->auto_post_enabled,
                    'repost_enabled' => (bool) $page->repost_enabled,
                ];
                $overrides[FacebookPagePostingGuard::PAUSED_KEY] = true;

                $page->settings_override = $overrides;
                $page->auto_post_enabled = false;
                $page->repost_enabled = false;
                $page->save();
            }
        });

        $this->info('บันทึกการหยุดแล้ว: ปิดโพสต์อัตโนมัติและรีโพสของเพจนี้ · แชท คอมเมนต์ และเพจอื่นไม่ถูกแตะ');
    }

    /**
     * ยกเลิกโพสต์ตั้งเวลา 1 โพสต์ — ต้องพิสูจน์ได้ก่อนว่าเป็นของเพจนี้ ยังไม่ขึ้น และยังไม่ถึงเวลา
     *
     * @param  array  $post  รายการจาก /scheduled_posts
     * @param  string  $pageId  ไอดีเพจ Facebook
     * @param  string  $token  token ของเพจ
     * @param  Collection<int, FortunePage>  $pages  แถวสาขาของเพจนี้ในระบบ
     *
     * @throws RuntimeException เมื่อพิสูจน์ไม่ได้ เก็บสำเนาไม่ได้ หรือ Facebook ไม่ยืนยันการลบ
     */
    private function cancelScheduledPost(array $post, string $pageId, string $token, Collection $pages): void
    {
        $id = (string) $post['id'];
        $current = $this->graphGet($id, $token, ['fields' => 'id,is_published,scheduled_publish_time,from']);

        if (($current['is_published'] ?? null) !== false
            || (int) ($current['scheduled_publish_time'] ?? 0) <= now()->timestamp
            || (string) ($current['from']['id'] ?? '') !== $pageId) {
            throw new RuntimeException("พิสูจน์ไม่ได้ว่า {$id} เป็นโพสต์ของเพจนี้ที่ยังไม่ขึ้นและยังไม่ถึงเวลา — ไม่ลบ");
        }

        // เก็บสำเนาก่อนลบเสมอ — ลบแล้วเอาคืนจาก Facebook ไม่ได้
        $auditPath = "facebook-posting-pauses/{$pageId}/{$id}.json";
        $disk = Storage::disk('local');
        if (! $disk->exists($auditPath)
            && ! $disk->put($auditPath, json_encode($post, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException("เก็บสำเนาโพสต์ {$id} ไม่ได้ — ไม่ลบ");
        }

        $response = Http::timeout(30)->delete(FacebookGraphApi::url($id), ['access_token' => $token]);
        if (! $response->successful() || ! ($response->json() === true || $response->json('success') === true)) {
            throw new RuntimeException("Facebook ไม่ยืนยันการยกเลิก {$id} (HTTP {$response->status()})");
        }

        if (Schema::hasTable('fortune_page_reposts')) {
            FortunePageRepost::query()->whereIn('fortune_page_id', $pages->modelKeys())
                ->where('status', FortunePageRepost::STATUS_SCHEDULED)
                ->where(fn ($q) => $q->where('target_post_id', $id)->orWhere('target_object_id', $id))
                ->update(['status' => FortunePageRepost::STATUS_CANCELLED, 'error' => self::LEDGER_NOTE]);
        }

        $this->info("ยกเลิกโพสต์ตั้งเวลา {$id} แล้ว");
    }

    /**
     * ทำให้สมุดรีโพสในระบบตรงกับความจริงบน Facebook แล้วคืนจำนวนแถวที่ยังไม่รู้ผล
     *
     * ⚠️ Facebook ไม่คืน post_id ตอนตั้งเวลา "รูป" (published=false) — แถวพวกนี้จึงจับคู่กับ
     *    รายการ /scheduled_posts ไม่ได้ และจะค้างสถานะ "ตั้งเวลาแล้ว" ตลอดไป
     *    เมื่อ Facebook ยืนยันแล้วว่าไม่เหลือโพสต์ตั้งเวลาบนเพจ แถวที่ยังไม่ถึงเวลาจึงปิดได้อย่างพิสูจน์ได้
     *
     * @param  Collection<int, FortunePage>  $pages  แถวสาขาของเพจนี้ในระบบ
     * @param  bool  $remoteQueueEmpty  Facebook ยืนยันว่าไม่เหลือโพสต์ตั้งเวลาแล้ว
     * @return int จำนวนแถวที่ยังเป็น กำลังส่ง/ตั้งเวลา
     */
    private function settleLedger(Collection $pages, bool $remoteQueueEmpty): int
    {
        if (! Schema::hasTable('fortune_page_reposts')) {
            return 0;
        }

        $ids = $pages->modelKeys();

        if ($remoteQueueEmpty) {
            $closed = FortunePageRepost::query()->whereIn('fortune_page_id', $ids)
                ->where('status', FortunePageRepost::STATUS_SCHEDULED)
                ->where('slot_at', '>', now())
                ->update(['status' => FortunePageRepost::STATUS_CANCELLED, 'error' => self::LEDGER_NOTE]);

            if ($closed > 0) {
                $this->info("ปิดแถวในสมุดรีโพสที่ไม่มีโพสต์ค้างบน Facebook แล้ว: {$closed}");
            }
        }

        // แถวที่เลยเวลาแล้วแต่ยังไม่รู้ผล — cron รีโพสไม่วิ่งให้เพจที่ปิดสวิตช์แล้ว จึงต้องตรวจตรงนี้
        // (ตัวตรวจเดิมของระบบ: ถาม Facebook ว่าขึ้นจริงไหม + ปิดแถวที่ค้าง "กำลังส่ง" เกิน 30 นาที)
        $service = app(FortuneRepostService::class);
        foreach ($pages as $page) {
            try {
                $service->reconcile($page);
            } catch (\Throwable $e) {
                $this->warn('ตรวจสถานะสมุดรีโพสไม่ได้: '.SafeLog::redactSecrets($e->getMessage()));
            }
        }

        return FortunePageRepost::query()->whereIn('fortune_page_id', $ids)
            ->whereIn('status', [FortunePageRepost::STATUS_SENDING, FortunePageRepost::STATUS_SCHEDULED])
            ->count();
    }

    /**
     * ปลดการหยุด — เอาธงออก เก็บประวัติไว้ แต่ "ไม่" เปิดสวิตช์โพสต์ให้
     *
     * เปิดโพสต์กลับเป็นการตัดสินใจของเจ้าของ: ปลดล็อกที่นี่ แล้วไปเปิดสวิตช์เองที่หลังบ้าน
     * โพสต์ตั้งเวลาที่ยกเลิกไปแล้วไม่ถูกสร้างคืน (สำเนาอยู่ใน storage/app/facebook-posting-pauses)
     *
     * @param  string  $pageId  ไอดีเพจ Facebook ที่ยืนยันแล้ว
     * @param  array  $live  ข้อมูลเพจที่ Facebook ตอบ (id, name)
     * @return int รหัสจบคำสั่ง
     */
    private function resume(string $pageId, array $live): int
    {
        $lifted = [];

        DB::transaction(function () use ($pageId, &$lifted) {
            $locked = FortunePage::query()->where('platform', 'facebook')
                ->where('external_page_id', $pageId)->lockForUpdate()->get();

            foreach ($locked as $page) {
                $overrides = $page->settings_override ?? [];
                if (! filter_var($overrides[FacebookPagePostingGuard::PAUSED_KEY] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                // ย้ายค่าที่จำไว้ไปเป็น "ประวัติ" — หยุดรอบหน้าจะได้จำค่าสวิตช์ ณ ตอนนั้นใหม่
                $snapshot = (array) ($overrides[FacebookPagePostingGuard::SNAPSHOT_KEY] ?? []);
                unset($overrides[FacebookPagePostingGuard::PAUSED_KEY], $overrides[FacebookPagePostingGuard::SNAPSHOT_KEY]);
                $overrides[FacebookPagePostingGuard::LAST_PAUSE_KEY] = $snapshot + ['resumed_at' => now()->toIso8601String()];

                $page->settings_override = $overrides;
                $page->save();
                $lifted[] = $snapshot;
            }
        });

        if ($lifted === []) {
            $this->info('เพจนี้ไม่ได้ถูกสั่งหยุดโพสต์อยู่ — ไม่มีอะไรต้องปลด');

            return self::SUCCESS;
        }

        $this->info("ปลดการหยุดโพสต์ของเพจ {$live['name']} ({$pageId}) แล้ว");
        foreach ($lifted as $snapshot) {
            $this->line(sprintf(
                'ค่าสวิตช์ก่อนถูกหยุด: โพสต์อัตโนมัติ=%s · รีโพส=%s',
                ($snapshot['auto_post_enabled'] ?? false) ? 'เปิด' : 'ปิด',
                ($snapshot['repost_enabled'] ?? false) ? 'เปิด' : 'ปิด'
            ));
        }
        $this->warn('สวิตช์โพสต์อัตโนมัติ/รีโพสยังปิดอยู่ — คำสั่งนี้ไม่เปิดให้ เจ้าของเปิดเองที่หลังบ้านเมื่อพร้อม · โพสต์ตั้งเวลาที่ยกเลิกไปแล้วไม่ถูกสร้างคืน');

        return self::SUCCESS;
    }

    /**
     * อ่านข้อมูลจาก Graph (GET เท่านั้น)
     *
     * @param  string  $path  path ของ Graph เช่น me หรือ {page}/scheduled_posts
     * @param  string  $token  token ของเพจ
     * @param  array  $params  query string เพิ่มเติม
     * @return array ข้อมูลที่ Facebook ตอบ
     *
     * @throws RuntimeException เมื่อไม่มี token หรือ Facebook ตอบผิดพลาด (ถือว่ายืนยันไม่ได้)
     */
    private function graphGet(string $path, string $token, array $params): array
    {
        if ($token === '') {
            throw new RuntimeException('เพจนี้ไม่มี token — ยืนยันกับ Facebook ไม่ได้');
        }

        $response = Http::timeout(30)->get(FacebookGraphApi::url($path), $params + ['access_token' => $token]);
        $data = $response->json();

        if (! $response->successful() || ! is_array($data) || isset($data['error'])) {
            throw new RuntimeException("อ่านข้อมูลจาก Facebook ไม่สำเร็จ (HTTP {$response->status()}) — ถือว่ายืนยันไม่ได้");
        }

        return $data;
    }

    /**
     * รายการโพสต์ตั้งเวลาทั้งหมดของเพจ (ไล่ทุกหน้า)
     *
     * อ่านไม่ครบ = โยน exception — ห้ามรายงานว่า "คิวว่าง" ทั้งที่ยังอ่านไม่หมด
     *
     * @param  string  $pageId  ไอดีเพจ Facebook
     * @param  string  $token  token ของเพจ
     * @return array<int, array<string, mixed>> รายการโพสต์ (id, message, scheduled_publish_time, is_published)
     *
     * @throws RuntimeException เมื่อคำตอบผิดรูป ไอดีไม่ใช่ของเพจนี้ หรือไล่หน้าไม่จบ
     */
    private function scheduledPosts(string $pageId, string $token): array
    {
        $posts = [];
        $after = null;
        $seen = [];

        do {
            $data = $this->graphGet($pageId.'/scheduled_posts', $token, array_filter([
                'fields' => 'id,is_published,scheduled_publish_time,message',
                'limit' => 100,
                'after' => $after,
            ], fn ($value) => $value !== null));

            if (! isset($data['data']) || ! is_array($data['data'])) {
                throw new RuntimeException('Facebook ตอบรายการโพสต์ตั้งเวลาผิดรูป — อ่านไม่ครบ');
            }

            foreach ($data['data'] as $post) {
                if (! preg_match('/^'.preg_quote($pageId, '/').'_[0-9]+$/', (string) ($post['id'] ?? ''))) {
                    throw new RuntimeException('พบโพสต์ตั้งเวลาที่ไอดีไม่ใช่ของเพจนี้ — ไม่ยกเลิกอะไรทั้งสิ้น');
                }
                $posts[$post['id']] = array_intersect_key($post, array_flip(['id', 'message', 'scheduled_publish_time', 'is_published']));
            }

            if (empty($data['paging']['next'])) {
                break;
            }

            $after = $data['paging']['cursors']['after'] ?? null;
            if (! is_string($after) || $after === '' || isset($seen[$after]) || count($seen) >= 100) {
                throw new RuntimeException('ไล่หน้ารายการโพสต์ตั้งเวลาไม่จบ — ยืนยันไม่ได้ว่าคิวว่าง');
            }
            $seen[$after] = true;
        } while (true);

        return array_values($posts);
    }
}
