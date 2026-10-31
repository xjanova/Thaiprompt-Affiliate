<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AccountDeletionBlockedException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Support\AccountEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🗑️ ลบบัญชีจากแอป (audit PLAY-05 — Google Play บังคับให้มีทางลบบัญชีในแอป)
 *
 * GET    /api/v1/account/deletion-check  → แอปถามก่อนว่าลบได้ไหม (แสดงเหตุผลก่อนให้ผู้ใช้ยืนยัน)
 * DELETE /api/v1/account                 → ลบบัญชี {confirm_text: "ลบบัญชี", password? | email_code? | two_factor_code?}
 * POST   /api/v1/account/delete          → เหมือน DELETE (สำหรับ client ที่ส่ง body กับ DELETE ไม่ได้)
 * POST   /api/v1/account/delete/send-code → ส่งรหัส 6 หลักไปอีเมลของบัญชี (บัญชี LINE/FB/Google ที่ไม่รู้รหัสผ่าน)
 *
 * ลบสำเร็จ = token ทุกตัวของบัญชีถูกเพิกถอนแล้ว → แอปต้องล้าง token ในเครื่องและกลับหน้าเข้าสู่ระบบ
 */
class AccountController extends Controller
{
    public function __construct(private readonly AccountDeletionService $deletion) {}

    /**
     * ตรวจว่าลบบัญชีได้หรือยัง
     */
    public function deletionCheck(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $blockers = $this->deletion->blockers($user);

        return response()->json([
            'success' => true,
            'message' => empty($blockers) ? 'สามารถลบบัญชีได้' : 'ยังลบบัญชีไม่ได้ในขณะนี้',
            'data' => [
                'can_delete' => empty($blockers),
                'blockers' => $blockers,
                'requires_password' => $this->deletion->requiresPassword($user),
                // 🔒 (2026-10-29) วิธียืนยันตัวตน: password / email_code / two_factor / add_email
                'confirmation_method' => $this->deletion->confirmationMethod($user),
                'can_use_email_code' => $this->deletion->canUseEmailCode($user),
                'email_hint' => $this->deletion->canUseEmailCode($user) ? AccountEmail::mask((string) $user->email) : null,
                'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
                'info_url' => route('account.delete'),
            ],
        ]);
    }

    /**
     * ส่งรหัส 6 หลักไปอีเมลของบัญชีเพื่อยืนยันการลบ (POST /api/v1/account/delete/send-code)
     */
    public function sendDeletionCode(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->deletion->sendEmailCode($user);
        $sent = (bool) ($result['success'] ?? false);

        return response()->json([
            'success' => $sent,
            'code' => $sent ? 'EMAIL_CODE_SENT' : (isset($result['retry_after']) ? 'EMAIL_CODE_COOLDOWN' : 'EMAIL_CODE_UNAVAILABLE'),
            'message' => $result['message'],
            'data' => ['retry_after' => $result['retry_after'] ?? null],
        ], $sent ? 200 : (isset($result['retry_after']) ? 429 : 422));
    }

    /**
     * ลบบัญชีของผู้ใช้ปัจจุบัน
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $error = $this->deletion->confirmationError(
            $user,
            $request->input('confirm_text'),
            $request->input('password'),
            $request->input('email_code'),
            $request->input('two_factor_code'),
        );
        if ($error !== null) {
            return response()->json([
                'success' => false,
                'code' => $error['code'],
                'message' => $error['message'],
                'errors' => [$error['field'] => [$error['message']]],
            ], 422);
        }

        try {
            $ref = $this->deletion->delete($user, 'self', $user);
        } catch (AccountDeletionBlockedException $e) {
            return response()->json([
                'success' => false,
                'code' => $e->primaryCode(),
                'message' => $e->getMessage(),
                'data' => [
                    'blockers' => $e->blockers(),
                ],
            ], 409);
        } catch (\Throwable $e) {
            Log::error('API account deletion failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_DELETION_FAILED',
                'message' => 'ลบบัญชีไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หรือติดต่อทีมงาน',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'ลบบัญชีเรียบร้อยแล้ว',
            'data' => [
                'reference' => $ref,
            ],
        ]);
    }
}
