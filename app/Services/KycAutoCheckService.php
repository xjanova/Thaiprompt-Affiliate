<?php

namespace App\Services;

use App\Models\KycVerification;
use App\Models\SlipVerificationLog;
use App\Services\Fortune\FortunePaymentFuzzyMatcher;
use Carbon\Carbon;

/**
 * ตรวจ KYC อัตโนมัติ (ชั้นที่ 1 — ไม่ใช้บริการภายนอกเพิ่ม)
 *
 * ให้คะแนนคำขอยืนยันตัวตนจากข้อมูลที่มีอยู่แล้วในระบบ:
 *   1. เลขบัตรประชาชน 13 หลัก ตรวจ checksum ตามสูตรกรมการปกครอง
 *   2. วันหมดอายุบัตร (จาก OCR)
 *   3. ชื่อจากบัตร ↔ ชื่อบัญชีธนาคารที่ลูกค้าผูกไว้ (ช่องทางรับเงินถอน)
 *   4. ชื่อจากบัตร ↔ ชื่อผู้โอนจากสลิปจริงที่ลูกค้าเคยจ่ายค่าดูดวง
 *      (SlipVerificationLog — หลักฐานจากธนาคารจริง ปลอมยากที่สุด)
 *
 * ผล = pass / warn / fail / unknown ต่อข้อ + สรุป all_green
 * ⚠️ เป็นตัวช่วยตัดสินใจ — การอนุมัติยังเป็นแอดมินกดเสมอ (นโยบายการเงิน)
 */
class KycAutoCheckService
{
    /**
     * ตัดคำนำหน้าชื่อ + ยุบช่องว่าง ก่อนเทียบ
     * (นางสาว ต้องมาก่อน นาง — ไม่งั้น "นางสาว" โดนตัดเหลือ "สาว")
     */
    public static function normalizeName(string $s): string
    {
        // ⚠️ ตัวยาวต้องมาก่อนตัวสั้นที่เป็น prefix กัน: นางสาว ก่อน นาง / MRS ก่อน MR
        $s = preg_replace('/^(นางสาว|นาง|นาย|น\.ส\.|ด\.ญ\.|ด\.ช\.|เด็กหญิง|เด็กชาย|MRS\.?|MISS|MS\.?|MR\.?)\s*/ui', '', trim($s));

        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /**
     * ตรวจ checksum เลขบัตรประชาชนไทย 13 หลัก
     */
    public static function validThaiIdChecksum(string $id): bool
    {
        $id = preg_replace('/\D/', '', $id);
        if (strlen($id) !== 13) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $id[$i] * (13 - $i);
        }

        return (11 - ($sum % 11)) % 10 === (int) $id[12];
    }

    /**
     * รันทุกข้อ — คืน checks + สรุป
     *
     * @return array{checks: array<int, array{key: string, label: string, status: string, detail: string}>, pass: int, warn: int, fail: int, unknown: int, all_green: bool}
     */
    public function run(KycVerification $kyc): array
    {
        $user = $kyc->user;
        $extracted = (array) ($kyc->extracted_data ?? []);

        // ชื่อจากบัตร: OCR ก่อน → คอลัมน์ user (เคยอนุมัติ/กรอกไว้) เป็น fallback
        $kycName = trim(
            trim((string) ($extracted['thai_first_name'] ?? $user?->thai_first_name ?? ''))
            .' '
            .trim((string) ($extracted['thai_last_name'] ?? $user?->thai_last_name ?? ''))
        );

        $checks = [
            $this->checkIdChecksum($extracted),
            $this->checkCardExpiry($extracted),
            $this->checkBankAccountName($user, $kycName),
            $this->checkSlipSenderName($user, $kycName),
        ];

        $count = ['pass' => 0, 'warn' => 0, 'fail' => 0, 'unknown' => 0];
        $identityProven = false;
        foreach ($checks as $c) {
            $count[$c['status']]++;
            // ต้องมีอย่างน้อย 1 ข้อที่ "พิสูจน์ว่าบัตรเป็นของคนนี้จริง" —
            // checksum/วันหมดอายุแค่บอกว่าบัตรจริง ไม่ได้บอกว่าเป็นบัตรของเขา
            // (กันเคสเอาบัตรจริงของคนอื่นมาสมัคร แล้วป้ายเขียวหลอกแอดมิน)
            if (in_array($c['key'], ['name_bank', 'name_slip'], true) && $c['status'] === 'pass') {
                $identityProven = true;
            }
        }

        return [
            'checks' => $checks,
            'pass' => $count['pass'],
            'warn' => $count['warn'],
            'fail' => $count['fail'],
            'unknown' => $count['unknown'],
            'all_green' => $count['fail'] === 0 && $count['warn'] === 0 && $identityProven,
        ];
    }

