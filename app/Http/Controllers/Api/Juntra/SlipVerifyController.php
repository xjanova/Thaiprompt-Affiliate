<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\Fortune\SlipOkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/v1/juntra/payment/verify-slip
 *
 * ตรวจสลิปโอนเงินให้เว็บ จันทรา.online (juntraweb) ด้วย SlipOK ตัวเดียวกับที่
 * บอทแม่หมอใน Facebook/LINE ใช้อยู่
 *
 * ทำไมต้องอยู่ฝั่งนี้: บัญชี SlipOK + พูลโควตา + flood guard อยู่รีโปนี้
 * (SlipOkAccountPool หมุนหลายบัญชีเพราะโควตาฟรีประมาณ 100 ใบ/เดือน/บัญชี)
 * ถ้า juntraweb ไปต่อ SlipOK เองจะกลายเป็นโควตาคนละก้อน แอดมินต้องดูแล
 * สองที่ และ flood guard ก็จะไม่เห็นกัน
 *
 * ที่นี่ "ตรวจอย่างเดียว" — ไม่ตัดสินใจเรื่องเงินให้ juntraweb เพราะบิลของ
 * เว็บอยู่ในวอลเลตของเว็บเอง (คนละระบบกับ FortuneReading ของบอท)
 * juntraweb เป็นคนเทียบยอด/กันสลิปซ้ำ/เครดิตเข้ากระเป๋าเอง
 */
class SlipVerifyController extends Controller
{
    public function __invoke(Request $request, SlipOkService $slipok): JsonResponse
    {
        if (! $slipok->isEnabled()) {
            return response()->json(['message' => 'ระบบตรวจสลิปปิดใช้งานอยู่'], 503);
        }

        $request->validate([
            'slip' => 'required|image|max:6144',
        ]);

        $user     = $request->user();
        $platform = 'juntra';
        $userId   = (string) ($user->id ?? '');

        // flood guard ใช้ตัวเดียวกับบอท — กันคนยิงสลิปปลอมรัวจนโควตาหมด
        if (! $slipok->canSpendForUser($platform, $userId)) {
            $slipok->registerOverflowStrike($platform, $userId);

            return response()->json([
                'message'     => 'ตรวจสลิปบ่อยเกินไป กรุณารอสักครู่',
                'reason_code' => 'flood_guard',
            ], 429);
        }

        try {
            $verify = $slipok->verifyByFile(
                $request->file('slip')->getRealPath(),
                $platform,
                $userId,
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra slip verify threw', ['err' => $e->getMessage()]);

            return response()->json(['message' => 'ตรวจสลิปไม่สำเร็จชั่วคราว'], 503);
        }

        return response()->json(['data' => [
            'ok'               => (bool) ($verify['ok'] ?? false),
            'message'          => (string) ($verify['message'] ?? ''),
            'error_code'       => $verify['error_code'] ?? null,
            'trans_ref'        => $verify['transRef'] ?? null,
            'amount'           => $verify['amount'] ?? null,
            'receiver_account' => $verify['receiver_account'] ?? null,
            'receiver_name'    => $verify['receiver_name'] ?? null,
            'sender_name'      => $verify['sender_name'] ?? null,
            'trans_timestamp'  => $verify['trans_timestamp'] ?? null,
            // เข้าบัญชีร้านเราจริงไหม — juntraweb ต้องเช็คข้อนี้ก่อนเครดิต
            // ไม่งั้นสลิปโอนให้คนอื่นก็ผ่านได้
            'receiver_matches' => $slipok->receiverMatchesOurAccounts($verify['receiver_account'] ?? null),
        ]]);
    }
}
