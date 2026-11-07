<?php

namespace App\Services\Fortune;

use App\Models\FortunePage;
use App\Models\FortunePageRepost;
use App\Support\SafeLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * 🔁 FortuneRepostService — รีโพสคอนเทนต์จากเพจต้นทาง (ปกติ = เพจหลัก) ลงเพจสาขาอัตโนมัติ
 *
 * เจ้าของสั่ง (2026-11-07): "รีโพสจากเพจหลักแบบอัตโนมัติ ตารางเวลาเหลื่อมกัน 30 นาที
 *                           ใช้คอนเทนต์จากเพจหลัก โดยไม่ซ้ำกับเพจหลักในแต่ละวัน"
 *
 * กติกา 3 ข้อที่ทั้งคลาสยึดไว้:
 *   1. ช่องเวลา = เวลาที่เพจต้นทางโพส (ทั้งที่ขึ้นแล้วและที่ตั้งเวลาไว้) + offset ของสาขา
 *      ⇒ ต้นทางโพส 07:00 สาขาโพส 07:30 · ต้นทางเปลี่ยนตารางวันไหน สาขาตามเองโดยไม่ต้องตั้งใหม่
 *   2. คอนเทนต์ที่หยิบมาลง ต้องเป็นโพสที่ต้นทางลงไว้ "ก่อนวันของช่องนั้น" เท่านั้น
 *      ⇒ วันเดียวกันสองเพจไม่มีโพสซ้ำกัน (สาขาตามหลังต้นทางอย่างน้อย 1 วัน)
 *   3. โพสต้นฉบับ 1 ใบ ลงสาขาเดียวกันได้ครั้งเดียว (unique ในตาราง ไม่ใช่แค่ในโค้ด)
 *
 * ⚠️ กับดักที่ออกแบบกันไว้:
 *   - โพสที่ผูกกับวันที่ (ดวงรายวัน 06/11/2569) เอาไปลงวันอื่น = บอกดวงผิดวัน → ตัดด้วย exclude_keywords + regex วันที่
 *   - Facebook ตั้งเวลาได้ต้องล่วงหน้าอย่างน้อย 10 นาที → ช่องที่ใกล้กว่า MIN_LEAD_SECONDS ข้าม (ไม่ใช่ error)
 *   - จองแถวในสมุด (status=sending) ก่อนยิง Graph — ถ้ายิงผ่านแต่เขียน DB ไม่ทัน รอบหน้าจะไม่โพสซ้ำ
 *   - ห้ามพิมพ์ token ลง log/DB/หน้าจอ — ข้อความ error ทุกตัวผ่าน SafeLog::redactSecrets()
 */
class FortuneRepostService
{
    public const GRAPH = 'https://graph.facebook.com/v21.0/';

    public const TZ = 'Asia/Bangkok';

    /** ล่วงหน้าขั้นต่ำก่อนตั้งเวลา (Facebook บังคับ 10 นาที + กันเวลาเดินระหว่างส่ง 2 นาที) */
    public const MIN_LEAD_SECONDS = 720;

    /** ลองโพสอื่นแทนได้กี่ใบต่อ 1 ช่อง ถ้าใบแรกส่งไม่ผ่าน */
    public const MAX_ATTEMPTS_PER_SLOT = 3;

    /** ยิง Graph สร้างโพสได้สูงสุดกี่ครั้งต่อรอบต่อสาขา (กันยิงถล่มตอนเปิดครั้งแรก) */
    public const MAX_PUBLISH_PER_RUN = 30;

    /** ดึงคลังโพสต้นทางสูงสุดกี่หน้า (หน้าละ 100) */
    public const MAX_SOURCE_PAGES = 6;

    /** รหัส exception: ส่งคำขอสร้างโพสแล้วแต่ไม่ได้คำตอบ (Facebook อาจรับไปแล้ว) */
    protected const AMBIGUOUS_SEND = 599;

    /** วันที่แบบไทย/สากลในข้อความ = โพสผูกวัน ห้ามเอาไปลงวันอื่น */
    protected const DATE_PATTERN = '/(?<!\d)\d{1,2}\/\d{1,2}\/(?:25|20)\d{2}(?!\d)/u';

