<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AccountDeletionBlockedException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🗑️ ลบบัญชีจากแอป (audit PLAY-05 — Google Play บังคับให้มีทางลบบัญชีในแอป)
 *
 * GET    /api/v1/account/deletion-check  → แอปถามก่อนว่าลบได้ไหม (แสดงเหตุผลก่อนให้ผู้ใช้ยืนยัน)
 * DELETE /api/v1/account                 → ลบบัญชี {confirm_text: "ลบบัญชี", password?}
 * POST   /api/v1/account/delete          → เหมือน DELETE (สำหรับ client ที่ส่ง body กับ DELETE ไม่ได้)
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
                'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
                'info_url' => route('account.delete'),
            ],
        ]);
    }

    /**
     * ลบบัญชีของผู้ใช้ปัจจุบัน
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $error = $this->deletion->confirmationError($user, $request->input('confirm_text'), $request->input('password'));
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
