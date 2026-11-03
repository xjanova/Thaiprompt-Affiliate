<?php

namespace App\Support;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * 🚦 (2026-11-03 FB web-only) ความจุของเส้น /api/v1/juntra/server/* — named rate limiters
 *
 * ทำไมต้องเปลี่ยน:
 *   ทุกคำขอของกลุ่มนี้มาจาก IP เดียว (เซิร์ฟเวอร์เว็บจันทรา) เดิมใช้ throttle:N,1,prefix = ถังเดียวทั้งเว็บ
 *   แชท 300/นาที "รวมทุกลูกค้า" + กลุ่มนอก 600/นาที — พอย้ายลูกค้า FB ทั้งหมดไปคุยบนเว็บ
 *   ลูกค้าคนเดียวที่กดรัวกินถังของทุกคน และ peak ข้อความ FB เดิมชนเพดานรวมทันที (ได้ 429 = ห้องเงียบ)
 *
 * ที่ทำ:
 *   - jwchat  = 2 ชั้น: ต่อลูกค้า (key ด้วย user_ref) + เพดานรวมของแชททั้งเว็บ
 *               ชั้นต่อลูกค้าอยู่ก่อน → คนที่ชนเพดานตัวเองแล้วไม่กินถังรวมของคนอื่นต่อ
 *   - jwsrv   = ด่านรวมทั้งกลุ่ม (กันรั่ว) — ไม่มีทางต่ำกว่าผลรวมของถังย่อย (ไม่งั้นด่านนอกตัดแชทก่อนถึงเพดานแชท)
 *   - jwpay / jwread / jwaff = เพดานรวมเท่าเดิม แค่ย้ายตัวเลขมาไว้ใน config
 *   ทุกตัวปรับได้ที่ config/ratelimit.php → juntra_server (env RATE_LIMIT_JUNTRA_*) ไม่ต้องแก้ route
 *
 * ⚠️ preflight (fortune:fb-web-only-preflight) อ่านเพดานรวมของ chat/send จาก limiter ชุดนี้
 *    (เอาค่าสูงสุดในชุดของ named limiter = เพดานรวม) — ถ้าเพิ่มชั้นที่ใหญ่กว่าเพดานรวม ต้องแก้ preflight ด้วย
 */
class JuntraServerRateLimits
{
    /** ชื่อ limiter ที่ route ใช้ — ห้ามซ้ำกัน (แต่ละชื่อ = ถังแยก) */
    public const SERVER = 'jwsrv';

    public const CHAT = 'jwchat';

    public const PAY = 'jwpay';

    public const READ = 'jwread';

    public const AFFILIATE = 'jwaff';

    /** เสียงพากย์ผู้พยากรณ์ — ส่วนใหญ่ได้จาก cache คลิป แต่คลิปใหม่ต้องเรียก TTS จึงแยกถัง */
    public const VOICE = 'jwvoice';

    /** รูปแบบ user_ref เดียวกับที่ ChatController validate — นอกเหนือจากนี้ไปถังเดียวกัน (คำขอจะโดน 422 อยู่แล้ว) */
    private const USER_REF_PATTERN = '/^[A-Za-z0-9_-]{1,64}$/';

    /** log เตือนเพดานรวมเต็มได้ไม่เกิน 1 ครั้งต่อช่วงนี้ (วินาที) — กัน log ท่วมตอนคนแน่น */
    private const SATURATION_LOG_COOLDOWN = 60;

    /**
     * ลงทะเบียน limiter ทั้งชุด — เรียกจาก AppServiceProvider::boot()
     */
    public static function register(): void
    {
        RateLimiter::for(self::SERVER, fn (Request $request) => Limit::perMinute(self::serverTotal())
            ->by('all')
            ->response(self::responder('server')));

        RateLimiter::for(self::CHAT, fn (Request $request) => [
            // ชั้นต่อลูกค้า — ต้องอยู่ก่อนเพดานรวม (ThrottleRequests เช็ค+นับทีละชั้นตามลำดับ)
            Limit::perMinute(self::chatPerUser())
                ->by('u:'.self::userRefKey($request))
                ->response(self::responder('customer')),
            Limit::perMinute(self::chatTotal())
                ->by('all')
                ->response(self::responder('chat')),
        ]);

        RateLimiter::for(self::PAY, fn (Request $request) => Limit::perMinute(self::setting('pay', 120))
            ->by('all')
            ->response(self::responder('pay')));

        RateLimiter::for(self::READ, fn (Request $request) => Limit::perMinute(self::setting('read', 60))
            ->by('all')
            ->response(self::responder('read')));

        RateLimiter::for(self::AFFILIATE, fn (Request $request) => Limit::perMinute(self::setting('affiliate', 300))
            ->by('all')
            ->response(self::responder('affiliate')));

        RateLimiter::for(self::VOICE, fn (Request $request) => Limit::perMinute(self::setting('voice', 120))
            ->by('all')
            ->response(self::responder('voice')));
    }