    /**
     * วางแผน + ตั้งเวลาโพสลงสาขา 1 สาขา
     *
     * @param  bool  $dryRun  true = ดูแผนเฉยๆ ไม่ยิง Graph สร้างโพส
     * @param  bool  $force  true = ทำแม้สวิตช์รีโพสของสาขานี้ปิดอยู่ (ใช้กับปุ่ม "ดูตัวอย่าง" เท่านั้น)
     * @return array{ok:bool,message:string,planned:array<int,array<string,mixed>>,skipped:array<int,string>}
     */
    public function run(FortunePage $target, bool $dryRun = false, bool $force = false, ?CarbonImmutable $now = null): array
    {
        $now = ($now ?? CarbonImmutable::now())->setTimezone(self::TZ);
        $report = ['ok' => false, 'message' => '', 'planned' => [], 'skipped' => []];

        // ── 1. ตรวจความพร้อม ──────────────────────────────────────────
        if (! $force && ! $target->repost_enabled) {
            $report['message'] = 'สาขานี้ปิดรีโพสอยู่';

            return $report;
        }

        if ($target->platform !== 'facebook') {
            $report['message'] = 'รีโพสได้เฉพาะเพจ Facebook';

            return $report;
        }

        if (! $force && ! $target->is_active) {
            $report['message'] = 'สาขานี้ปิดใช้งานอยู่';

            return $report;
        }

        $source = $this->resolveSource($target);

        if (! $source) {
            $report['message'] = 'ไม่พบเพจต้นทาง — เลือกเพจต้นทางหรือกำหนดสาขาหลักก่อน';

            return $report;
        }

        if ((int) $source->id === (int) $target->id) {
            $report['message'] = 'เพจต้นทางกับเพจปลายทางเป็นเพจเดียวกัน';

            return $report;
        }

        $sourceToken = (string) $source->page_access_token;
        $targetToken = (string) $target->page_access_token;

        if ($sourceToken === '' || $targetToken === '') {
            $report['message'] = $sourceToken === ''
                ? "เพจต้นทาง \"{$source->name}\" ยังไม่มี token"
                : 'เพจสาขานี้ยังไม่มี token';

            return $report;
        }

        $cfg = $target->repostConfig();
        $offset = max(0, (int) ($target->repost_offset_minutes ?? 30));
        $maxPerDay = max(0, (int) ($target->repost_max_per_day ?? 0));

        if ($cfg['media_types'] === []) {
            $report['message'] = 'ยังไม่ได้เลือกประเภทโพสที่จะรีโพส';

            return $report;
        }

        // ── 2. ช่องเวลา (เวลาต้นทาง + offset) ในหน้าต่างที่ตั้งเวลาได้ ─────────
        $windowStart = $now->addSeconds(self::MIN_LEAD_SECONDS);
        $windowEnd = $now->addHours($cfg['lookahead_hours']);

        try {
            $slots = $this->sourceSlots(
                $source,
                $sourceToken,
                $windowStart->subMinutes($offset),
                $windowEnd->subMinutes($offset),
                $now
            );
        } catch (\Throwable $e) {
            $report['message'] = 'อ่านตารางโพสของเพจต้นทางไม่ได้: '.SafeLog::redactSecrets($e->getMessage());

            return $report;
        }

        $slots = array_map(function (array $slot) use ($offset) {
            $slot['target_at'] = $slot['at']->addMinutes($offset);

            return $slot;
        }, $slots);

        $openSlots = [];

        foreach ($slots as $slot) {
            if ($this->slotTaken($target, $slot)) {
                continue;
            }

            $openSlots[] = $slot;
        }

        if ($openSlots === []) {
            $report['ok'] = true;
            $report['message'] = $slots === []
                ? 'เพจต้นทางยังไม่มีโพสในช่วงเวลาที่วางแผนได้'
                : 'ทุกช่องเวลามีโพสแล้ว';

            return $report;
        }

        // ── 3. คลังโพสต้นทางที่หยิบมาลงได้ ───────────────────────────────
        try {
            $library = $this->sourceLibrary($source, $sourceToken, $now->subDays($cfg['lookback_days']), $cfg);
        } catch (\Throwable $e) {
            $report['message'] = 'อ่านคลังโพสของเพจต้นทางไม่ได้: '.SafeLog::redactSecrets($e->getMessage());

            return $report;
        }

        $used = FortunePageRepost::query()
            ->where('fortune_page_id', $target->id)
            ->pluck('source_post_id')
            ->flip()
            ->all();

        $dayCounts = [];
        $published = 0;

        // ── 4. เติมทีละช่อง ───────────────────────────────────────────
        foreach ($openSlots as $slot) {
            /** @var CarbonImmutable $at */
            $at = $slot['target_at'];
            $dayKey = $at->format('Y-m-d');

            if ($maxPerDay > 0) {
                $dayCounts[$dayKey] ??= $this->countForDay($target, $at);

                if ($dayCounts[$dayKey] >= $maxPerDay) {
                    $report['skipped'][] = $at->format('d/m H:i').' — ครบ '.$maxPerDay.' โพสของวันนี้แล้ว';

                    continue;
                }
            }

            $attempts = 0;
            $filled = false;

            foreach ($this->pickCandidates($library, $used, $slot, $at, $cfg['pick_order'], (int) $target->id) as $candidate) {
                if ($attempts >= self::MAX_ATTEMPTS_PER_SLOT) {
                    break;
                }

                if (! $dryRun && $published >= self::MAX_PUBLISH_PER_RUN) {
                    $report['skipped'][] = $at->format('d/m H:i').' — รอบนี้ส่งครบโควตาแล้ว ไว้รอบหน้า';
                    $filled = true;
                    break;
                }

                $attempts++;
                $used[$candidate['id']] = true;
                $caption = $this->buildCaption($candidate['message'], $cfg['caption_suffix']);

                $row = [
                    'slot_at' => $at->format('Y-m-d H:i'),
                    'media_type' => $candidate['media'],
                    'source_post_id' => $candidate['id'],
                    'source_created_at' => $candidate['created']->format('Y-m-d H:i'),
                    'caption' => mb_substr($caption, 0, 120),
                    'status' => 'preview',
                    'error' => null,
                ];

                if ($dryRun) {
                    $report['planned'][] = $row;
                    $filled = true;
                    break;
                }

                $result = $this->scheduleOne($target, $source, $sourceToken, $targetToken, $candidate, $slot, $at, $caption);
                $published++;
                $row['status'] = $result['status'];
                $row['error'] = $result['error'];
                $report['planned'][] = $row;

                // sending = หมดเวลารอ Facebook ตอบ ไม่รู้ว่าขึ้นคิวไปแล้วหรือยัง
                // ⇒ ถือว่าช่องนี้มีโพสแล้ว ห้ามลองใบอื่นลงช่องเดิม (เสี่ยงขึ้น 2 โพสซ้อนกัน)
                if (in_array($result['status'], [FortunePageRepost::STATUS_SCHEDULED, FortunePageRepost::STATUS_SENDING], true)) {
                    $filled = true;

                    if ($maxPerDay > 0) {
                        $dayCounts[$dayKey]++;
                    }

                    break;
                }
            }

            if (! $filled && $attempts === 0) {
                $report['skipped'][] = $at->format('d/m H:i').' — ไม่มีโพสในคลังที่ยังไม่เคยลง';
            }
        }

        $ok = collect($report['planned'])->whereIn('status', ['preview', FortunePageRepost::STATUS_SCHEDULED])->count();
        $fail = collect($report['planned'])->where('status', FortunePageRepost::STATUS_FAILED)->count();

        $report['ok'] = true;
        $report['message'] = $dryRun
            ? "แผนตัวอย่าง {$ok} โพส"
            : "ตั้งเวลาแล้ว {$ok} โพส".($fail > 0 ? " · ส่งไม่ผ่าน {$fail}" : '');

        return $report;
    }

