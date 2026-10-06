<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPushToken;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 Admin Mobile API: ลงทะเบียน/ถอน FCM token ของเครื่องแอดมิน (แจ้งเตือนคิวงานจาก admin-app:push-alerts)
 *
 * - token เป็นความลับระดับอุปกรณ์ — ไม่ log ไม่คืนกลับใน JSON
 * - 1 token = 1 แถว: เครื่องเดิมล็อกอินบัญชีแอดมินอื่น → แถวย้ายไปเป็นของบัญชีใหม่
 * - ออกจากระบบ (auth/logout) ลบ token ของ Sanctum token นั้น · logout-all ลบของแอดมินคนนั้นทั้งหมด (AuthController)
 */
class DevicesController extends Controller
{
    /**
     * POST /api/admin/devices/push-token
     * Body: { token: string, platform: android|ios|web, device_id?: string, app_version?: string }
     */
    public function storePushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string|min:20|max:512',
            'platform' => 'required|string|in:android,ios,web',
            'device_id' => 'nullable|string|max:191',
            'app_version' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $accessTokenId = self::currentAccessTokenId($request);

        // เครื่องเดิม (device_id เดิมของแอดมินคนเดิม) ได้ token ใหม่ → ลบ token เก่าของเครื่องนั้นทิ้ง ไม่ให้ยิงซ้ำสองชุด
        if (! empty($data['device_id'])) {
            AdminPushToken::query()
                ->where('user_id', $user->id)
                ->where('device_id', $data['device_id'])
                ->where('token', '!=', $data['token'])
                ->delete();
        }

        $values = [
            'user_id' => $user->id,
            'access_token_id' => $accessTokenId,
            'platform' => $data['platform'],
            'device_id' => $data['device_id'] ?? null,
            'app_version' => $data['app_version'] ?? null,
        ];

        try {
            $row = AdminPushToken::query()->updateOrCreate(['token' => $data['token']], $values);
        } catch (UniqueConstraintViolationException $e) {
            // ลงทะเบียน token เดียวกันพร้อมกันสองคำขอ (แอปเปิด + onTokenRefresh) — อีกคำขอสร้างแถวไปก่อน → อัปเดตแถวนั้นแทน
            $row = AdminPushToken::query()->where('token', $data['token'])->firstOrFail();
            $row->fill($values)->save();
        }

        Log::info('AdminApp: ลงทะเบียน push token', [
            'admin_id' => $user->id,
            'push_token_row' => $row->id,
            'platform' => $data['platform'],
            'app_version' => $data['app_version'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'registered' => true,
                'platform' => $row->platform,
                'device_id' => $row->device_id,
                'app_version' => $row->app_version,
                'updated_at' => $row->updated_at?->toIso8601String(),
            ],
            'message' => 'ลงทะเบียนเครื่องรับแจ้งเตือนแล้ว',
        ]);
    }

    /**
     * ลบ push token ตอนออกจากระบบ (เรียกจาก AuthController ก่อนลบ Sanctum token)
     *
     * - $allDevices = true (logout-all) → ลบทุกเครื่องของแอดมินคนนี้
     * - ไม่งั้น → ลบเครื่องที่ลงทะเบียนด้วย Sanctum token ปัจจุบัน + token ที่แอปส่งมาใน body (push_token / token) ถ้ามี
     *
     * ไม่โยน error — ออกจากระบบต้องสำเร็จเสมอ
     */
    public static function forgetOnLogout(Request $request, bool $allDevices): int
    {
        try {
            $user = $request->user();
            if (! $user) {
                return 0;
            }

            if ($allDevices) {
                return AdminPushToken::query()->where('user_id', $user->id)->delete();
            }

            $accessTokenId = self::currentAccessTokenId($request);
            $pushToken = $request->input('push_token', $request->input('token'));
            $pushToken = is_string($pushToken) && $pushToken !== '' ? $pushToken : null;

            if ($accessTokenId === null && $pushToken === null) {
                return 0;
            }

            return AdminPushToken::query()
                ->where('user_id', $user->id)
                ->where(function ($q) use ($accessTokenId, $pushToken) {
                    if ($accessTokenId !== null) {
                        $q->orWhere('access_token_id', $accessTokenId);
                    }
                    if ($pushToken !== null) {
                        $q->orWhere('token', $pushToken);
                    }
                })
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('AdminApp: ลบ push token ตอนออกจากระบบไม่สำเร็จ', ['error' => \App\Support\SafeLog::exceptionMessage($e)]);

            return 0;
        }
    }

    /**
     * id ของ Sanctum token ที่ใช้เรียกคำขอนี้ (null = ไม่ใช่ token จริง เช่น session/เทสต์)
     */
    private static function currentAccessTokenId(Request $request): ?int
    {
        try {
            $token = $request->user()?->currentAccessToken();
            if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $id = $token->getKey();

                return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
            }
        } catch (\Throwable $e) {
            // ไม่มี token จริง — ข้าม
        }

        return null;
    }

    /**
     * DELETE /api/admin/devices/push-token
     * Body: { token: string }
     */
    public function destroyPushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string|max:512',
        ]);

        // ถอนได้เฉพาะ token ของตัวเอง
        $removed = AdminPushToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json([
            'success' => true,
            'data' => ['removed' => $removed],
            'message' => $removed > 0 ? 'ยกเลิกการแจ้งเตือนของเครื่องนี้แล้ว' : 'ไม่พบเครื่องนี้ในรายการแจ้งเตือน',
        ]);
    }
}
