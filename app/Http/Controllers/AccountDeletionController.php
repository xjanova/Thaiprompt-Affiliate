<?php

namespace App\Http\Controllers;

use App\Exceptions\AccountDeletionBlockedException;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Support\AccountEmail;
use App\Support\ContactInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 🗑️ ลบบัญชีผ่านเว็บ (audit PLAY-05)
 *
 * GET  /account/delete  — หน้าสาธารณะ: อธิบายวิธีลบบัญชี (ในแอป / บนเว็บ / ทางอีเมล)
 *                          + ข้อมูลที่ลบ/ที่ต้องเก็บตามกฎหมาย ; ถ้าล็อกอินอยู่แสดงฟอร์มลบ
 *                          → URL นี้ใช้กรอกช่อง "Delete account URL" ใน Google Play Console
 * POST /account/delete  — (auth) ลบบัญชีผ่าน AccountDeletionService แล้วออกจากระบบ
 */
class AccountDeletionController extends Controller
{
    public function __construct(private readonly AccountDeletionService $deletion) {}

    /**
     * หน้าอธิบาย + ฟอร์มลบบัญชี (route: account.delete)
     */
    public function show(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        return view('account.delete', [
            'user' => $user,
            'blockers' => $user ? $this->deletion->blockers($user) : [],
            // วิธียืนยันตัวตน: password / email_code / two_factor / add_email (ดู AccountDeletionService::confirmationMethod)
            'confirmMethod' => $user ? $this->deletion->confirmationMethod($user) : null,
            'canUseEmailCode' => $user ? $this->deletion->canUseEmailCode($user) : false,
            'emailMasked' => $user && $this->deletion->canUseEmailCode($user) ? AccountEmail::mask((string) $user->email) : null,
            'confirmText' => AccountDeletionService::CONFIRM_TEXT,
            'supportEmail' => ContactInfo::supportEmail(),
            'deletedRef' => $request->session()->get('account_deleted_ref'),
        ]);
    }

    /**
     * ทางเข้าสำหรับคนที่ยังไม่ล็อกอิน (route: account.delete.login — หุ้ม auth)
     *
     * middleware auth จะจำ URL นี้ไว้ (url.intended) แล้วพาไปหน้า login
     * ล็อกอินเสร็จ LoginController::intended() พากลับมาที่นี่ → ส่งต่อไปหน้าฟอร์มลบบัญชี
     */
    public function login()
    {
        return redirect()->route('account.delete');
    }

    /**
     * ลบบัญชีของผู้ที่ล็อกอินอยู่ (route: account.delete.destroy)
     *
     * body: confirm_text (ต้องเป็น "ลบบัญชี") + หลักฐานอย่างน้อย 1 อย่าง:
     *       password · email_code (ขอที่ account.delete.send-code) · two_factor_code
     */
    public function destroy(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'confirm_text' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'max:255'],
            'email_code' => ['nullable', 'string', 'max:20'],
            'two_factor_code' => ['nullable', 'string', 'max:50'],
        ], [
            'confirm_text.required' => 'กรุณาพิมพ์คำว่า "'.AccountDeletionService::CONFIRM_TEXT.'" เพื่อยืนยัน',
        ]);

        $error = $this->deletion->confirmationError(
            $user,
            $request->input('confirm_text'),
            $request->input('password'),
            $request->input('email_code'),
            $request->input('two_factor_code'),
        );
        if ($error !== null) {
            return back()->withInput($request->only('confirm_text'))->withErrors([$error['field'] => $error['message']]);
        }

        try {
            $ref = $this->deletion->delete($user, 'self', $user);
        } catch (AccountDeletionBlockedException $e) {
            return back()->with('error', $e->getMessage())
                ->withErrors(['account' => collect($e->blockers())->pluck('message')->all()]);
        } catch (\Throwable $e) {
            Log::error('Web account deletion failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'ลบบัญชีไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หรือติดต่อทีมงาน');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('account.delete')
            ->with('account_deleted_ref', $ref)
            ->with('success', 'ลบบัญชีเรียบร้อยแล้ว');
    }

    /**
     * ส่งรหัส 6 หลักไปอีเมลของบัญชีเพื่อยืนยันการลบ (route: account.delete.send-code — fetch จากหน้าฟอร์ม)
     */
    public function sendCode(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->deletion->sendEmailCode($user);
        $sent = (bool) ($result['success'] ?? false);

        if (! $request->expectsJson()) {
            return back()->with($sent ? 'success' : 'error', $result['message']);
        }

        return response()->json([
            'success' => $sent,
            'message' => $result['message'],
            'retry_after' => $result['retry_after'] ?? null,
        ], $sent ? 200 : (isset($result['retry_after']) ? 429 : 422));
    }
}