    /**
     * 1) เลขบัตร 13 หลัก + checksum
     */
    protected function checkIdChecksum(array $extracted): array
    {
        $id = (string) ($extracted['id_card_number'] ?? '');

        if ($id === '') {
            return $this->result('id_checksum', 'เลขบัตรประชาชน (checksum)', 'unknown',
                'OCR อ่านเลขบัตรไม่ได้ — ตรวจจากรูปด้วยตนเอง');
        }

        return self::validThaiIdChecksum($id)
            ? $this->result('id_checksum', 'เลขบัตรประชาชน (checksum)', 'pass',
                'เลข '.$id.' ผ่านสูตรตรวจของกรมการปกครอง')
            : $this->result('id_checksum', 'เลขบัตรประชาชน (checksum)', 'fail',
                'เลข '.$id.' ไม่ผ่าน checksum — อาจอ่านผิดหรือบัตรปลอม');
    }

    /**
     * 2) วันหมดอายุบัตร
     */
    protected function checkCardExpiry(array $extracted): array
    {
        $raw = (string) ($extracted['expiry_date'] ?? '');

        if ($raw === '') {
            return $this->result('card_expiry', 'วันหมดอายุบัตร', 'unknown',
                'OCR อ่านวันหมดอายุไม่ได้ — ตรวจจากรูปด้วยตนเอง');
        }

        try {
            $expiry = Carbon::parse($raw);
        } catch (\Throwable) {
            return $this->result('card_expiry', 'วันหมดอายุบัตร', 'unknown',
                'อ่านค่าได้ "'.$raw.'" แต่แปลงวันที่ไม่ได้ — ตรวจด้วยตนเอง');
        }

        // ปี พ.ศ. หลุดมาบางครั้ง (2570) — ถ้าเกินปีปัจจุบัน +80 ให้ถือว่าเป็น พ.ศ. แล้วลบ 543
        if ($expiry->year > now()->year + 80) {
            $expiry = $expiry->subYears(543);
        }

        return $expiry->isFuture()
            ? $this->result('card_expiry', 'วันหมดอายุบัตร', 'pass',
                'บัตรหมดอายุ '.$expiry->format('d/m/Y').' — ยังไม่หมดอายุ')
            : $this->result('card_expiry', 'วันหมดอายุบัตร', 'fail',
                'บัตรหมดอายุแล้วเมื่อ '.$expiry->format('d/m/Y'));
    }

