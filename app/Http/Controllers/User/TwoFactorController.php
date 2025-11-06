<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Show 2FA setup page
     */
    public function setup()
    {
        $user = Auth::user();
        $status = $this->twoFactorService->getStatus($user);

        return view('user.two-factor.setup', compact('status'));
    }

    /**
     * Enable 2FA
     */
    public function enable(Request $request)
    {
        $request->validate([
            'preferred_method' => ['required', 'in:sms,line,email'],
        ]);

        $user = Auth::user();
        $result = $this->twoFactorService->enable($user, $request->input('preferred_method'));

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        // Show recovery codes
        return redirect()->route('two-factor.recovery-codes', ['codes' => json_encode($result['recovery_codes'])])
            ->with('success', $result['message']);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $user = Auth::user();
        $result = $this->twoFactorService->disable($user);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Show 2FA verification page
     */
    public function verify(Request $request)
    {
        $action = $request->input('action', 'general');
        $redirect = $request->input('redirect');

        return view('user.two-factor.verify', compact('action', 'redirect'));
    }

    /**
     * Send 2FA code
     */
    public function sendCode(Request $request)
    {
        $user = Auth::user();
        $action = $request->input('action', 'verification');

        $result = $this->twoFactorService->sendCode($user, $action);

        return response()->json($result);
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'action' => ['required', 'string'],
            'remember_device' => ['boolean'],
        ]);

        $user = Auth::user();
        $result = $this->twoFactorService->verifyCode(
            $user,
            $request->input('code'),
            $request->input('action'),
            $request->boolean('remember_device')
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'redirect' => $request->input('redirect') ?? route('user.dashboard'),
        ]);
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'recovery_code' => ['required', 'string'],
            'action' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $result = $this->twoFactorService->verifyRecoveryCode(
            $user,
            $request->input('recovery_code'),
            $request->input('action')
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'warning' => $result['warning'] ?? null,
            'redirect' => $request->input('redirect') ?? route('user.dashboard'),
        ]);
    }

    /**
     * Show recovery codes
     */
    public function showRecoveryCodes(Request $request)
    {
        $codes = json_decode($request->input('codes', '[]'), true);
        return view('user.two-factor.recovery-codes', compact('codes'));
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = Auth::user();
        $userSettings = \App\Models\TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (!$userSettings || !$userSettings->enabled) {
            return redirect()->back()->with('error', '2FA ยังไม่ได้เปิดใช้งาน');
        }

        $codes = $userSettings->generateRecoveryCodes();

        return redirect()->route('two-factor.recovery-codes', ['codes' => json_encode($codes)])
            ->with('success', 'สร้างรหัสกู้คืนใหม่เรียบร้อยแล้ว');
    }

    /**
     * Remove trusted device
     */
    public function removeTrustedDevice(Request $request)
    {
        $request->validate([
            'device_fingerprint' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $userSettings = \App\Models\TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (!$userSettings) {
            return response()->json(['success' => false, 'message' => 'User settings not found'], 404);
        }

        $userSettings->removeTrustedDevice($request->input('device_fingerprint'));

        return response()->json(['success' => true, 'message' => 'อุปกรณ์ถูกลบออกเรียบร้อยแล้ว']);
    }

    /**
     * Remove all trusted devices
     */
    public function removeAllTrustedDevices(Request $request)
    {
        $user = Auth::user();
        $userSettings = \App\Models\TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (!$userSettings) {
            return redirect()->back()->with('error', 'ไม่พบการตั้งค่า');
        }

        $userSettings->removeAllTrustedDevices();

        return redirect()->back()->with('success', 'ลบอุปกรณ์ที่เชื่อถือทั้งหมดเรียบร้อยแล้ว');
    }
}
