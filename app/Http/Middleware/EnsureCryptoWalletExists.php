<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCryptoWalletExists
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

        // Check if user has at least one crypto wallet
        if (!$user->cryptoWallets()->exists()) {
            return redirect()
                ->route('user.crypto-wallet.index')
                ->with('warning', 'กรุณาสร้างกระเป๋าคริปโตก่อนใช้งาน');
        }

        // Check if default wallet exists
        if (!$user->defaultCryptoWallet) {
            return redirect()
                ->route('user.crypto-wallet.wallets')
                ->with('warning', 'กรุณาตั้งกระเป๋าหลักก่อนใช้งาน');
        }

        return $next($request);
    }
}
