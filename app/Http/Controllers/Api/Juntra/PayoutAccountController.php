<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Models\PaymentBankAccount;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/juntra/payment/account
 *
 * บัญชีรับเงินของแม่หมอ — ตัวเดียวกับที่บอท Facebook/LINE ใช้อยู่
 *
 * เจ้าของกำหนด (2026-07-25): "ค่าการชำระเงิน ใช้ค่าเดิมของดูดวงแม่หมอเดียวกันเลย"
 * เดิมเว็บ จันทรา.online มีช่องตั้ง promptpay_id ของตัวเองในหน้าแอดมิน ซึ่งแปลว่า
 * ต้องตั้งค่าสองที่และมีโอกาสตั้งไม่ตรงกัน — ลูกค้าเว็บโอนเข้าบัญชีหนึ่ง ลูกค้าบอท
 * โอนอีกบัญชีหนึ่ง แล้ว SlipOK/SMS ที่ผูกกับบัญชีของบอทจะจับคู่สลิปฝั่งเว็บไม่ได้
 *
 * ตอนนี้เว็บดึงบัญชีจากที่นี่ที่เดียว = แก้บัญชีในหลังบ้านบอทแล้วมีผลทุกช่องทาง
 * ทันที และตัวตรวจสลิปอัตโนมัติทำงานได้เพราะปลายทางเป็นบัญชีเดียวกันเสมอ
 *
 * ส่งเลขบัญชีเต็ม (ไม่ mask) เพราะปลายทางคือ juntraweb ซึ่งเป็นระบบของเราเอง
 * และต้องเอาไปสร้าง QR ให้ลูกค้าสแกนจ่ายจริง — ไม่ใช่ข้อมูลที่ส่งให้ third party
 */
class PayoutAccountController extends Controller
{
    public function __invoke(): JsonResponse
    {
        /** @var PaymentBankAccount|null $account */
        $account = PaymentBankAccount::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $account) {
            return response()->json([
                'message' => 'ยังไม่ได้ตั้งบัญชีรับเงินในระบบแม่หมอ',
            ], 404);
        }

        return response()->json(['data' => [
            'promptpay_id'   => $account->promptpay_id,
            'promptpay_type' => $account->promptpay_type,
            'account_name'   => $account->account_name,
            'account_number' => $account->account_number,
            'bank_code'      => $account->bank_code,
            'bank_name'      => $account->bank_name,
            // เว็บใช้ตัดสินใจว่าจะเปิดโหมดจับยอดอัตโนมัติได้ไหม
            'sms_checker_enabled' => (bool) $account->sms_checker_enabled,
        ]]);
    }
}
