<?php

namespace Tests\Feature\Platform;

use App\Support\WebSessionRedirect;
use Tests\TestCase;

/**
 * กติกาปลายทางของ "เปิดเว็บแบบล็อกอินจากแอป" (PLAY-16 / SHOP-15)
 *
 * เดิม redirect_path รับอะไรก็ได้ → ผู้โจมตีออก token บัญชีตัวเองพร้อม https://evil.example
 * แล้วส่งลิงก์ main.thaiprompt.online ให้เหยื่อ (open redirect + login CSRF)
 *
 * ไม่ใช้ DB — ทดสอบตรรกะ WebSessionRedirect ล้วนๆ (รันบนเครื่อง dev ได้)
 */
class WebSessionRedirectRulesTest extends TestCase
{
    /**
     * ปลายทางภายนอก / เลี่ยงกติกา ต้องถูกปฏิเสธทั้งหมด
     */
    public function test_external_and_tricky_paths_are_rejected(): void
    {
        $bad = [
            'https://evil.example',
            'http://evil.example/user',
            '//evil.example',
            '//evil.example/user',
            '/\\evil.example',
            '\\\\evil.example',
            '/%2F%2Fevil.example',
            '/%5Cevil.example',
            'javascript:alert(1)',
            'user/wallet',                 // ไม่ขึ้นต้นด้วย /
            '/admin',                      // ไม่อยู่ใน prefix ที่อนุญาต
            '/admin/users',
            '/login',
            '/username',                   // /user ต้องเป็น segment เต็ม
            '/user/../admin',
            '/user/%2E%2E/admin',
            '/user/./wallet',
            "/user\nLocation: https://evil",
            "/user\t/wallet",              // อักขระควบคุมกลาง path (ที่หัว/ท้ายถูก trim ทิ้งได้)
            '/seller/https://evil.example',
            '',
            '   ',
            '/'.str_repeat('a', 300),
        ];

        foreach ($bad as $path) {
            $this->assertNull(
                WebSessionRedirect::sanitizePath($path),
                'ต้องปฏิเสธ: '.json_encode($path)
            );
        }

        $this->assertNull(WebSessionRedirect::sanitizePath(null));
    }

    /**
     * path ภายในใต้ prefix ที่อนุญาตต้องผ่าน (และตัด query/fragment ออก)
     */
    public function test_internal_allowed_paths_pass(): void
    {
        $cases = [
            '/user' => '/user',
            '/user/' => '/user',
            '/user/wallet/topup' => '/user/wallet/topup',
            '/seller/dashboard' => '/seller/dashboard',
            '/taladsod' => '/taladsod',
            '/taladsod/shop/12' => '/taladsod/shop/12',
            '/shop/some-product' => '/shop/some-product',
            '/storefront' => '/storefront',
            '/wallet' => '/wallet',
            '/account/delete' => '/account/delete',
            '/user/wallet?amount=100' => '/user/wallet',
            '/user/wallet#top' => '/user/wallet',
            '  /user/profile  ' => '/user/profile',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, WebSessionRedirect::sanitizePath($input), 'input: '.$input);
        }
    }

    /**
     * ค่าเริ่มต้นต้องผ่านกติกาเอง (ไม่งั้นแอปที่ไม่ส่ง redirect_path จะพัง)
     */
    public function test_default_path_is_allowed(): void
    {
        $this->assertSame(WebSessionRedirect::DEFAULT_PATH, WebSessionRedirect::sanitizePath(WebSessionRedirect::DEFAULT_PATH));
    }

    /**
     * query params: เก็บเฉพาะคีย์/ค่าที่ปลอดภัย และจำกัดจำนวน
     */
    public function test_query_params_are_filtered(): void
    {
        $clean = WebSessionRedirect::sanitizeQuery([
            'amount' => 1000,
            'tab' => 'topup',
            'flag' => true,
            'bad key' => 'x',
            'redirect' => ['nested' => 'https://evil'],
            'empty' => '',
            'long' => str_repeat('a', 500),
            'crlf' => "a\r\nb",
        ]);

        $this->assertSame(['amount' => '1000', 'tab' => 'topup', 'flag' => '1'], $clean);

        $this->assertSame([], WebSessionRedirect::sanitizeQuery('amount=1'));
        $this->assertSame([], WebSessionRedirect::sanitizeQuery(null));

        $many = [];
        for ($i = 0; $i < 30; $i++) {
            $many['k'.$i] = 'v';
        }
        $this->assertCount(10, WebSessionRedirect::sanitizeQuery($many));
    }

    public function test_build_target_and_mask_email(): void
    {
        $this->assertSame('/user/wallet', WebSessionRedirect::buildTarget('/user/wallet'));
        $this->assertSame('/user/wallet?amount=100', WebSessionRedirect::buildTarget('/user/wallet', ['amount' => '100']));

        $this->assertSame('so*****@gmail.com', WebSessionRedirect::maskEmail('somchai@gmail.com'));
        $this->assertSame('a***@x.co', WebSessionRedirect::maskEmail('a@x.co'));
        $this->assertSame('', WebSessionRedirect::maskEmail(null));
        $this->assertSame('', WebSessionRedirect::maskEmail('not-an-email'));
    }
}
