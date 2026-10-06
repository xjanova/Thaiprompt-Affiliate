<?php

namespace App\Http\Controllers\Api\Admin\Approvals;

use App\Http\Controllers\Api\Admin\Approvals\Concerns\ApprovalResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Services\UserSuspensionService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🔒 แอปแอดมิน: ระงับ / ยกเลิกระงับบัญชี · รีเซ็ต PIN กระเป๋าเงิน — ตรรกะเดียวกับหลังบ้านเว็บ
 *
 * - suspend/unsuspend: UserPolicy::block (ห้ามตัวเอง · แอดมินขึ้นไป) + UserSuspensionService (ตัวเดียวกับ Admin\UserController)
 *   ระงับ = เพิกถอน token แอปทั้งหมด + ปล่อยงานไรเดอร์ที่ค้าง (User::suspend)
 *   บัญชีทีมงาน (admin/moderator) → เฉพาะ super admin (ไม่ใช่ = 403 STAFF_REQUIRES_SUPER_ADMIN)
 * - reset-wallet-pin: super admin เท่านั้น (เหมือน Admin\WalletController::resetUserPin) → WalletService::adminResetPin
 */
class UserModerationController extends Controller
{
    use ApprovalResponses;

    public function __construct(
        private readonly UserSuspensionService $suspensions,
        private readonly WalletService $wallets,
    ) {}

    /**
     * POST /api/admin/users/{user}/suspend  body: { reason? }
     */
    public function suspend(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->can('block', $user)) {
            return $this->forbidden((int) $request->user()->id === (int) $user->id
                ? 'ไม่สามารถระงับบัญชีของตัวเองได้'
                : 'ไม่มีสิทธิ์ระงับบัญชีนี้');
        }

        $data = $this->validateInput($request, [
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'reason.max' => 'เหตุผลต้องไม่เกิน 500 ตัวอักษร',
        ]);

        try {
            $result = $this->suspensions->suspend($user, $request->user(), $data['reason'] ?? null);
        } catch (\Throwable $e) {
            return $this->serverError('user_suspend', $e, ['user_id' => $user->id]);
        }

        return $this->respondResult($result, $user);
    }

    /**
     * POST /api/admin/users/{user}/unsuspend
     */
    public function unsuspend(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->can('block', $user)) {
            return $this->forbidden('ไม่มีสิทธิ์ยกเลิกการระงับบัญชีนี้');
        }

        try {
            $result = $this->suspensions->unsuspend($user, $request->user());
        } catch (\Throwable $e) {
            return $this->serverError('user_unsuspend', $e, ['user_id' => $user->id]);
        }

        return $this->respondResult($result, $user);
    }

    /**
     * POST /api/admin/users/{user}/reset-wallet-pin
     *
     * ล้าง PIN + ปลดล็อกจากการกรอกผิด → ผู้ใช้ตั้ง PIN ใหม่เองได้ (กดซ้ำได้ ผลเหมือนเดิม)
     */
    public function resetWalletPin(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->isSuperAdmin()) {
            return $this->forbidden('เฉพาะ Super Admin เท่านั้นที่สามารถรีเซ็ต PIN ได้');
        }

        $wallet = Wallet::query()->where('user_id', $user->id)->orderBy('id')->first();
        if (! $wallet) {
            return $this->fail('ผู้ใช้นี้ยังไม่มีกระเป๋าเงิน', 'WALLET_NOT_FOUND', 404);
        }

        try {
            $this->wallets->adminResetPin($wallet, $request->user());
        } catch (\Throwable $e) {
            return $this->serverError('reset_wallet_pin', $e, ['user_id' => $user->id, 'wallet_id' => $wallet->id]);
        }

        return $this->ok([
            'user_id' => (int) $user->id,
            'wallet_id' => (int) $wallet->id,
            'has_pin' => false,
            'locked' => false,
        ], 'รีเซ็ต PIN สำเร็จ ผู้ใช้สามารถตั้ง PIN ใหม่ได้');
    }

    /**
     * @param  array{ok: bool, level: string, code: string, message: string, status?: int}  $result
     */
    private function respondResult(array $result, User $user): JsonResponse
    {
        $user->refresh();
        $data = [
            'user_id' => (int) $user->id,
            'suspended' => $user->isSuspended(),
            'suspended_at' => $this->iso($user->blocked_at),
            'already' => $result['level'] === 'info',
        ];

        if (! $result['ok']) {
            // 403 = ไม่มีสิทธิ์กับบัญชีนี้ (เช่น บัญชีทีมงานต้องเป็น super admin) · 409 = ทำไม่ได้ตามกติกา
            $status = (int) ($result['status'] ?? 409);

            return $this->fail($result['message'], $result['code'], $status >= 400 ? $status : 409, $data);
        }

        return $this->ok($data, $result['message']);
    }
}
