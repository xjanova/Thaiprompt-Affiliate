<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
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
 *
 * 🔴 (2026-07-26 FIX) เดิมดึง "บัญชี active ตัวแรกของทั้งระบบ" (is_default →
 * sort_order → id) ซึ่งไม่สนว่าแอดมินติ๊กบัญชีไหนไว้ในหน้าตั้งค่าดูดวง
 * (fortune_bank_account_ids) → เว็บสร้าง QR เป็นบัญชีร้าน แต่บอท FB/LINE รับเงิน
 * เข้าบัญชีดูดวง = ลูกค้าเว็บโอนผิดบัญชี ตัวตรวจสลิปตีกลับทั้งที่โอนถูก
 * (บั๊กชุดเดียวกับที่เคยเกิดกับ LINE เมื่อ 2026-07-24)
 * ตอนนี้เรียกผ่าน FortuneTellingSetting ตัวเดียวกับบอท → ติ๊กตัวไหนได้ตัวนั้น
 */
class PayoutAccountController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = FortuneTellingSetting::getSettings();

        // เอาบัญชีที่บอทใช้สร้าง QR เป็นหลัก เพราะเว็บก็เอาไปสร้าง QR เหมือนกัน
        // (ต้องเป็นใบเดียวกันเป๊ะ ๆ ไม่งั้น SlipOK จับปลายทางไม่ตรง)
        // ถ้าไม่มีบัญชีไหนตั้งพร้อมเพย์ไว้เลย ยังส่งบัญชีหลักไป เว็บจะได้มีเลขบัญชี
        // ให้ลูกค้าโอนแบบธนาคาร แล้วค่อย fallback ไปใช้พร้อมเพย์ที่ตั้งไว้ในเว็บเอง
        /** @var PaymentBankAccount|null $account */
        $account = $settings->getFortunePromptpayAccount()
            ?? $settings->getFortunePrimaryBankAccount();

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