    /**
     * อัปเดตสถานะโพสที่ถึงเวลาแล้ว (ขึ้นจริงไหม / ถูกลบไหม) + เคลียร์แถวที่ค้าง "กำลังส่ง"
     *
     * @return array{published:int,failed:int,checked:int}
     */
    public function reconcile(FortunePage $target, ?CarbonImmutable $now = null): array
    {
        $now = ($now ?? CarbonImmutable::now())->setTimezone(self::TZ);
        $stats = ['published' => 0, 'failed' => 0, 'checked' => 0];

        // แถวที่จองไว้แต่โปรเซสตายกลางทาง — ไม่รู้ว่า Graph รับไปหรือยัง ให้แอดมินเห็น
        $stats['failed'] += FortunePageRepost::query()
            ->where('fortune_page_id', $target->id)
            ->where('status', FortunePageRepost::STATUS_SENDING)
            ->where('updated_at', '<', $now->subMinutes(30))
            ->update([
                'status' => FortunePageRepost::STATUS_FAILED,
                'error' => 'ค้างระหว่างส่ง — โปรดเช็คหน้าเพจว่ามีโพสนี้หรือไม่',
            ]);

        $token = (string) $target->page_access_token;

        if ($token === '') {
            return $stats;
        }

        $due = FortunePageRepost::query()
            ->where('fortune_page_id', $target->id)
            ->where('status', FortunePageRepost::STATUS_SCHEDULED)
            ->where('slot_at', '<=', $now->subMinutes(10))
            ->where(fn ($q) => $q->whereNull('checked_at')->orWhere('checked_at', '<', $now->subMinutes(30)))
            ->orderBy('slot_at')
            ->limit(30)
            ->get();

        foreach ($due as $repost) {
            $stats['checked']++;

            try {
                $state = $this->remoteState($repost, $token);
            } catch (\Throwable $e) {
                $state = null;
            }

            if ($state === 'published') {
                $repost->status = FortunePageRepost::STATUS_PUBLISHED;
                $stats['published']++;
            } elseif ($state === 'missing') {
                $repost->status = FortunePageRepost::STATUS_FAILED;
                $repost->error = 'ไม่พบโพสบนเพจแล้ว (อาจถูกลบหรือ Facebook ไม่ยอมเผยแพร่)';
                $stats['failed']++;
            }

            $repost->checked_at = $now;
            $repost->save();
        }

        return $stats;
    }

