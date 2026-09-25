<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\WebSessionRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🔐 เปิดเว็บแบบล็อกอินจากแอป (token จาก POST /api/v1/web-session)
 *
 * แทน closure เดิมใน routes/web.php ที่มีช่องโหว่ (audit PLAY-16 / SHOP-15):
 *   - open redirect: redirect($redirectPath) ตรงๆ + ต่อ query string ที่ติดมากับลิงก์ได้ทั้งหมด
 *   - login CSRF: GET ลิงก์เดียว = ล็อกอินเป็นเจ้าของ token ทันที (ผู้โจมตีส่งลิงก์บัญชีตัวเองให้เหยื่อ)
 *   - ไม่เช็คบัญชีถูกระงับ
 *
 * ตอนนี้:
 *   GET  /mobile-web-session?token=…  → แค่ "ดู" token (ไม่เผา) แล้วแสดงหน้ายืนยันว่าจะเข้าในชื่อใคร
 *        (ตัว preview ลิงก์ของแชทที่ยิง GET ล่วงหน้าจึงเผา token ไม่ได้)
 *        · ล็อกอินอยู่เป็นคนเดียวกันแล้ว → ใช้ token แล้วพาไปปลายทางเลย
 *        · ยังไม่ล็อกอิน + IP เดียวกับเครื่องที่ออก token → หน้าเดียวกันแต่กดยืนยันให้อัตโนมัติ
 *        · มีคนอื่นล็อกอินอยู่ / IP ต่าง → ต้องกดยืนยันเอง (เห็นชื่อบัญชีชัดเจน)
 *   POST /mobile-web-session (มี CSRF) → เผา token (ใช้ครั้งเดียว) แล้วล็อกอินจริง
 */
class MobileWebSessionController extends Controller
{
    private const CACHE_PREFIX = 'web_session_token:';

    /**
     * GET /mobile-web-session (route: mobile-web-session)
     */
    public function show(Request $request): Response
    {
        $token = $this->tokenFrom($request->query('token'));
        if ($token === null) {
            return $this->failRedirect('ลิงก์ไม่ถูกต้อง');
        }

        $payload = Cache::get(self::CACHE_PREFIX.hash('sha256', $token));
        $checked = $this->validatePayload($payload);

        if (isset($checked['error'])) {
            if (is_array($payload)) {
                Cache::forget(self::CACHE_PREFIX.hash('sha256', $token));
            }

            return $this->failRedirect($checked['error']);
        }

        /** @var User $target */
        $target = $checked['user'];
        $current = Auth::user();

        // ล็อกอินอยู่เป็นคนเดียวกันแล้ว → ไม่ต้องล็อกอินใหม่ แค่ใช้ token แล้วไปต่อ
        if ($current && (int) $current->getAuthIdentifier() === (int) $target->id) {
            $consumed = $this->pullPayload($token);
            if ($consumed === null) {
                return $this->failRedirect('ลิงก์หมดอายุหรือถูกใช้ไปแล้ว กรุณาลองใหม่จากแอป');
            }

            return $this->noStore(redirect()->to(url($checked['target'])));
        }

        $autoSubmit = ! $current
            && is_string($payload['ip'] ?? null)
            && hash_equals((string) $payload['ip'], (string) $request->ip());

        $response = response()->view('auth.mobile-web-session', [
            'token' => $token,
            'targetName' => $target->name,
            'targetEmail' => WebSessionRedirect::maskEmail($target->email),
            'currentName' => $current?->name,
            'currentEmail' => $current ? WebSessionRedirect::maskEmail($current->email) : null,
            'destinationLabel' => $this->destinationLabel($checked['path']),
            'destinationPath' => $checked['path'],
            'autoSubmit' => $autoSubmit,
        ]);

        return $this->noStore($response);
    }

