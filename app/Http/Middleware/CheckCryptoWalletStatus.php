<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCryptoWalletStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $wallet = $user->defaultCryptoWallet;

        if (!$wallet) {
            return redirect()
                ->route('user.crypto-wallet.index')
                ->with('error', 'ไม่พบกระเป๋าคริปโต');
        }

        // Check if wallet is active
        if ($wallet->status !== 'active') {
            $message = match ($wallet->status) {
                'locked' => 'กระเป๋าของคุณถูกล็อค กรุณาติดต่อฝ่ายสนับสนุน',
                'suspended' => 'กระเป๋าของคุณถูกระงับ กรุณาติดต่อฝ่ายสนับสนุน',
                default => 'กระเป๋าของคุณไม่สามารถใช้งานได้',
            };

            return redirect()
                ->route('user.crypto-wallet.index')
                ->with('error', $message);
        }

        return $next($request);
    }
}
