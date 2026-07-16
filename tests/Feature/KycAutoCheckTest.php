<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Models\SlipVerificationLog;
use App\Models\User;
use App\Services\KycAutoCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🤖 ทดสอบตรวจ KYC อัตโนมัติชั้นที่ 1 (ไม่ใช้บริการภายนอก)
 *
 * checksum เลขบัตร / วันหมดอายุ / ชื่อบัตร↔บัญชีธนาคาร /
 * ชื่อบัตร↔ผู้โอนจากสลิปจริง (ธนาคารมักย่อนามสกุล — ต้อง fuzzy)
 *
 * @group kyc
 */
class KycAutoCheckTest extends TestCase
{
    use RefreshDatabase;

    /** เลขบัตรที่ผ่าน checksum จริงตามสูตรกรมการปกครอง */
    private const VALID_ID = '1234567890121';

    private function makeUser(): User
    {
        return User::create([
            'name' => 'สมหญิง ใจดี',
            'email' => 'fb_5550001@thaiprompt.local',
            'password' => bcrypt(str()->random(20)),
            'facebook_psid' => '5550001',
        ]);
    }

    private function makeKyc(User $user, array $extracted): KycVerification
    {
        return KycVerification::create([
            'user_id' => $user->id,
            'id_card_image' => 'kyc/id-cards/test.webp',
            'selfie_image' => 'kyc/selfies/test.webp',
            'status' => 'pending',
            'submitted_at' => now(),
            'extracted_data' => $extracted,
        ]);
    }

    public function test_thai_id_checksum_math(): void
    {
        $this->assertTrue(KycAutoCheckService::validThaiIdChecksum(self::VALID_ID));
        $this->assertFalse(KycAutoCheckService::validThaiIdChecksum('1234567890120'), 'หลักสุดท้ายผิดต้องไม่ผ่าน');
        $this->assertFalse(KycAutoCheckService::validThaiIdChecksum('12345'), 'ไม่ครบ 13 หลักต้องไม่ผ่าน');
    }

    public function test_expired_card_is_flagged(): void
    {
        $user = $this->makeUser();
        $kyc = $this->makeKyc($user, [
            'id_card_number' => self::VALID_ID,
            'expiry_date' => now()->subYear()->format('Y-m-d'),
        ]);

        $result = app(KycAutoCheckService::class)->run($kyc);
        $expiry = collect($result['checks'])->firstWhere('key', 'card_expiry');

        $this->assertSame('fail', $expiry['status'], 'บัตรหมดอายุต้องโดนธงแดง');
        $this->assertFalse($result['all_green']);
    }

    public function test_buddhist_year_expiry_is_converted(): void
    {
        $user = $this->makeUser();
        // ปี พ.ศ. หลุดมาจาก OCR (ค.ศ. ปัจจุบัน + 543 + อีก 3 ปีข้างหน้า)
        $kyc = $this->makeKyc($user, [
            'expiry_date' => (now()->year + 543 + 3).'-01-15',
        ]);

        $result = app(KycAutoCheckService::class)->run($kyc);
        $expiry = collect($result['checks'])->firstWhere('key', 'card_expiry');

        $this->assertSame('pass', $expiry['status'], 'ปี พ.ศ. ต้องถูกแปลงเป็น ค.ศ. แล้วตัดสินถูก');
    }

    public function test_bank_account_name_matches_despite_title_prefix(): void
    {
        $user = $this->makeUser();
        $user->paymentMethods()->create([
            'type' => 'bank_transfer',
            'name' => 'บัญชีหลัก',
            'account_name' => 'นางสาว สมหญิง ใจดี',
            'account_number' => '1234567890',
            'bank_name' => 'กสิกรไทย',
            'is_active' => true,
        ]);

        $kyc = $this->makeKyc($user, [
            'id_card_number' => self::VALID_ID,
            'thai_first_name' => 'สมหญิง',
            'thai_last_name' => 'ใจดี',
        ]);

        $result = app(KycAutoCheckService::class)->run($kyc);
        $bank = collect($result['checks'])->firstWhere('key', 'name_bank');

        $this->assertSame('pass', $bank['status'], 'คำนำหน้าต่างกันต้องยังเทียบตรง');
    }

