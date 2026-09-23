<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

/**
 * ไฟล์ใน config/ ต้องไม่มีคีย์ลับฝังเป็นค่าตายตัว
 *
 * 🔐 (2026-09-23) config/services.php เคยมี env('CLOUDFLARE_API_TOKEN', '<Global API Key 37 ตัว>')
 *    ฝังไว้ตั้งแต่ 2025-12-01 — repo นี้เป็น public และ scripts/deploy-to-distribution.sh
 *    ก็ส่งโฟลเดอร์ config/ ไปให้ลูกค้าด้วย ⇒ คีย์หลุดทั้งอินเทอร์เน็ต ต้องหมุนทิ้ง
 *
 * ความลับต้องอยู่ใน .env (หรือหน้าแอดมิน) เท่านั้น ค่า default ใน config ใช้ได้แค่ '' / URL / ค่าทั่วไป
 *
 * ตรวจสตริงทุกตัวในไฟล์ config (ไม่ใช่แค่ค่า default ของ env()) — ไม่บูตแอป ไม่แตะ DB
 */
class ConfigHasNoHardcodedSecretsTest extends TestCase
{
    public function test_config_files_contain_no_secret_looking_literals(): void
    {
        $offenders = [];

        foreach (glob($this->configPath().'/*.php') ?: [] as $file) {
            foreach (token_get_all((string) file_get_contents($file)) as $token) {
                if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    continue;
                }

                $literal = substr($token[1], 1, -1);
                if ($reason = $this->secretReason($literal)) {
                    // โชว์แค่หัว 6 ตัว — ห้ามพิมพ์คีย์เต็มลง log ของ CI
                    $offenders[] = basename($file).':'.$token[2].' ('.$reason.', '.substr($literal, 0, 6).'…)';
                }
            }
        }

        $this->assertSame([], $offenders,
            "พบค่าที่หน้าตาเป็นคีย์ลับฝังใน config/ — ย้ายไปไว้ใน .env แล้วให้ default เป็น ''\n"
            ."ถ้าคีย์นั้นเป็นของจริง ต้องหมุนคีย์ทิ้งด้วย (repo นี้ public):\n".implode("\n", $offenders)
        );
    }

    /**
     * ตัวตรวจต้องจับของจริงได้ — กันเทสต์ผ่านเพราะ pattern พังจนไม่เจออะไรเลย
     * (ใช้ค่าปลอมที่สร้างขึ้น ไม่ใช่คีย์ที่เคยหลุด)
     */
    public function test_detector_catches_known_secret_shapes_and_ignores_normal_values(): void
    {
        $fakeHexKey = str_repeat('a1b2c3', 6).'d';        // 37 ตัว = ทรงเดียวกับ Cloudflare Global API Key
        $fakeApiToken = str_repeat('Ab3_x', 8);             // 40 ตัว = ทรง Cloudflare API Token
        $fakeOpenAi = 'sk-'.str_repeat('Z9y', 10);

        $fakeEvmPrivateKey = '0x'.str_repeat('4f', 32);  // 0x + 64 hex

        foreach ([$fakeHexKey, $fakeApiToken, $fakeOpenAi, $fakeEvmPrivateKey, 'AIza'.str_repeat('Q', 35), 'ghp_'.str_repeat('x1', 18)] as $secret) {
            $this->assertNotNull($this->secretReason($secret), "ตัวตรวจจับไม่ได้: {$secret}");
        }

        foreach ([
            '', 'https://api.paysolutions.asia', 'claude-haiku-4-5-20251001', 'gemini-2.5-flash-preview-tts',
            '0x0000000000000000000000000000000000000000', 'tp_affiliate_database_', '/usr/sbin/sendmail -bs -i',
            'ร้านค้าทางการของระบบ สินค้าคุณภาพสูง รับประกันแท้ 100%', 'App\\Providers\\AppServiceProvider',
        ] as $normal) {
            $this->assertNull($this->secretReason($normal), "ตัวตรวจจับผิด (false positive): {$normal}");
        }
    }

    /**
     * คืนเหตุผลถ้าสตริงหน้าตาเป็นคีย์ลับ · null = ไม่ใช่
     */
    private function secretReason(string $value): ?string
    {
        // address ของ EVM (0x + 40 hex) เป็นข้อมูลสาธารณะ ไม่ใช่ความลับ
        // (private key = 0x + 64 hex ยังโดนกฎสตริงสุ่มยาวข้างล่าง)
        if (preg_match('/^0x[0-9a-fA-F]{40}$/', $value)) {
            return null;
        }

        if (preg_match('/^[A-Fa-f0-9]{32,}$/', $value)) {
            return 'hex ยาว '.strlen($value).' ตัว';
        }

        if (preg_match('/^(sk-|sk_live_|sk_test_|rk_live_|pk_live_|whsec_|AIza|ghp_|gho_|ghs_|ghu_|github_pat_|glpat-|xox[abprs]-|EAA[A-Za-z0-9]{20})/', $value)) {
            return 'ขึ้นต้นแบบคีย์ของผู้ให้บริการ';
        }

        // สตริงสุ่มยาว (API token ทั่วไป) — ต้องมีทั้งตัวอักษรและตัวเลข ไม่มีจุด/ทับ/ช่องว่าง (ตัด URL, path, ข้อความ)
        if (strlen($value) >= 40 && preg_match('/^[A-Za-z0-9_-]+$/', $value)
            && preg_match('/[A-Za-z]/', $value) && preg_match('/\d/', $value)) {
            return 'สตริงสุ่มยาว '.strlen($value).' ตัว';
        }

        return null;
    }

    private function configPath(): string
    {
        return dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'config';
    }
}