    /**
     * ยกเลิกโพสที่ตั้งเวลาไว้ (ลบออกจาก Facebook ด้วย)
     *
     * @return array{ok:bool,message:string}
     */
    public function cancel(FortunePageRepost $repost): array
    {
        if (! $repost->isCancellable()) {
            return ['ok' => false, 'message' => 'โพสนี้ยกเลิกไม่ได้แล้ว (ถึงเวลาหรือไม่ได้อยู่ในสถานะตั้งเวลา)'];
        }

        $page = $repost->page;
        $token = (string) ($page?->page_access_token ?? '');
        $objectId = $repost->media_type === 'text'
            ? ($repost->target_post_id ?: $repost->target_object_id)
            : ($repost->target_object_id ?: $repost->target_post_id);

        if ($token === '' || ! $objectId) {
            return ['ok' => false, 'message' => 'ไม่มี token หรือรหัสโพสปลายทาง'];
        }

        $response = Http::timeout(30)->delete(self::GRAPH.$objectId, ['access_token' => $token]);

        // ลบไปแล้ว/ไม่มีอยู่แล้ว (code 100) ก็ถือว่ายกเลิกสำเร็จ — เป้าหมายคือ "ต้องไม่ขึ้นหน้าเพจ"
        $gone = (int) $response->json('error.code') === 100;

        if (! $response->successful() && ! $gone) {
            return ['ok' => false, 'message' => 'Facebook ไม่ยอมลบ: '.SafeLog::redactSecrets((string) $response->json('error.message', 'HTTP '.$response->status()))];
        }

        $repost->status = FortunePageRepost::STATUS_CANCELLED;
        $repost->error = null;
        $repost->save();

        return ['ok' => true, 'message' => 'ยกเลิกโพสเวลา '.$repost->slot_at->format('d/m H:i').' แล้ว'];
    }

    /**
     * เพจต้นทางของสาขา — ตั้งไว้เองหรือสาขาหลักของ facebook
     */
    public function resolveSource(FortunePage $target): ?FortunePage
    {
        if ($target->repost_source_page_id) {
            return FortunePage::query()
                ->where('platform', 'facebook')
                ->find($target->repost_source_page_id);
        }

        return FortunePage::query()
            ->where('platform', 'facebook')
            ->where('is_default', true)
            ->first();
    }

