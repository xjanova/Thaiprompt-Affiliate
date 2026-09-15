<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuthenticateJuntraServer — alias 'juntra.server'
 *
 * 🔐 (2026-09-15) ประตูของ /api/v1/juntra/server/* — ให้ "เซิร์ฟเวอร์ของเว็บ จันทรา.online"
 *    เรียกได้เท่านั้น ด้วย token แบบ client_credentials ของ Passport client ตัวเดียวกับ SSO
 *
 * ทำไมไม่ใช้ 'juntra.user': เส้นกลุ่มนี้ไม่มีผู้ใช้ปลายทาง (juntraweb ยิงเองตอนตรวจสลิป/จองยอด)
 *   และทำเรื่องเงินข้ามระบบ (เคลมสลิป, จองยอดทศนิยม) → ต้องรู้ว่า "ใครยิง" แน่ๆ ไม่ใช่แค่มี token
 *
 * กติกา (CONTRACT §A):
 *   - ตรวจ Bearer ด้วย League ResourceServer แบบเดียวกับ Passport CheckCredentials
 *   - ต้องเป็น token ของ client เอง (ไม่มี user) — token ของผู้ใช้ = 403
 *   - client ต้องมีอยู่จริงและไม่ถูก revoke
 *   - client ต้องอยู่ใน config('services.juntra.server_client_ids') หรือถ้ารายการว่าง
 *     ต้องเป็น client ที่ redirect ชี้ไปโดเมน จันทรา.online (ตรวจ "host" ไม่ใช่แค่ contains
 *     กัน redirect แบบ https://evil.example/?x=xn--82c4af5bzdj.online)
 *
 * ⚠️ ไม่มี token เลย → ตอบ 401 ทันทีโดยไม่แตะ ResourceServer
 *    (สภาพแวดล้อมที่ยังไม่มีคีย์ Passport จะโยน exception ตอนสร้าง ResourceServer — ห้ามกลายเป็น 500)
 */
class AuthenticateJuntraServer
{
    /** host ของเว็บจันทรา (punycode + ตัวอักษรไทย) */
    public const JUNTRA_HOSTS = ['xn--82c4af5bzdj.online', 'จันทรา.online'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return $this->deny(401, 'unauthenticated', 'Unauthenticated.');
        }

        try {
            $psr = (new PsrHttpFactory(
                new Psr17Factory,
                new Psr17Factory,
                new Psr17Factory,
                new Psr17Factory
            ))->createRequest($request);

            $psr = app(ResourceServer::class)->validateAuthenticatedRequest($psr);
        } catch (OAuthServerException $e) {
            return $this->deny(401, 'unauthenticated', 'Unauthenticated.');
        } catch (\Throwable $e) {
            // คีย์ Passport หาย / config พัง — ปิดประตูไว้ก่อน (fail closed) และบอกแอดมินใน log
            Log::error('AuthenticateJuntraServer: ตรวจ token ไม่ได้ (ระบบ Passport ไม่พร้อม)', [
                'error' => $e->getMessage(),
            ]);

            return $this->deny(401, 'unauthenticated', 'Unauthenticated.');
        }

        // token ต้องยังอยู่ในฐานข้อมูลและไม่ถูกเพิกถอน
        //   (ResourceServer เช็ค revoke ให้แล้ว — ชั้นนี้ไว้ดู user_id ของ token ด้วย
        //    เพราะ league/oauth2-server รุ่นใหม่ใส่ client id ไว้ใน sub ของ token แบบ client_credentials)
        $token = null;
        try {
            $token = app(TokenRepository::class)->find($psr->getAttribute('oauth_access_token_id'));
        } catch (\Throwable $e) {
            $token = null;
        }

        if ($token === null || (bool) ($token->revoked ?? false)) {
            return $this->deny(401, 'unauthenticated', 'Unauthenticated.');
        }

        $psrUserId = $psr->getAttribute('oauth_user_id');
        if (($psrUserId !== null && $psrUserId !== '') || $token->user_id !== null) {
            Log::warning('AuthenticateJuntraServer: ปฏิเสธ token ของผู้ใช้ (เส้นนี้รับเฉพาะ client_credentials)', [
                'client_id' => $psr->getAttribute('oauth_client_id'),
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return $this->deny(403, 'forbidden_client', 'User tokens are not accepted on this endpoint.');
        }

        $clientId = (string) ($psr->getAttribute('oauth_client_id') ?? '');
        $client = $clientId !== '' ? Passport::client()->newQuery()->find($clientId) : null;

        if ($client === null || (bool) $client->revoked || ! $this->isJuntrawebClient($client)) {
            Log::warning('AuthenticateJuntraServer: client ไม่มีสิทธิ์เรียก API ฝั่งเซิร์ฟเวอร์ของจันทรา', [
                'client_id' => $clientId,
                'found' => $client !== null,
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return $this->deny(403, 'forbidden_client', 'This client is not allowed to call this endpoint.');
        }

        $request->attributes->set('juntra_client_id', (string) $client->getKey());

        return $next($request);
    }

    /**
     * client นี้คือเซิร์ฟเวอร์ของเว็บจันทราไหม
     */
    protected function isJuntrawebClient($client): bool
    {
        $allowed = array_map('strval', (array) config('services.juntra.server_client_ids', []));

        if (! empty($allowed)) {
            return in_array((string) $client->getKey(), $allowed, true);
        }

        // ไม่ได้ตั้งรายการไว้ → ยอมรับเฉพาะ client ที่ callback ชี้ไปโดเมนจันทรา (client ของ SSO)
        //   Passport 12 เก็บ redirect เป็นสตริงคั่นด้วย comma
        foreach (explode(',', (string) ($client->redirect ?? '')) as $uri) {
            $host = mb_strtolower((string) parse_url(trim($uri), PHP_URL_HOST));
            if ($host === '') {
                continue;
            }

            foreach (self::JUNTRA_HOSTS as $juntraHost) {
                if ($host === $juntraHost || str_ends_with($host, '.'.$juntraHost)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function deny(int $status, string $reasonCode, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'reason_code' => $reasonCode,
        ], $status);
    }
}
