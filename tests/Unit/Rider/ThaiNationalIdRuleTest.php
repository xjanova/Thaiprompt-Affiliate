<?php

namespace Tests\Unit\Rider;

use App\Rules\ThaiNationalId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ตรวจเลขบัตรประชาชนไทย 13 หลัก + checksum (ใช้ตอนสมัครไรเดอร์ทั้งแอปและเว็บ — audit RIDER-27)
 *
 * ฟังก์ชันล้วน ไม่แตะฐานข้อมูล → รันบนเครื่อง dev ได้
 */
class ThaiNationalIdRuleTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function validIds(): array
    {
        return [
            'เลขปกติ' => ['1101700230708'],
            'ขึ้นต้น 3' => ['3100200345676'],
            'checksum = 1' => ['1234567890121'],
            'มีขีดคั่น' => ['3-7005-00123-45-2'],
            'มีเว้นวรรค' => ['1 1017 00230 70 8'],
        ];
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidIds(): array
    {
        return [
            'checksum ผิด' => ['1101700230707'],
            '12 หลัก' => ['110170023070'],
            '14 หลัก' => ['11017002307080'],
            'ขึ้นต้นด้วย 0' => ['0101700230708'],
            'เลขซ้ำทั้งหมด' => ['1111111111111'],
            'มีตัวอักษร' => ['11017OO230708'],
            'ค่าว่าง' => [''],
            'null' => [null],
        ];
    }

    #[DataProvider('validIds')]
    public function test_valid_ids_pass(string $id): void
    {
        $this->assertTrue(ThaiNationalId::isValid($id));
        $this->assertSame([], $this->runRule($id));
    }

    #[DataProvider('invalidIds')]
    public function test_invalid_ids_fail_with_thai_message(mixed $id): void
    {
        $this->assertFalse(ThaiNationalId::isValid($id));

        $errors = $this->runRule($id);
        $this->assertCount(1, $errors);
        $this->assertMatchesRegularExpression('/เลขบัตรประชาชน/u', $errors[0]);
    }

    public function test_normalize_strips_non_digits(): void
    {
        $this->assertSame('1101700230708', ThaiNationalId::normalize(' 1-1017-00230-70-8 '));
        $this->assertSame('', ThaiNationalId::normalize(null));
    }

    /**
     * รัน rule แล้วคืนข้อความ error ทั้งหมด
     *
     * @return array<int, string>
     */
    private function runRule(mixed $value): array
    {
        $errors = [];
        (new ThaiNationalId)->validate('id_card_number', $value, function (string $message) use (&$errors) {
            $errors[] = $message;
        });

        return $errors;
    }
}