    /**
     * ช่องเวลาของเพจต้นทางในช่วง [from, to] — ทั้งโพสที่ขึ้นแล้ว และที่ตั้งเวลาไว้
     *
     * @return array<int, array{at:CarbonImmutable,media:string,post_id:string}>
     */
    protected function sourceSlots(FortunePage $source, string $token, CarbonImmutable $from, CarbonImmutable $to, CarbonImmutable $now): array
    {
        if ($to->lessThanOrEqualTo($from)) {
            return [];
        }

        $slots = [];
        $pageId = $source->external_page_id;

        // โพสที่ตั้งเวลาไว้ (ปกติคือตารางของวันนี้/พรุ่งนี้)
        $scheduled = $this->graphGet($pageId.'/scheduled_posts', $token, [
            'limit' => 100,
            'fields' => 'id,scheduled_publish_time,attachments{media_type,type}',
        ]);

        foreach ($scheduled['data'] ?? [] as $post) {
            $ts = (int) ($post['scheduled_publish_time'] ?? 0);

            if ($ts <= 0) {
                continue;
            }

            $at = CarbonImmutable::createFromTimestamp($ts, self::TZ);

            if ($at->betweenIncluded($from, $to)) {
                $slots[] = ['at' => $at, 'media' => $this->mediaOf($post) ?? 'photo', 'post_id' => (string) $post['id']];
            }
        }

        // โพสที่ขึ้นไปแล้วในช่วงนี้ (ช่องที่ต้นทางโพสสดๆ ไม่ได้ตั้งเวลา)
        if ($from->lessThan($now)) {
            $published = $this->graphGet($pageId.'/published_posts', $token, [
                'limit' => 100,
                'since' => $from->getTimestamp(),
                'until' => min($to->getTimestamp(), $now->getTimestamp()),
                'fields' => 'id,created_time,attachments{media_type,type}',
            ]);

            foreach ($published['data'] ?? [] as $post) {
                if (empty($post['created_time'])) {
                    continue;
                }

                $at = CarbonImmutable::parse($post['created_time'])->setTimezone(self::TZ);

                if ($at->betweenIncluded($from, $to)) {
                    $slots[] = ['at' => $at, 'media' => $this->mediaOf($post) ?? 'photo', 'post_id' => (string) $post['id']];
                }
            }
        }

        // ต้นทางโพส 2 ใบนาทีเดียวกัน = ช่องเดียว (ใบแรกเป็นตัวแทน)
        $unique = [];

        foreach ($slots as $slot) {
            $key = $slot['at']->format('Y-m-d H:i');
            $unique[$key] ??= $slot;
        }

        ksort($unique);

        return array_values($unique);
    }

    /**
     * คลังโพสต้นทางที่เอามาลงได้ (กรองประเภท/คำต้องห้าม/วันที่ไว้แล้ว)
     *
     * @return array<int, array{id:string,object_id:?string,media:string,message:string,created:CarbonImmutable,image:?string}>
     */
    protected function sourceLibrary(FortunePage $source, string $token, CarbonImmutable $since, array $cfg): array
    {
        $library = [];
        $path = $source->external_page_id.'/published_posts';
        $params = [
            'limit' => 100,
            'since' => $since->getTimestamp(),
            'fields' => 'id,created_time,message,status_type,attachments{media_type,type,target{id},media{image{src}}}',
        ];

        for ($i = 0; $i < self::MAX_SOURCE_PAGES; $i++) {
            $json = $this->graphGet($path, $token, $params);

            foreach ($json['data'] ?? [] as $post) {
                $item = $this->libraryItem($post, $cfg);

                if ($item !== null) {
                    $library[] = $item;
                }
            }

            $after = $json['paging']['cursors']['after'] ?? null;

            if (empty($json['paging']['next']) || ! $after) {
                break;
            }

            $params['after'] = $after;
        }

        return $library;
    }

    /**
     * แปลงโพสจาก Graph เป็นรายการในคลัง (null = เอามาลงไม่ได้)
     */
    protected function libraryItem(array $post, array $cfg): ?array
    {
        if (empty($post['id']) || empty($post['created_time'])) {
            return null;
        }

        // แชร์โพสคนอื่นมา — ไม่ใช่คอนเทนต์ของเรา
        if (($post['status_type'] ?? '') === 'shared_story') {
            return null;
        }

        $media = $this->mediaOf($post);
        $message = trim((string) ($post['message'] ?? ''));

        if ($media === null || ! in_array($media, $cfg['media_types'], true)) {
            return null;
        }

        if ($media === 'text' && $message === '') {
            return null;
        }

        if ($this->isExcluded($message, $cfg['exclude_keywords'])) {
            return null;
        }

        $att = $post['attachments']['data'][0] ?? [];

        return [
            'id' => (string) $post['id'],
            'object_id' => isset($att['target']['id']) ? (string) $att['target']['id'] : null,
            'media' => $media,
            'message' => $message,
            'created' => CarbonImmutable::parse($post['created_time'])->setTimezone(self::TZ),
            'image' => $att['media']['image']['src'] ?? null,
        ];
    }

