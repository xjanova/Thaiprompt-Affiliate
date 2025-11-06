<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = 'general'): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if 2FA is required for this action
        $amount = $request->input('amount'); // For withdrawal/transfer
        if (!$this->twoFactorService->isRequired($action, $user, $amount)) {
            return $next($request);
        }

        // Check if already verified in current session
        if ($this->twoFactorService->isVerified($action)) {
            return $next($request);
        }

        // Redirect to 2FA verification page
        return redirect()->route('two-factor.verify', [
            'action' => $action,
            'redirect' => $request->fullUrl(),
        ])->with('info', 'กรุณายืนยันตัวตนด้วย 2FA เพื่อดำเนินการต่อ');
    }
}
