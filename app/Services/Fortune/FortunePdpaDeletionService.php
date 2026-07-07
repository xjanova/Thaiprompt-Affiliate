<?php

namespace App\Services\Fortune;

use App\Models\MlmMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 🗑️ FortunePdpaDeletionService — เครื่องมือลบข้อมูลลูกค้าตาม PDPA (แม่หมอจันทรา)
 *
 * ให้ platform + platform_user_id (LINE userId / FB PSID) มา แล้วลบ "ข้อมูลดูดวงทั้งหมด"
 * ของลูกค้าคนนั้นออกจากระบบ + ยุติสิทธิ์สายงาน/คอมมิชชั่นที่จะพึงได้ นับจากวันแจ้งลบ
 *
 * ── ขอบเขต (owner decision 2026-07-07) ────────────────────────────────────
 *  1. ลบเฉพาะ "ข้อมูลดูดวง" — ไม่แตะระบบอื่น (อีคอมเมิร์ซ/โรงแรม/TPIX/wallet ข้ามระบบ)
 *  2. สมาชิก MLM ที่มีสายงาน → "ปกปิด PII + ยุติสิทธิ์" (ไม่ลบ node/ledger ทิ้ง)
 *     เพราะลบ node จะทำผังสายงาน + คอมมิชชั่นของสมาชิก "คนอื่น" พัง
 *     (บทเรียน audit 2026-06-12: binary placement คืน null เมื่อ tree เพี้ยน)
 *
 * ── หลักการทำงาน ──────────────────────────────────────────────────────────
 *  - Hard-delete: ตาราง fortune_* ที่เป็นข้อมูลส่วนตัวลูกค้า (คำทำนาย/บทสนทนา/persona ฯลฯ)
 *  - Terminate (ไม่ลบ): fortune_commissions ที่ pending → reject ; mlm_members → inactive ;
 *    users (บัญชีที่เกิดจากดูดวงล้วน) → ปกปิด PII + ตัดลิงก์ LINE/FB
 *  - ยกเว้น (legitimate interest): fortune_user_bans คงไว้ (กัน troll ใช้ PDPA รีเซ็ตแบน)
 *  - ทุก table op guard ด้วย hasTable/try-catch — table หายก็ไม่ทำทั้งงานล่ม
 *
 * เรียกจาก: FortunePdpaDeletionTrait (ในแชท) + ProcessFortuneDataDeletionJob (FB callback)
 */
