<?php

namespace App\Http\Middleware;

use App\Services\Pos\PosTerminalAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * middleware `pos.terminal` — ต้องเป็นเครื่อง POS ที่ยืนยันแล้ว (X-API-Key + X-Product-Key)
 *
 * ผ่าน: ฝาก PosTerminal ไว้ที่ $request->attributes['pos_terminal']
 * ไม่ผ่าน: 401 ข้อความเดียวกับ endpoint /api/pos/sync/* เดิม (ไม่บอกว่าผิดที่ key ไหน)
 */
class AuthenticatePosTerminal
{
    public function __construct(private readonly PosTerminalAuthenticator $authenticator) {}

    public function handle(Request $request, Closure $next): Response
    {
        $terminal = $this->authenticator->authenticate($request);

        if (! $terminal) {
            return response()->json([
                'success' => false,
                'code' => 'TERMINAL_UNAUTHORIZED',
                'message' => 'ไม่ได้รับอนุญาต',
            ], 401);
        }

        $request->attributes->set(PosTerminalAuthenticator::REQUEST_ATTRIBUTE, $terminal);

        return $next($request);
    }
}
