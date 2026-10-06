<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPushToken;
use App\Models\MobileAuthToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Admin Web: Mobile Pair Page
 *
 * หน้าให้ admin ที่ login web อยู่แล้ว → กด "เชื่อมต่อมือถือ"
 * เพื่อสร้าง QR code สำหรับให้ Admin Mobile App สแกน
 *
 * ใช้ session auth (admin middleware) แทน Sanctum bearer
 * — ไม่ต้องออก self-token ก่อน
 */
class MobilePairController extends Controller
{
    /**
     * อายุ pair_code (วินาที) — ตรงกับ PairingController API
     */
    private const PAIR_CODE_TTL = 300;

    /**
     * URL scheme
     */
    private const APP_SCHEME = 'thaipromptadmin';

    /**
     * คำนำหน้าชื่อ Sanctum token ของแอปแอดมิน
     * - admin-pair-   : จับคู่ด้วย QR (Api\Admin\PairingController::claim)
     * - admin-mobile- : ล็อกอินด้วยอีเมล/รหัสผ่านในแอป (Api\Admin\AuthController::issueAdminToken)
     * token อื่นที่มี ability admin (เช่น warroom) ไม่ใช่ "เครื่องแอปแอดมิน" → ไม่แสดง/ถอดจากหน้านี้ไม่ได้
     */
    private const DEVICE_TOKEN_PREFIXES = ['admin-pair-', 'admin-mobile-'];

    /**
     * แสดงหน้า "เชื่อมต่อ Admin Mobile App"
     */
    public function index(Request $request)
    {
        return view('admin.mobile-pair', [
            'pageTitle' => 'เชื่อมต่อ Admin Mobile App',
        ]);
    }