    public function test_wrong_bank_account_name_is_flagged(): void
    {
        $user = $this->makeUser();
        $user->paymentMethods()->create([
            'type' => 'bank_transfer',
            'name' => 'บัญชีหลัก',
            'account_name' => 'นายทองดี มั่งมีทรัพย์สิน',
            'account_number' => '1234567890',
            'bank_name' => 'กสิกรไทย',
            'is_active' => true,
        ]);

        $kyc = $this->makeKyc($user, [
            'thai_first_name' => 'สมหญิง',
            'thai_last_name' => 'ใจดี',
        ]);

        $result = app(KycAutoCheckService::class)->run($kyc);
        $bank = collect($result['checks'])->firstWhere('key', 'name_bank');

        $this->assertSame('fail', $bank['status'], 'ชื่อคนละคนต้องโดนธงแดง — อาจใช้บัตรคนอื่นสมัคร');
    }

    public function test_slip_sender_with_abbreviated_surname_matches(): void
    {
        $user = $this->makeUser();
        // ธนาคารย่อนามสกุลบนสลิป: "นาย สมหญิง ใ"
        SlipVerificationLog::create([
            'platform' => 'facebook',
            'chat_user_id' => '5550001',
            'sent_to_slipok' => true,
            'decision' => 'accepted',
            'sender_name' => 'นาง สมหญิง ใ',
        ]);

        $kyc = $this->makeKyc($user, [
            'thai_first_name' => 'สมหญิง',
            'thai_last_name' => 'ใจดี',
        ]);

        $result = app(KycAutoCheckService::class)->run($kyc);
        $slip = collect($result['checks'])->firstWhere('key', 'name_slip');

        $this->assertSame('pass', $slip['status'], 'นามสกุลย่อจากธนาคารต้องยัง match ด้วย fuzzy');
    }

    /**
     * บัตรจริง (checksum+ไม่หมดอายุ) แต่ยังไม่มีอะไรพิสูจน์ว่าเป็นบัตร "ของเขา"
     * → ห้ามป้ายเขียว (กันเอาบัตรจริงของคนอื่นมาสมัคร)
     */
    public function test_valid_card_alone_without_identity_proof_is_not_all_green(): void
    {
        $user = $this->makeUser();
        // ไม่มี payment method + ไม่มีสลิป → name checks = unknown ทั้งคู่
        $kyc = $this->makeKyc($user, [
            'id_card_number' => self::VALID_ID,
            'thai_first_name' => 'สมหญิง',
            'thai_last_name' => 'ใจดี',
            'expiry_date' => now()->addYears(5)->format('Y-m-d'),
        ]);

        $result = app(KycAutoCheckService::class)->run($kyc);

        $this->assertFalse(
            $result['all_green'],
            'checksum+วันหมดอายุพิสูจน์แค่ว่าบัตรจริง ไม่ใช่ว่าเป็นบัตรของเขา — ห้ามขึ้น "กดอนุมัติได้เลย"'
        );
    }

    /**
     * คำนำหน้าอังกฤษ MRS ต้องถูกตัดทั้งคำ (ไม่ใช่ตัดแค่ MR เหลือ S)
     */
    public function test_english_mrs_title_is_stripped_completely(): void
    {
        $this->assertSame('SOMYING JAIDEE', KycAutoCheckService::normalizeName('MRS. SOMYING JAIDEE'));
        $this->assertSame('SOMYING', KycAutoCheckService::normalizeName('MS. SOMYING'));
        $this->assertSame('สมหญิง ใจดี', KycAutoCheckService::normalizeName('นางสาว  สมหญิง ใจดี'));
    }

    public function test_all_green_when_everything_checks_out(): void
    {
        $user = $this->makeUser();
        $user->paymentMethods()->create([
            'type' => 'bank_transfer',
            'name' => 'บัญชีหลัก',
            'account_name' => 'สมหญิง ใจดี',
            'account_number' => '1234567890',
            'bank_name' => 'กสิกรไทย',
            'is_active' => true,
        ]);
        SlipVerificationLog::create([
            'platform' => 'facebook',
            'chat_user_id' => '5550001',
            'sent_to_slipok' => true,
            'decision' => 'accepted',
            'sender_name' => 'นาง สมหญิง ใจดี',
        ]);

        $kyc = $this->makeKyc($user, [
            'id_card_number' => self::VALID_ID,
            'thai_first_name' => 'สมหญิง',
            'thai_last_name' => 'ใจดี',
            'expiry_date' => now()->addYears(5)->format('Y-m-d'),
        ]);

        $result = app(KycAutoCheckService::class)->run($kyc);

        $this->assertTrue($result['all_green'], 'ครบทุกข้อต้องขึ้นป้ายเขียว "กดอนุมัติได้เลย"');
        $this->assertSame(4, $result['pass']);
    }
}