    /**
     * ประเภทโพส: photo (รูปเดี่ยว) / video / text — null = ไม่รองรับ (อัลบั้ม, ลิงก์, แชร์)
     */
    protected function mediaOf(array $post): ?string
    {
        $att = $post['attachments']['data'][0] ?? null;

        if (! $att) {
            return 'text';
        }

        $mediaType = $att['media_type'] ?? '';
        $type = $att['type'] ?? '';

        if ($mediaType === 'video') {
            return 'video';
        }

        // อัลบั้มหลายรูปไม่รองรับ — ลงแค่รูปแรกจะเสียความหมายของโพส
        if ($mediaType === 'photo' && $type !== 'album') {
            return 'photo';
        }

        return null;
    }

    /**
     * โพสนี้ห้ามเอาไปลงวันอื่นไหม (ผูกกับวันที่ / มีคำที่แอดมินห้าม)
     */
    public function isExcluded(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && mb_stripos($message, $keyword) !== false) {
                return true;
            }
        }

        return (bool) preg_match(self::DATE_PATTERN, $message);
    }

    /**
     * ช่องนี้มีโพสแล้วไหม — เช็คทั้ง "โพสต้นทางเดียวกัน" และ "เวลาใกล้กัน ±2 นาที"
     *
     * ⚠️ เช็คด้วยโพสต้นทางด้วย เพราะถ้าแอดมินเปลี่ยน offset กลางวัน
     *    เวลาช่องจะเลื่อน → เช็คแค่เวลาจะลงซ้ำอีกใบในวันเดียวกัน
     */
    protected function slotTaken(FortunePage $target, array $slot): bool
    {
        /** @var CarbonImmutable $at */
        $at = $slot['target_at'];

        return FortunePageRepost::query()
            ->where('fortune_page_id', $target->id)
            ->whereIn('status', FortunePageRepost::OCCUPYING)
            ->where(function ($q) use ($slot, $at) {
                $q->where('slot_source_post_id', $slot['post_id'])
                    ->orWhereBetween('slot_at', [
                        $at->subMinutes(2)->format('Y-m-d H:i:s'),
                        $at->addMinutes(2)->format('Y-m-d H:i:s'),
                    ]);
            })
            ->exists();
    }

    /**
     * จำนวนโพสของสาขาในวันของช่องนี้ (ไว้คุม repost_max_per_day)
     */
    protected function countForDay(FortunePage $target, CarbonImmutable $at): int
    {
        return FortunePageRepost::query()
            ->where('fortune_page_id', $target->id)
            ->whereIn('status', FortunePageRepost::OCCUPYING)
            ->whereBetween('slot_at', [
                $at->startOfDay()->format('Y-m-d H:i:s'),
                $at->endOfDay()->format('Y-m-d H:i:s'),
            ])
            ->count();
    }

    /**
     * เรียงโพสที่ใช้ได้สำหรับช่องนี้ — ประเภทเดียวกับช่องของต้นทางก่อน แล้วค่อยประเภทอื่น
     *
     * เงื่อนไขหลัก: ต้องเป็นโพสที่ต้นทางลงไว้ "ก่อนวันของช่อง" (กติกาข้อ 2)
     *
     * @return array<int, array<string,mixed>>
     */
    protected function pickCandidates(array $library, array $used, array $slot, CarbonImmutable $at, string $order, int $pageId): array
    {
        $dayStart = $at->startOfDay();

        $pool = array_values(array_filter(
            $library,
            fn ($item) => ! isset($used[$item['id']]) && $item['created']->lessThan($dayStart)
        ));

        if ($order === 'random') {
            // สุ่มแบบคงที่ต่อสาขา+ช่อง — กดดูตัวอย่างซ้ำได้ผลเดิม และ 2 สาขาได้คนละใบ
            mt_srand(crc32($pageId.'|'.$at->getTimestamp()));
            shuffle($pool);
            mt_srand();
        } else {
            usort($pool, fn ($a, $b) => $order === 'oldest'
                ? $a['created'] <=> $b['created']
                : $b['created'] <=> $a['created']);
        }

        $same = array_values(array_filter($pool, fn ($i) => $i['media'] === $slot['media']));
        $other = array_values(array_filter($pool, fn ($i) => $i['media'] !== $slot['media']));

        return [...$same, ...$other];
    }

    /**
     * ข้อความโพส = ข้อความต้นฉบับ + ข้อความต่อท้ายของสาขา (ถ้าตั้งไว้)
     */
    public function buildCaption(string $message, string $suffix): string
    {
        $message = trim($message);
        $suffix = trim($suffix);

        if ($suffix === '') {
            return $message;
        }

        return $message === '' ? $suffix : $message."\n\n".$suffix;
    }

    /**
     * จองแถว → ยิง Graph ตั้งเวลา → บันทึกผล
     *
     * @return array{status:string,error:?string}
     */
    protected function scheduleOne(
        FortunePage $target,
        FortunePage $source,
        string $sourceToken,
        string $targetToken,
        array $candidate,
        array $slot,
        CarbonImmutable $at,
        string $caption
    ): array {
        try {
            // 🔒 จองก่อนยิง — unique (สาขา, โพสต้นฉบับ) กันโปรเซสซ้อนและกันโพสซ้ำถ้า DB ล่มหลังยิงผ่าน
            $repost = FortunePageRepost::create([
                'fortune_page_id' => $target->id,
                'source_fortune_page_id' => $source->id,
                'source_post_id' => $candidate['id'],
                'source_object_id' => $candidate['object_id'],
                'source_created_at' => $candidate['created'],
                'media_type' => $candidate['media'],
                'slot_at' => $at->format('Y-m-d H:i:s'),
                'slot_source_post_id' => $slot['post_id'],
                'status' => FortunePageRepost::STATUS_SENDING,
                'caption_preview' => mb_substr($caption, 0, 250),
            ]);
        } catch (QueryException $e) {
            return ['status' => FortunePageRepost::STATUS_FAILED, 'error' => 'โพสนี้ถูกจองไปแล้ว'];
        }

        try {
            $ids = $this->publishScheduled($target, $sourceToken, $targetToken, $candidate, $at, $caption);

            $repost->fill([
                'status' => FortunePageRepost::STATUS_SCHEDULED,
                'target_post_id' => $ids['post_id'],
                'target_object_id' => $ids['object_id'],
                'error' => null,
            ])->save();

            return ['status' => FortunePageRepost::STATUS_SCHEDULED, 'error' => null];
        } catch (\Throwable $e) {
            $error = mb_substr(SafeLog::redactSecrets($e->getMessage()), 0, 500);

            if ($e->getCode() === self::AMBIGUOUS_SEND) {
                // คงสถานะ sending ไว้ — reconcile() จะเปลี่ยนเป็น failed พร้อมข้อความให้แอดมินเช็คหน้าเพจ
                $repost->fill(['error' => $error])->save();

                return ['status' => FortunePageRepost::STATUS_SENDING, 'error' => $error];
            }

            $repost->fill(['status' => FortunePageRepost::STATUS_FAILED, 'error' => $error])->save();

            Log::warning('🔁 รีโพสลงสาขาไม่สำเร็จ', [
                'fortune_page_id' => $target->id,
                'source_post_id' => $candidate['id'],
                'slot_at' => $at->toDateTimeString(),
                'error' => $error,
            ]);

            return ['status' => FortunePageRepost::STATUS_FAILED, 'error' => $error];
        }
    }

    /**
     * ยิง Graph สร้างโพสแบบตั้งเวลา (published=false + scheduled_publish_time)
     *
     * @return array{post_id:?string,object_id:?string}
     */
    protected function publishScheduled(FortunePage $target, string $sourceToken, string $targetToken, array $candidate, CarbonImmutable $at, string $caption): array
    {
        $pageId = $target->external_page_id;
        $common = [
            'published' => 'false',
            'scheduled_publish_time' => (string) $at->getTimestamp(),
            'access_token' => $targetToken,
        ];

        switch ($candidate['media']) {
            case 'photo':
                $url = $this->largestImageUrl($candidate, $sourceToken);
                $response = $this->postGraph($pageId.'/photos', $common + [
                    'url' => $url,
                    'message' => $caption,
                ], 120);
                $json = $this->ensureOk($response);

                return ['post_id' => $json['post_id'] ?? null, 'object_id' => $json['id'] ?? null];

            case 'video':
                $fileUrl = $this->videoSourceUrl($candidate, $sourceToken);
                $response = $this->postGraph($pageId.'/videos', $common + [
                    'file_url' => $fileUrl,
                    'description' => $caption,
                ], 180);
                $json = $this->ensureOk($response);

                return ['post_id' => $json['post_id'] ?? null, 'object_id' => $json['id'] ?? null];

            default:
                $response = $this->postGraph($pageId.'/feed', $common + [
                    'message' => $caption,
                ], 60);
                $json = $this->ensureOk($response);

                return ['post_id' => $json['id'] ?? null, 'object_id' => null];
        }
    }

    /**
     * POST สร้างโพส — หมดเวลา/สายหลุด = "ไม่รู้ผล" (โยน AMBIGUOUS_SEND) ไม่ใช่ "ไม่ผ่าน"
     *
     * @return \Illuminate\Http\Client\Response
     */
    protected function postGraph(string $path, array $data, int $timeout)
    {
        try {
            return Http::timeout($timeout)->asForm()->post(self::GRAPH.$path, $data);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException(
                'หมดเวลารอ Facebook ตอบ — ไม่แน่ใจว่าโพสขึ้นคิวแล้วหรือยัง: '.SafeLog::redactSecrets($e->getMessage()),
                self::AMBIGUOUS_SEND
            );
        }
    }

    /**
     * URL รูปใหญ่สุดของโพสต้นฉบับ (รูปใน attachments ย่อเหลือ ~720px)
     */
    protected function largestImageUrl(array $candidate, string $sourceToken): string
    {
        if (! empty($candidate['object_id'])) {
            try {
                $json = $this->graphGet($candidate['object_id'], $sourceToken, ['fields' => 'images']);
                $images = $json['images'] ?? [];
                usort($images, fn ($a, $b) => ((int) ($b['width'] ?? 0)) <=> ((int) ($a['width'] ?? 0)));

                if (! empty($images[0]['source'])) {
                    return (string) $images[0]['source'];
                }
            } catch (\Throwable $e) {
                // ตกไปใช้รูปจาก attachments ข้างล่าง
            }
        }

        if (! empty($candidate['image'])) {
            return (string) $candidate['image'];
        }

        throw new RuntimeException('หา URL รูปของโพสต้นฉบับไม่เจอ');
    }

    /**
     * URL ไฟล์วิดีโอต้นฉบับ (ลิงก์ชั่วคราวจาก Facebook — Facebook ดึงไปทันทีตอนสร้างโพส)
     */
    protected function videoSourceUrl(array $candidate, string $sourceToken): string
    {
        if (empty($candidate['object_id'])) {
            throw new RuntimeException('โพสวิดีโอไม่มีรหัสวิดีโอ');
        }

        $json = $this->graphGet($candidate['object_id'], $sourceToken, ['fields' => 'source']);

        if (empty($json['source'])) {
            throw new RuntimeException('Facebook ไม่ให้ไฟล์วิดีโอต้นฉบับ');
        }

        return (string) $json['source'];
    }

    /**
     * สถานะโพสปลายทางบน Facebook: published / missing / null (ยังไม่รู้)
     */
    protected function remoteState(FortunePageRepost $repost, string $token): ?string
    {
        if ($repost->media_type === 'video' && $repost->target_object_id) {
            $response = Http::timeout(20)->get(self::GRAPH.$repost->target_object_id, [
                'fields' => 'published',
                'access_token' => $token,
            ]);

            if ((int) $response->json('error.code') === 100) {
                return 'missing';
            }

            return $response->json('published') === true ? 'published' : null;
        }

        $postId = $repost->target_post_id;

        if (! $postId) {
            return null;
        }

        $response = Http::timeout(20)->get(self::GRAPH.$postId, [
            'fields' => 'is_published',
            'access_token' => $token,
        ]);

        if ((int) $response->json('error.code') === 100) {
            return 'missing';
        }

        return $response->json('is_published') === true ? 'published' : null;
    }

    /**
     * GET Graph แล้วคืน JSON (โยน exception พร้อมข้อความที่ตัด token ออกแล้ว)
     */
    protected function graphGet(string $path, string $token, array $params = []): array
    {
        $response = Http::timeout(30)->get(self::GRAPH.ltrim($path, '/'), $params + ['access_token' => $token]);

        return $this->ensureOk($response);
    }

    /**
     * @param  \Illuminate\Http\Client\Response  $response
     */
    protected function ensureOk($response): array
    {
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || isset($json['error'])) {
            $message = is_array($json) ? ($json['error']['message'] ?? null) : null;

            throw new RuntimeException(SafeLog::redactSecrets((string) ($message ?: 'Graph HTTP '.$response->status())));
        }

        return $json;
    }
}
