<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🪪 (2026-11-03) PSID สำรองของลูกค้า Messenger — หนึ่งคนมีได้หลาย PSID (หนึ่งตัวต่อเพจ/สาขา)
 *
 * PSID หนึ่งตัวผูกได้ user เดียวเท่านั้น (unique) — ห้ามย้ายเจ้าของภายหลัง
 * (ย้าย = สายงาน/กระเป๋าย้ายตาม ต้องรวมบัญชีด้วยมือเท่านั้น)
 *
 * @property int $id
 * @property int $user_id
 * @property string $psid
 * @property int|null $fortune_page_id
 * @property string $source backfill | bot_signup | web_gateway | session_alias | oauth_hint
 */
class FortuneMessengerPsid extends Model
{
    protected $table = 'fortune_messenger_psids';

    protected $fillable = [
        'user_id',
        'psid',
        'fortune_page_id',
        'source',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'fortune_page_id' => 'integer',
    ];

    /**
     * ความสัมพันธ์กับ User เจ้าของ PSID
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ตารางพร้อมใช้หรือยัง — จำเฉพาะ "พร้อมแล้ว" (ช่วง deploy ก่อน migrate ต้องไม่พัง)
     */
    public static function tableReady(): bool
    {
        static $ready = false;

        if ($ready) {
            return true;
        }

        try {
            return $ready = Schema::hasTable('fortune_messenger_psids');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * หา user_id เจ้าของ PSID (null = ไม่รู้จัก)
     */
    public static function ownerIdOf(string $psid): ?int
    {
        if ($psid === '' || ! static::tableReady()) {
            return null;
        }

        $id = static::query()->where('psid', $psid)->value('user_id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * ผูก PSID เข้ากับ user (idempotent)
     *
     * @return bool true = PSID นี้เป็นของ $userId แล้ว (ผูกใหม่หรือผูกไว้ก่อน)
     *              false = PSID ถูกคนอื่นถือไว้ / PSID ไม่ถูกรูปแบบ / ตารางยังไม่มี
     *
     * @example
     * FortuneMessengerPsid::bind($user->id, '24680135792468013', 5, 'session_alias');
     */
    public static function bind(int $userId, string $psid, ?int $pageId, string $source): bool
    {
        // PSID ต้องเป็นตัวเลขล้วน — กัน id แปลกปลอม (LINE U…, tg_…) หลุดเข้าตาราง FB
        if ($psid === '' || ! ctype_digit($psid) || ! static::tableReady()) {
            return false;
        }

        $existing = static::query()->where('psid', $psid)->first();
        if ($existing) {
            if ((int) $existing->user_id !== $userId) {
                return false;
            }
            // รู้เพจทีหลัง (แถว backfill ไม่มีเพจ) → เติมให้ ไม่ทับค่าที่มีแล้ว
            if ($existing->fortune_page_id === null && $pageId !== null) {
                $existing->update(['fortune_page_id' => $pageId]);
            }

            return true;
        }

        try {
            static::query()->create([
                'user_id' => $userId,
                'psid' => $psid,
                'fortune_page_id' => $pageId,
                'source' => mb_substr($source, 0, 32),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            // แข่งกันผูกพร้อมกัน — ดูว่าผู้ชนะคือ user เดียวกันหรือไม่
            return static::ownerIdOf($psid) === $userId;
        } catch (\Throwable $e) {
            Log::warning('FortuneMessengerPsid: ผูก PSID ไม่สำเร็จ', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