    /**
     * POST /mobile-web-session (route: mobile-web-session.consume) — ใช้ token + ล็อกอินจริง
     */
    public function consume(Request $request): Response
    {
        $token = $this->tokenFrom($request->input('token'));
        if ($token === null) {
            return $this->failRedirect('ลิงก์ไม่ถูกต้อง');
        }

        $payload = $this->pullPayload($token);
        $checked = $this->validatePayload($payload);

        if (isset($checked['error'])) {
            return $this->failRedirect($checked['error']);
        }

        /** @var User $target */
        $target = $checked['user'];

        // มีบัญชีอื่นล็อกอินอยู่ (ผู้ใช้กดยืนยันแล้วบนหน้ายืนยัน) → ออกจากบัญชีเดิมให้สะอาดก่อน
        $current = Auth::user();
        if ($current && (int) $current->getAuthIdentifier() !== (int) $target->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        Auth::login($target);
        $request->session()->regenerate();

        Log::info('Mobile web session login', [
            'user_id' => $target->id,
            'path' => $checked['path'],
            'same_ip' => is_string($payload['ip'] ?? null) && hash_equals((string) $payload['ip'], (string) $request->ip()),
        ]);

        return $this->noStore(redirect()->to(url($checked['target'])));
    }

    /**
     * ตรวจ payload ของ token: หมดอายุ / ผู้ใช้หาย / ถูกระงับ / ปลายทางไม่ผ่านกติกา
     *
     * @return array{user?: User, path?: string, target?: string, error?: string}
     */
    private function validatePayload(mixed $payload): array
    {
        if (! is_array($payload) || empty($payload['user_id'])) {
            return ['error' => 'ลิงก์หมดอายุหรือไม่ถูกต้อง กรุณาลองใหม่จากแอป'];
        }

        // กันกรณี cache store ไม่เคารพ TTL (เช่นย้าย driver) — เช็คเวลาหมดอายุซ้ำเอง
        if (isset($payload['expires_at']) && (int) $payload['expires_at'] < now()->getTimestamp()) {
            return ['error' => 'ลิงก์หมดอายุแล้ว กรุณาลองใหม่จากแอป'];
        }

        $user = User::find($payload['user_id']);
        if (! $user) {
            return ['error' => 'ไม่พบบัญชีผู้ใช้'];
        }

        if ($user->isSuspended()) {
            return ['error' => \App\Http\Middleware\EnsureAccountActive::SUSPENDED_MESSAGE];
        }

        // ตรวจปลายทางซ้ำอีกชั้น (token เก่าที่ออกก่อนแก้บั๊กอาจมี path อันตรายค้างใน cache)
        $path = WebSessionRedirect::sanitizePath($payload['redirect_path'] ?? WebSessionRedirect::DEFAULT_PATH);
        if ($path === null) {
            return ['error' => 'ปลายทางของลิงก์ไม่ได้รับอนุญาต'];
        }

        $query = WebSessionRedirect::sanitizeQuery($payload['query'] ?? []);

        return [
            'user' => $user,
            'path' => $path,
            'target' => WebSessionRedirect::buildTarget($path, $query),
        ];
    }

    /**
     * เอา payload ออกจาก cache แบบอะตอมมิก (ใช้ได้ครั้งเดียว แม้กดยืนยันซ้ำพร้อมกัน)
     */
    private function pullPayload(string $token): ?array
    {
        $key = self::CACHE_PREFIX.hash('sha256', $token);

        try {
            $payload = Cache::lock('lock:'.$key, 5)->block(3, fn () => Cache::pull($key));
        } catch (\Throwable $e) {
            // cache driver ไม่รองรับ lock / รอ lock ไม่ทัน → pull ตรง (ยังใช้ได้ครั้งเดียวในทางปฏิบัติ)
            $payload = Cache::pull($key);
        }

        return is_array($payload) ? $payload : null;
    }

    private function tokenFrom(mixed $raw): ?string
    {
        return is_string($raw) && preg_match('/^[A-Za-z0-9]{64}$/', $raw) ? $raw : null;
    }

    /**
     * คำอธิบายปลายทางภาษาไทยบนหน้ายืนยัน
     */
    private function destinationLabel(string $path): string
    {
        $labels = [
            '/user/wallet' => 'กระเป๋าเงินของฉัน',
            '/user' => 'พื้นที่สมาชิก',
            '/seller' => 'หลังร้านค้า',
            '/taladsod' => 'ตลาดสดไทยพร๊อมท์',
            '/shop' => 'ร้านค้า',
            '/storefront' => 'หน้าร้าน',
            '/wallet' => 'กระเป๋าเงิน',
            '/account' => 'บัญชีของฉัน',
        ];

        foreach ($labels as $prefix => $label) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $label;
            }
        }

        return 'เว็บไซต์ไทยพร๊อมท์';
    }

    private function failRedirect(string $message): Response
    {
        return $this->noStore(redirect('/login')->with('error', $message));
    }

    /**
     * ห้าม cache หน้าที่มี token + ไม่ส่ง URL (ที่มี token) ต่อไปเป็น Referer
     */
    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