    /** เพดานแชทต่อลูกค้า 1 คน (user_ref) ต่อนาที */
    public static function chatPerUser(): int
    {
        return self::setting('chat_per_user', 60);
    }

    /** เพดานแชทรวมทั้งเว็บต่อนาที — ค่าที่ preflight เทียบกับ peak ข้อความ FB × 1.5 */
    public static function chatTotal(): int
    {
        return self::setting('chat_total', 1200);
    }

    /**
     * ด่านรวมทั้งกลุ่มต่อนาที — ไม่ต่ำกว่าผลรวมของถังย่อยเสมอ
     * (ตั้ง env แชทสูงขึ้นแล้วลืมขยายด่านนอก = แชทโดนตัดที่ด่านนอกแทน — กันไว้ที่นี่)
     */
    public static function serverTotal(): int
    {
        $inner = self::chatTotal() + self::setting('pay', 120) + self::setting('read', 60) + self::setting('affiliate', 300)
            + self::setting('voice', 120);

        return max(self::setting('total', 2400), $inner);
    }

    /**
     * key ของถังต่อลูกค้า — user_ref ที่รูปแบบถูกเท่านั้น (ห้ามเอา array/ข้อความยาวมาเป็น key)
     */
    public static function userRefKey(Request $request): string
    {
        $ref = $request->input('user_ref');

        if (is_int($ref)) {
            $ref = (string) $ref;
        }

        if (is_string($ref) && preg_match(self::USER_REF_PATTERN, $ref) === 1) {
            return $ref;
        }

        return '-invalid-';
    }

    /** ค่าจาก config/ratelimit.php → juntra_server — ค่าเพี้ยน (≤0 / ไม่ใช่ตัวเลข) ใช้ค่าเริ่มต้น */
    private static function setting(string $key, int $default): int
    {
        $value = config('ratelimit.juntra_server.'.$key);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : $default;
    }

    /**
     * 429 เป็น JSON ภาษาไทย + reason_code ให้เว็บแยกได้ว่า "ลูกค้าคนนี้ส่งถี่" หรือ "ทั้งระบบเต็ม"
     *   juntraweb ถือ 429 ทุกตัวเป็น "ลองใหม่ได้" อยู่แล้ว (JuntraServerClient) — Retry-After มากับ header
     */
    private static function responder(string $scope): \Closure
    {
        return function (Request $request, array $headers) use ($scope): JsonResponse {
            if ($scope !== 'customer') {
                // เพดานรวมเต็ม = ความจุไม่พอ → ops ต้องเห็น (เฝ้าอัตรา 429 หลังเปิด web-only)
                if (Cache::add('juntra:server-throttle-saturated:'.$scope, true, self::SATURATION_LOG_COOLDOWN)) {
                    Log::warning('Juntra server: เพดานรวมเต็ม', [
                        'scope' => $scope,
                        'path' => $request->path(),
                    ]);
                }
            }

            $message = match ($scope) {
                'customer' => 'ส่งข้อความถี่เกินไปค่ะ · รอสักครู่แล้วค่อยส่งใหม่นะคะ',
                'chat' => 'ตอนนี้มีคนคุยกับแม่หมอเยอะมากค่ะ · รอสักครู่แล้วลองใหม่นะคะ',
                default => 'มีการเรียกใช้งานถี่เกินไป กรุณารอสักครู่แล้วลองใหม่',
            };

            return response()->json([
                'success' => false,
                'code' => 'TOO_MANY_REQUESTS',
                'reason_code' => $scope === 'customer' ? 'rate_limited' : 'capacity',
                'scope' => $scope,
                'message' => $message,
            ], 429, $headers);
        };
    }
}