    /**
     * POST /admin/mobile-pair/init — สร้าง pair_code (session auth)
     */
    public function init(Request $request): JsonResponse
    {
        $admin = $request->user();

        // Rate limit ต่อ admin user
        $rlKey = 'admin-web-pair-init:'.$admin->id;
        if (RateLimiter::tooManyAttempts($rlKey, 30)) {
            $seconds = RateLimiter::availableIn($rlKey);

            return $this->error('สร้างรหัสจับคู่บ่อยเกินไป กรุณาลองใหม่ใน '.$seconds.' วินาที', 429);
        }
        RateLimiter::hit($rlKey, 3600);

        // ตรวจสอบ admin role อีกที (กันกรณี middleware miss)
        $isAdmin = ($admin->is_super_admin ?? false) || ($admin->role ?? null) === 'admin';
        if (! $isAdmin) {
            return $this->error('บัญชีนี้ไม่มีสิทธิ์ admin', 403);
        }

        // สร้าง readable pair code
        $pairCode = $this->generateReadablePairCode(8);

        // ตรวจสอบ 2FA
        $twoFactor = $admin->twoFactorSettings;
        $requires2fa = $twoFactor && ($twoFactor->enabled ?? false);

        $token = MobileAuthToken::create([
            'login_token' => hash('sha256', Str::random(64)),
            'login_token_expires_at' => now()->addSeconds(self::PAIR_CODE_TTL),
            'code_challenge' => 'admin-pair-flow',
            'state' => Str::random(32),
            'device_id' => 'pending-'.Str::random(16),
            'device_name' => 'Pending Pair',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_admin' => true,
            'pair_code' => hash('sha256', $pairCode),
            'pair_code_expires_at' => now()->addSeconds(self::PAIR_CODE_TTL),
            'generator_user_id' => $admin->id,
            'requires_2fa' => $requires2fa,
            'two_factor_passed' => false,
        ]);

        Log::info('Admin web pair code generated', [
            'admin_id' => $admin->id,
            'token_id' => $token->id,
            'requires_2fa' => $requires2fa,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'pair_code' => $pairCode,
                'qr_payload' => self::APP_SCHEME.'://pair/'.$pairCode,
                'expires_in' => self::PAIR_CODE_TTL,
                'requires_2fa' => $requires2fa,
                'admin_email' => $admin->email,
            ],
        ]);
    }

    /**
     * GET /admin/mobile-pair/status?pair_code=XXX
     */
    public function status(Request $request): JsonResponse
    {
        $pairCode = $request->query('pair_code');
        if (! $pairCode || strlen($pairCode) !== 8) {
            return response()->json(['success' => false, 'message' => 'pair_code ไม่ถูกต้อง'], 422);
        }

        $token = MobileAuthToken::where('pair_code', hash('sha256', strtoupper($pairCode)))
            ->where('is_admin', true)
            ->first();

        if (! $token) {
            return response()->json(['success' => true, 'data' => ['status' => 'not_found']]);
        }

        if ($token->isPairCodeExpired() && ! $token->claimed_at) {
            return response()->json(['success' => true, 'data' => ['status' => 'expired']]);
        }

        if ($token->claimed_at) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'claimed',
                    'device_name' => $token->device_name,
                    'claimed_at' => $token->claimed_at->toIso8601String(),
                ],
            ]);
        }

        return response()->json(['success' => true, 'data' => ['status' => 'pending']]);
    }

    /**
     * POST /admin/mobile-pair/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $admin = $request->user();
        $pairCode = $request->input('pair_code');

        if (! $pairCode || strlen($pairCode) !== 8) {
            return response()->json(['success' => false, 'message' => 'pair_code ไม่ถูกต้อง'], 422);
        }

        $deleted = MobileAuthToken::where('pair_code', hash('sha256', strtoupper($pairCode)))
            ->where('is_admin', true)
            ->where('generator_user_id', $admin->id)
            ->whereNull('claimed_at')
            ->delete();

        return response()->json(['success' => true, 'data' => ['cancelled' => $deleted > 0]]);
    }

    /**
     * GET /admin/mobile-pair/devices — เครื่องที่จับคู่แอปแอดมินแล้วของ "ทุกแอดมิน"
     *
     * ไม่คืน token / FCM token ใด ๆ — คืนแค่ชื่อเครื่อง เจ้าของ เวลาใช้ล่าสุด และสถานะแจ้งเตือน
     * can_revoke = เครื่องของตัวเอง หรือผู้ดูเป็น super admin
     */
    public function devices(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $canRevokeAny = $this->isSuperAdmin($viewer);

        $tokens = PersonalAccessToken::query()
            ->where('tokenable_type', (new User)->getMorphClass())
            ->where(function ($q) {
                foreach (self::DEVICE_TOKEN_PREFIXES as $prefix) {
                    $q->orWhere('name', 'like', $prefix.'%');
                }
            })
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'tokenable_id', 'name', 'abilities', 'last_used_at', 'created_at']);

        // ต้องมี ability admin จริง (กันชื่อพ้องจาก token ระบบอื่น)
        $tokens = $tokens->filter(fn (PersonalAccessToken $t) => in_array('admin', (array) $t->abilities, true))->values();

        $owners = User::query()
            ->whereIn('id', $tokens->pluck('tokenable_id')->unique()->all())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        // เครื่องที่ลงทะเบียนรับแจ้งเตือนแล้ว (นับตาม Sanctum token ที่ลงทะเบียน)
        $pushTokenIds = AdminPushToken::query()
            ->whereIn('access_token_id', $tokens->pluck('id')->all())
            ->pluck('access_token_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $pairNames = $this->pairDeviceNames($tokens);

        $devices = $tokens->map(function (PersonalAccessToken $t) use ($viewer, $canRevokeAny, $owners, $pushTokenIds, $pairNames) {
            [$prefix, $deviceKey, $nameInToken] = $this->parseTokenName($t->name);
            $owner = $owners->get($t->tokenable_id);
            $isMe = (int) $t->tokenable_id === (int) $viewer->id;

            return [
                'id' => $t->id,
                'device_name' => $nameInToken
                    ?? $pairNames[$t->tokenable_id.'|'.$deviceKey]
                    ?? 'อุปกรณ์ '.$deviceKey,
                'method' => $prefix === 'admin-pair-' ? 'qr' : 'password',
                'admin' => [
                    'id' => $owner?->id ?? (int) $t->tokenable_id,
                    'name' => $owner?->name ?? 'บัญชีที่ถูกลบ',
                ],
                'push_enabled' => $pushTokenIds->has((int) $t->id),
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'paired_at' => $t->created_at?->toIso8601String(),
                'is_me' => $isMe,
                'can_revoke' => $isMe || $canRevokeAny,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $devices->count(),
                'admins' => $devices->pluck('admin.id')->unique()->count(),
                'devices' => $devices,
            ],
        ]);
    }

    /**
     * POST /admin/mobile-pair/devices/{tokenId}/revoke — ถอดเครื่อง (ลบ Sanctum token + FCM token ของเครื่องนั้น)
     *
     * แอดมินถอดได้เฉพาะเครื่องตัวเอง · super admin ถอดได้ทุกเครื่อง
     * ถอดแล้ว แอปเครื่องนั้นได้ 401 ในคำขอถัดไป → กลับไปหน้าจับคู่
     */
    public function revokeDevice(Request $request, int $tokenId): JsonResponse
    {
        $viewer = $request->user();

        $token = PersonalAccessToken::query()
            ->where('id', $tokenId)
            ->where('tokenable_type', (new User)->getMorphClass())
            ->first();

        if (! $token || ! $this->isDeviceTokenName($token->name)) {
            return $this->error('ไม่พบเครื่องนี้ (อาจถูกถอดไปแล้ว)', 404);
        }

        $isMe = (int) $token->tokenable_id === (int) $viewer->id;
        if (! $isMe && ! $this->isSuperAdmin($viewer)) {
            return $this->error('ถอดได้เฉพาะเครื่องของตัวเอง (super admin ถอดได้ทุกเครื่อง)', 403);
        }

        DB::transaction(function () use ($token) {
            AdminPushToken::query()->where('access_token_id', $token->id)->delete();
            $token->delete();
        });

        Log::info('Admin web revoked admin-app device', [
            'by_admin_id' => $viewer->id,
            'owner_id' => $token->tokenable_id,
            'access_token_id' => $token->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['revoked' => true, 'id' => $token->id],
            'message' => 'ถอดเครื่องแล้ว',
        ]);
    }

    /**
     * แยกชื่อ token "admin-pair-<device_id 12 ตัว> (<ชื่อเครื่อง>)" → [prefix, device_key, ชื่อเครื่อง|null]
     *
     * @return array{0: string, 1: string, 2: string|null}
     */
    private function parseTokenName(string $name): array
    {
        $prefix = collect(self::DEVICE_TOKEN_PREFIXES)->first(fn ($p) => str_starts_with($name, $p)) ?? '';
        $rest = substr($name, strlen($prefix));

        if (preg_match('/^(\S+) \((.+)\)$/u', $rest, $m)) {
            return [$prefix, $m[1], $m[2]];
        }

        return [$prefix, trim($rest), null];
    }

    /**
     * token รุ่นเก่า (ชื่อไม่มีชื่อเครื่อง) → หาชื่อจากแถวจับคู่ใน mobile_auth_tokens
     * key = "<user_id>|<device_id 12 ตัวแรก>"
     *
     * @return array<string, string>
     */
    private function pairDeviceNames(Collection $tokens): array
    {
        $needs = $tokens->filter(fn ($t) => $this->parseTokenName($t->name)[2] === null);
        if ($needs->isEmpty()) {
            return [];
        }

        return MobileAuthToken::query()
            ->where('is_admin', true)
            ->whereNotNull('claimed_at')
            ->whereIn('user_id', $needs->pluck('tokenable_id')->unique()->all())
            ->orderBy('claimed_at')
            ->get(['user_id', 'device_id', 'device_name'])
            ->mapWithKeys(fn ($row) => [$row->user_id.'|'.substr((string) $row->device_id, 0, 12) => (string) $row->device_name])
            ->all();
    }

    private function isDeviceTokenName(string $name): bool
    {
        foreach (self::DEVICE_TOKEN_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->isSuperAdmin() || ($user->role ?? null) === 'super_admin';
    }

    private function generateReadablePairCode(int $length = 8): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $charsLen = strlen($chars);
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $charsLen - 1)];
        }

        return $code;
    }

    private function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