class FortunePdpaDeletionService
{
    /**
     * ลบข้อมูลดูดวงทั้งหมดของลูกค้า 1 คน (idempotent — เรียกซ้ำได้ปลอดภัย)
     *
     * @param  string  $platform  'line' | 'facebook'
     * @param  string  $platformUserId  LINE userId / FB PSID
     * @return array{ok:bool,platform:string,platform_user_id:string,counts:array<string,int>,error?:string}
     */
    public function deleteForCustomer(string $platform, string $platformUserId): array
    {
        $platform = $platform === 'line' ? 'line' : 'facebook';
        $platformUserId = trim($platformUserId);

        $counts = [];

        if ($platformUserId === '') {
            return ['ok' => false, 'platform' => $platform, 'platform_user_id' => '', 'counts' => [], 'error' => 'empty_user_id'];
        }

        try {
            // ── 1) รวบรวมตัวตนทุกคีย์ที่โยงถึงลูกค้าคนนี้ ─────────────────────────
            // reading ids + facebook ids (บาง table ใช้ facebook_user_id แม้เป็น LINE) + user ids ที่ผูกบัญชี
            [$readingIds, $facebookIds, $userIds, $filePaths] = $this->resolveIdentity($platform, $platformUserId);

            // ── 2) งานฐานข้อมูลทั้งหมดใน transaction เดียว (atomic) ───────────────
            DB::transaction(function () use ($platform, $platformUserId, $readingIds, $facebookIds, $userIds, &$counts) {
                // 2a. ยุติสิทธิ์คอมมิชชั่นดูดวงที่ "ยังไม่จ่าย" (pending) — เก็บ ledger ที่จ่ายแล้วไว้
                $counts['commissions_terminated'] = $this->terminatePendingCommissions($userIds);

                // 2b. ปกปิด PII + ยุติสิทธิ์บัญชี/สมาชิก MLM (เฉพาะบัญชีที่เกิดจากดูดวงล้วน)
                [$counts['users_anonymized'], $counts['mlm_suspended']] = $this->anonymizeLinkedAccounts($userIds, $platform, $platformUserId);

                // 2c. ลบข้อมูลดูดวงส่วนตัว (hard delete) ทุก table ที่คีย์ด้วย platform id
                $this->deleteFortuneData($platform, $platformUserId, $readingIds, $facebookIds, $userIds, $counts);
            });

            // ── 3) side-effects นอก transaction (ย้อนกลับไม่ได้) — best-effort ──────
            // 3a. ลบ log บทสนทนา realtime ใน Redis
            try {
                $counts['redis_chatlog_keys'] = app(FortuneChatLogService::class)->purgeCustomer($platform, $platformUserId);
            } catch (\Throwable $e) {
                Log::warning('PDPA: purge redis chatlog failed (non-blocking)', ['error' => $e->getMessage()]);
            }

            // 3b. ลบไฟล์ที่เก็บไว้ (สลิป/เสียง/รูป) — best-effort
            $counts['files_deleted'] = $this->deleteStoredFiles($filePaths);

            Log::info('🗑️ PDPA: ลบข้อมูลลูกค้าสำเร็จ', [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
                'counts' => $counts,
            ]);

            return ['ok' => true, 'platform' => $platform, 'platform_user_id' => $platformUserId, 'counts' => $counts];
        } catch (\Throwable $e) {
            Log::error('🗑️ PDPA: ลบข้อมูลลูกค้าล้มเหลว', [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 800),
            ]);

            return ['ok' => false, 'platform' => $platform, 'platform_user_id' => $platformUserId, 'counts' => $counts, 'error' => $e->getMessage()];
        }
    }

    /**
     * รวบรวม reading ids / facebook ids / user ids / file paths ที่โยงถึงลูกค้า
     *
     * @return array{0:array<int>,1:array<string>,2:array<int>,3:array<string>}
     */
    protected function resolveIdentity(string $platform, string $platformUserId): array
    {
        $readingIds = [];
        $facebookIds = [];
        $userIds = [];
        $filePaths = [];

        // ถ้าเป็น FB → platformUserId เองคือ PSID
        if ($platform === 'facebook') {
            $facebookIds[] = $platformUserId;
        }

        if (Schema::hasTable('fortune_readings')) {
            // ดึงทุก reading ที่เป็นของลูกค้า (ทั้งคอลัมน์ใหม่ platform_user_id + เก่า facebook_user_id)
            $fileCols = collect(['slip_image_path', 'voice_audio_path', 'user_image_url', 'reading_image_url', 'celtic_summary_image_path'])
                ->filter(fn ($c) => Schema::hasColumn('fortune_readings', $c))
                ->values()
                ->all();

            $select = array_merge(['id', 'facebook_user_id', 'user_id'], $fileCols);

            DB::table('fortune_readings')
                ->where(function ($q) use ($platformUserId) {
                    $q->where('platform_user_id', $platformUserId)
                        ->orWhere('facebook_user_id', $platformUserId);
                })
                ->select($select)
                ->orderBy('id')
                ->chunk(500, function ($rows) use (&$readingIds, &$facebookIds, &$userIds, &$filePaths, $fileCols) {
                    foreach ($rows as $r) {
                        $readingIds[] = (int) $r->id;
                        if (! empty($r->facebook_user_id)) {
                            $facebookIds[] = (string) $r->facebook_user_id;
                        }
                        if (! empty($r->user_id)) {
                            $userIds[] = (int) $r->user_id;
                        }
                        foreach ($fileCols as $c) {
                            if (! empty($r->{$c})) {
                                $filePaths[] = (string) $r->{$c};
                            }
                        }
                    }
                });
        }

        // บัญชี users ที่ผูกด้วย LINE/FB id โดยตรง (อาจไม่มี reading ผูก user_id)
        if (Schema::hasTable('users')) {
            $userQuery = User::query();
            $hasAny = false;
            if (Schema::hasColumn('users', 'line_user_id')) {
                $userQuery->orWhere('line_user_id', $platformUserId);
                $hasAny = true;
            }
            if (Schema::hasColumn('users', 'facebook_user_id') && ! empty($facebookIds)) {
                $userQuery->orWhereIn('facebook_user_id', array_values(array_unique($facebookIds)));
                $hasAny = true;
            }
            if ($hasAny) {
                foreach ($userQuery->pluck('id') as $id) {
                    $userIds[] = (int) $id;
                }
            }
        }

        return [
            array_values(array_unique($readingIds)),
            array_values(array_unique(array_filter($facebookIds))),
            array_values(array_unique($userIds)),
            array_values(array_unique($filePaths)),
        ];
    }

    /**
     * ยุติสิทธิ์คอมมิชชั่นดูดวงที่ยังไม่จ่าย (pending) → reject
     *
     * เก็บ record ที่ approved/paid ไว้ (ledger + เงินที่เข้ากระเป๋าแล้ว ไม่แตะ — ข้ามระบบ)
     */
    protected function terminatePendingCommissions(array $userIds): int
    {
        if (empty($userIds) || ! Schema::hasTable('fortune_commissions')) {
            return 0;
        }

        try {
            $update = ['status' => 'rejected', 'updated_at' => now()];
            if (Schema::hasColumn('fortune_commissions', 'rejected_at')) {
                $update['rejected_at'] = now();
            }
            if (Schema::hasColumn('fortune_commissions', 'notes')) {
                $update['notes'] = 'PDPA: ยุติสิทธิ์ตามคำขอลบข้อมูล';
            }

            return DB::table('fortune_commissions')
                ->whereIn('user_id', $userIds)
                ->where('status', 'pending')
                ->update($update);
        } catch (\Throwable $e) {
            Log::warning('PDPA: terminatePendingCommissions failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * ปกปิด PII + ยุติสิทธิ์บัญชีที่ผูก + สมาชิก MLM
     *
     * เฉพาะ "บัญชีที่เกิดจากดูดวงล้วน" (email สังเคราะห์ @thaiprompt.local) เท่านั้นจึงปกปิด PII เต็ม
     * บัญชีจริงที่ใช้หลายระบบ (email จริง) → ไม่แตะ users/mlm (เคารพขอบเขต "ไม่กระทบระบบอื่น")
     *
     * @return array{0:int,1:int} [users_anonymized, mlm_suspended]
     */
    protected function anonymizeLinkedAccounts(array $userIds, string $platform, string $platformUserId): array
    {
        if (empty($userIds) || ! Schema::hasTable('users')) {
            return [0, 0];
        }

        $anonymized = 0;
        $suspended = 0;
        $ref = 'PDPA-'.now()->format('ymd').'-'.Str::upper(Str::random(6));

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            // บัญชีที่เกิดจากดูดวงล้วน → email = line_xxx@thaiprompt.local / fb_xxx@thaiprompt.local
            $isFortuneOrigin = str_ends_with(mb_strtolower((string) $user->email), '@thaiprompt.local');

            if (! $isFortuneOrigin) {
                // บัญชีจริงหลายระบบ → ไม่ปกปิด users/mlm (สิทธิ์ดูดวง pending ถูก reject ไปแล้วที่ 2a)
                Log::info('PDPA: ข้ามปกปิดบัญชีจริง (email ไม่ใช่ @thaiprompt.local) — เคารพขอบเขตไม่กระทบระบบอื่น', [
                    'user_id' => $user->id,
                ]);

                continue;
            }

            // ── ปกปิด PII (เฉพาะคอลัมน์ที่มีจริง) ──
            $scrub = [
                'name' => 'ผู้ใช้ที่ลบข้อมูลแล้ว',
                'email' => 'deleted_'.$user->id.'_'.Str::lower(Str::random(8)).'@deleted.local',
                'password' => Hash::make(Str::random(40)),
                // ตัดลิงก์ LINE/FB → ค้นเจอไม่ได้อีก + ทักใหม่ = ตัวตนใหม่สะอาด
                'line_user_id' => null, 'line_display_name' => null, 'line_picture_url' => null,
                'line_access_token' => null, 'line_verified' => false,
                'facebook_user_id' => null, 'facebook_email' => null, 'facebook_name' => null,
                'facebook_picture_url' => null, 'facebook_verified' => false,
                // โปรไฟล์/ข้อมูลอ่อนไหว
                'profile_picture' => null, 'avatar_url' => null, 'date_of_birth' => null,
                'gender' => null, 'phone' => null, 'phone_verified' => false, 'bio' => null,
                'bank_name' => null, 'bank_account' => null, 'bank_account_name' => null,
                'address' => null, 'city' => null, 'state' => null, 'postal_code' => null,
                'id_card_number' => null, 'thai_first_name' => null, 'thai_last_name' => null,
                'english_first_name' => null, 'english_last_name' => null, 'id_card_address' => null,
                'pdpa_deleted_at' => now(), 'pdpa_deletion_ref' => $ref,
            ];

            $update = [];
            foreach ($scrub as $col => $val) {
                if (Schema::hasColumn('users', $col)) {
                    $update[$col] = $val;
                }
            }
            $update['updated_at'] = now();

            try {
                DB::table('users')->where('id', $user->id)->update($update);
                $anonymized++;
            } catch (\Throwable $e) {
                Log::warning('PDPA: anonymize user failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            // ── ยุติสิทธิ์สมาชิก MLM (คงโครงสร้าง tree — แค่ set inactive) ──
            if (Schema::hasTable('mlm_members')) {
                try {
                    $mlmUpdate = ['status' => 'inactive', 'updated_at' => now()];
                    $suspended += MlmMember::where('user_id', $user->id)
                        ->where('status', '!=', 'inactive')
                        ->update($mlmUpdate);
                } catch (\Throwable $e) {
                    Log::warning('PDPA: suspend mlm_member failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }
            }
        }

        return [$anonymized, $suspended];
    }

    /**
     * ลบข้อมูลดูดวงส่วนตัว (hard delete) ทุก table ที่คีย์ด้วย platform id / reading id
     */
    protected function deleteFortuneData(string $platform, string $platformUserId, array $readingIds, array $facebookIds, array $userIds, array &$counts): void
    {
        // ── ตารางที่คีย์ด้วย platform + platform_user_id ──
        $counts['personas'] = $this->deleteWhere('fortune_customer_personas', fn ($q) => $q->where('platform', $platform)->where('platform_user_id', $platformUserId));
        $counts['sensitive_events'] = $this->deleteWhere('fortune_sensitive_events', fn ($q) => $q->where('platform', $platform)->where('platform_user_id', $platformUserId));
        $counts['contact_signals'] = $this->deleteWhere('fortune_contact_signals', fn ($q) => $q->where('platform', $platform)->where('platform_user_id', $platformUserId));
        $counts['user_languages'] = $this->deleteWhere('fortune_user_languages', fn ($q) => $q->where('platform', $platform)->where('platform_user_id', $platformUserId));
        $counts['monthly_free_claims'] = $this->deleteWhere('fortune_monthly_free_claims', fn ($q) => $q->where('platform', $platform)->where('psid', $platformUserId));

        // ── credits: คีย์ด้วย facebook_user_id (ใช้เก็บ platform id ทั้ง FB/LINE) + platform ──
        $creditIds = array_values(array_unique(array_merge($facebookIds, [$platformUserId])));
        $counts['user_credits'] = $this->deleteWhere('fortune_user_credits', fn ($q) => $q->where('platform', $platform)->whereIn('facebook_user_id', $creditIds));

        // ── ตารางที่คีย์ด้วย facebook_user_id (FB เท่านั้น) ──
        if (! empty($facebookIds)) {
            $counts['post_reactions'] = $this->deleteWhere('fortune_post_reactions', fn ($q) => $q->whereIn('facebook_user_id', $facebookIds));
            $counts['comment_engagements'] = $this->deleteWhere('fortune_comment_engagements', fn ($q) => $q->whereIn('facebook_user_id', $facebookIds));
        }

        // ── admin QA RAG: source_user_id = platform id ──
        $qaIds = array_values(array_unique(array_merge($facebookIds, [$platformUserId])));
        $counts['admin_qa'] = $this->deleteWhere('fortune_admin_qa', fn ($q) => $q->whereIn('source_user_id', $qaIds));

        // ── referrals: เฉพาะแถวที่ลูกค้าเป็น "ผู้ถูกชวน" (มี IP/UA ของลูกค้า) — เก็บแถวที่เป็นผู้ชวน ──
        //   สร้างเงื่อนไขเฉพาะคอลัมน์ที่มีจริง (ต้องมีอย่างน้อย 1 เงื่อนไข ไม่งั้นข้าม — กันลบทั้งตาราง)
        if (Schema::hasTable('fortune_referrals')) {
            $refByLine = Schema::hasColumn('fortune_referrals', 'referred_line_user_id');
            $refByUser = ! empty($userIds) && Schema::hasColumn('fortune_referrals', 'referred_user_id');
            if ($refByLine || $refByUser) {
                $counts['referrals'] = $this->deleteWhere('fortune_referrals', function ($q) use ($platformUserId, $userIds, $refByLine, $refByUser) {
                    // บังคับให้มีเงื่อนไขเสมอ (where 1=0 เป็นฐาน) แล้วค่อย orWhere ตามคอลัมน์ที่มี
                    $q->whereRaw('1 = 0');
                    if ($refByLine) {
                        $q->orWhere('referred_line_user_id', $platformUserId);
                    }
                    if ($refByUser) {
                        $q->orWhereIn('referred_user_id', $userIds);
                    }
                });
            }
        }

        // ── line_bot_conversations + messages (คีย์ด้วย line_user_id = platform id) ──
        $this->deleteLineBotConversations($platform, $platformUserId, $counts);

        // ── cross-cutting ที่คีย์ด้วย reading id ──
        if (! empty($readingIds)) {
            $counts['slip_verifications'] = $this->deleteWhere('slip_verifications', fn ($q) => $q->whereIn('fortune_reading_id', $readingIds));
            $counts['slip_verification_logs'] = $this->deleteWhere('slip_verification_logs', fn ($q) => $q->whereIn('fortune_reading_id', $readingIds));
            $counts['ai_usage_logs'] = $this->deleteWhere('ai_api_key_usage_logs', fn ($q) => $q->whereIn('reading_id', $readingIds));
        }
        // ai usage logs อาจคีย์ด้วย fb_user_id ด้วย (เผื่อไม่มี reading_id)
        if (! empty($facebookIds) && Schema::hasTable('ai_api_key_usage_logs') && Schema::hasColumn('ai_api_key_usage_logs', 'fb_user_id')) {
            $counts['ai_usage_logs'] = ($counts['ai_usage_logs'] ?? 0)
                + $this->deleteWhere('ai_api_key_usage_logs', fn ($q) => $q->whereIn('fb_user_id', $facebookIds));
        }

        // ── สุดท้าย: ลบ fortune_readings จริง (DB cascade → fortune_celtic_questions + fortune_takeover_logs) ──
        // ใช้ DB::table()->delete() = real DELETE (ข้าม SoftDeletes) → trigger FK cascade
        //
        // ⚠️ STRICT (compliance gate): ตรงนี้ "ไม่ catch" ต่างจาก aux tables ข้างบน —
        //   ถ้าลบ readings (PII หลัก: วันเกิด/คำถาม/คำทำนาย) ล้มเหลว ต้องให้ exception
        //   หลุดออกไป → DB::transaction rollback ทั้งก้อน → deleteForCustomer คืน ok:false
        //   กันเคส "บอกลูกค้าว่าลบแล้ว แต่ PII ยังอยู่ในระบบ" (ผิดหลัก PDPA)
        if (Schema::hasTable('fortune_readings')) {
            $counts['readings'] = DB::table('fortune_readings')
                ->where(function ($q) use ($platformUserId) {
                    $q->where('platform_user_id', $platformUserId)->orWhere('facebook_user_id', $platformUserId);
                })
                ->delete();
        }
    }

    /**
     * ลบ line_bot_conversations + line_bot_messages ที่โยงกัน
     */
    protected function deleteLineBotConversations(string $platform, string $platformUserId, array &$counts): void
    {
        if (! Schema::hasTable('line_bot_conversations')) {
            return;
        }

        try {
            $convIds = DB::table('line_bot_conversations')
                ->where('line_user_id', $platformUserId)
                ->where('platform', $platform)
                ->pluck('id')
                ->all();

            if (! empty($convIds) && Schema::hasTable('line_bot_messages') && Schema::hasColumn('line_bot_messages', 'conversation_id')) {
                $counts['line_bot_messages'] = DB::table('line_bot_messages')->whereIn('conversation_id', $convIds)->delete();
            }

            $counts['line_bot_conversations'] = DB::table('line_bot_conversations')
                ->where('line_user_id', $platformUserId)
                ->where('platform', $platform)
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('PDPA: delete line_bot_conversations failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ลบแถวจาก table หนึ่ง แบบ guard (table หาย/พังก็ไม่ทำงานล่ม) — คืนจำนวนที่ลบ
     *
     * @param  callable(\Illuminate\Database\Query\Builder):void  $constrain
     */
    protected function deleteWhere(string $table, callable $constrain): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        try {
            $query = DB::table($table);
            $constrain($query);

            return $query->delete();
        } catch (\Throwable $e) {
            Log::warning('PDPA: deleteWhere failed', ['table' => $table, 'error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * ลบไฟล์ที่เก็บไว้ (สลิป/เสียง/รูป) — best-effort ทั้ง local + public disk
     *
     * ค่าที่เก็บอาจเป็น path สัมพัทธ์ (storage) หรือ URL เต็ม (R2/S3/GCS) — เราลบเฉพาะที่เป็น
     * path บน disk ที่เข้าถึงได้ ; URL ปลายทางไกลข้ามไป (row DB ที่มี PII ถูกลบแล้วเป็นหลัก)
     */
    protected function deleteStoredFiles(array $filePaths): int
    {
        $deleted = 0;
        foreach ($filePaths as $raw) {
            $path = trim((string) $raw);
            if ($path === '') {
                continue;
            }

            // URL เต็ม → ตัด host ออกเหลือ path หลัง /storage/ ถ้ามี ไม่งั้นข้าม
            if (preg_match('#^https?://#i', $path)) {
                if (preg_match('#/storage/(.+)$#', $path, $m)) {
                    $path = $m[1];
                } else {
                    continue; // remote disk — ข้าม (best-effort)
                }
            }

            $path = ltrim($path, '/');
            // ตัด prefix ซ้ำ เผื่อค่าเก็บเป็น 'public/...' หรือ 'storage/...'
            $path = preg_replace('#^(public|storage)/#', '', $path);

            foreach (['public', 'local'] as $disk) {
                try {
                    if (Storage::disk($disk)->exists($path)) {
                        Storage::disk($disk)->delete($path);
                        $deleted++;
                        break;
                    }
                } catch (\Throwable $e) {
                    // best-effort — ข้าม
                }
            }
        }

        return $deleted;
    }
}