    /**
     * 3) ชื่อจากบัตร ↔ ชื่อบัญชีธนาคาร (ช่องทางรับเงิน)
     */
    protected function checkBankAccountName($user, string $kycName): array
    {
        $label = 'ชื่อบัตร ↔ ชื่อบัญชีธนาคาร';

        if ($kycName === '') {
            return $this->result('name_bank', $label, 'unknown', 'ไม่มีชื่อจากบัตรให้เทียบ (OCR อ่านไม่ได้)');
        }

        $methods = $user?->paymentMethods()
            ->where('is_active', true)
            ->whereNotNull('account_name')
            ->pluck('account_name') ?? collect();

        if ($methods->isEmpty()) {
            return $this->result('name_bank', $label, 'unknown', 'ลูกค้ายังไม่ได้ผูกช่องทางรับเงิน');
        }

        $normalizedKyc = self::normalizeName($kycName);
        $matcher = app(FortunePaymentFuzzyMatcher::class);
        $best = 0;
        $bestName = '';

        foreach ($methods as $accountName) {
            if (self::normalizeName((string) $accountName) === $normalizedKyc) {
                return $this->result('name_bank', $label, 'pass',
                    'ตรงกับบัญชี "'.$accountName.'"');
            }
            $score = $matcher->scoreNameMatch(self::normalizeName((string) $accountName), $normalizedKyc);
            if ($score > $best) {
                $best = $score;
                $bestName = (string) $accountName;
            }
        }

        if ($best >= 80) {
            return $this->result('name_bank', $label, 'warn',
                'ใกล้เคียงกับบัญชี "'.$bestName.'" ('.$best.'%) — ตรวจด้วยตนเอง');
        }

        return $this->result('name_bank', $label, 'fail',
            'ไม่ตรงกับบัญชีที่ผูกไว้เลย (ใกล้สุด "'.$bestName.'" '.$best.'%)');
    }

    /**
     * 4) ชื่อจากบัตร ↔ ชื่อผู้โอนจากสลิปจริงที่เคยจ่ายค่าดูดวง
     *
     * หลักฐานแข็งสุด — ชื่อนี้มาจากธนาคารผ่าน SlipOK ตอนลูกค้าโอนจริง
     * (ธนาคารมักย่อนามสกุล เช่น "สมชาย ใ" → ใช้ fuzzy score ตัวเดียวกับ SMS matcher)
     */
    protected function checkSlipSenderName($user, string $kycName): array
    {
        $label = 'ชื่อบัตร ↔ ผู้โอนในสลิปที่เคยจ่าย';

        if ($kycName === '') {
            return $this->result('name_slip', $label, 'unknown', 'ไม่มีชื่อจากบัตรให้เทียบ');
        }

        $chatIds = array_values(array_filter([
            $user?->facebook_psid,
            $user?->line_user_id,
        ]));

        if (empty($chatIds)) {
            return $this->result('name_slip', $label, 'unknown', 'บัญชีนี้ไม่ได้ผูกกับแชทบอท — ไม่มีสลิปให้เทียบ');
        }

        $senders = SlipVerificationLog::whereIn('chat_user_id', $chatIds)
            ->whereNotNull('sender_name')
            ->latest()
            ->limit(30)
            ->pluck('sender_name')
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->values();

        if ($senders->isEmpty()) {
            return $this->result('name_slip', $label, 'unknown', 'ยังไม่มีสลิปที่อ่านชื่อผู้โอนได้');
        }

        $normalizedKyc = self::normalizeName($kycName);
        $matcher = app(FortunePaymentFuzzyMatcher::class);
        $best = 0;
        $bestName = '';

        foreach ($senders as $sender) {
            $score = $matcher->scoreNameMatch(self::normalizeName($sender), $normalizedKyc);
            if ($score > $best) {
                $best = $score;
                $bestName = $sender;
            }
        }

        if ($best >= 80) {
            return $this->result('name_slip', $label, 'pass',
                'ตรงกับผู้โอน "'.$bestName.'" ('.$best.'%) — หลักฐานจากธนาคารจริง');
        }

        if ($best >= 50) {
            return $this->result('name_slip', $label, 'warn',
                'ใกล้เคียงผู้โอน "'.$bestName.'" ('.$best.'%) — ตรวจด้วยตนเอง');
        }

        return $this->result('name_slip', $label, 'fail',
            'ไม่ตรงกับผู้โอนสลิปไหนเลย (ใกล้สุด "'.$bestName.'" '.$best.'%) — อาจใช้บัตรคนอื่นสมัคร');
    }

    /**
     * โครงผลลัพธ์ต่อข้อ
     */
    protected function result(string $key, string $label, string $status, string $detail): array
    {
        return compact('key', 'label', 'status', 'detail');
    }
}
