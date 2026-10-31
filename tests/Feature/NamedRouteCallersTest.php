<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * ล็อกไม่ให้โค้ดเรียก route('ชื่อ') ที่ไม่มีอยู่จริง
 *
 * 🚨 ที่มา (พบ 2026-10-30): สแกนหลัง deploy เจอชื่อ route ที่ไม่มีจริงราว 85 ชื่อ
 *    แต่ละจุดคือ RouteNotFoundException = 500 ที่รอวันข้อมูลตรงเงื่อนไข เช่น
 *    สมัครผ่าน LINE เสร็จแล้วเด้ง 500 / แจ้งเตือนอนุมัติ KYC หายเงียบเพราะ observer จับ exception ไว้
 *    เรนเดอร์หน้าบน DB ว่างจับไม่ได้ เพราะกิ่งที่เรียก route ไม่ถูกรัน จึงตรวจจากข้อความในโค้ดแทน
 *
 * ไม่แตะ DB — เทียบ route('...') ใน app/ และ resources/views/ กับตาราง route ล้วนๆ
 */
class NamedRouteCallersTest extends TestCase
{
    /**
     * ชื่อที่ยังไม่มี route จริงและตั้งใจยังไม่แก้ (ห้ามเดา endpoint ของผู้ให้บริการจ่ายเงิน/การตั้งรหัสผ่าน)
     *
     * เพิ่มชื่อใหม่ลงที่นี่ไม่ได้ถ้าไม่มีเหตุผล — ให้แก้ผู้เรียกแทน
     *
     * @var array<string, string>
     */
    private const KNOWN_GAPS = [
        'payment.cancel' => 'PaySolutionsProvider: เกตเวย์ปิดบน prod ต้องสร้างหน้ารับผลก่อนเปิดใช้',
        'payment.success' => 'PaySolutionsProvider: เกตเวย์ปิดบน prod ต้องสร้างหน้ารับผลก่อนเปิดใช้',
        'payment.omise.callback' => 'OmiseProvider: เกตเวย์ปิดบน prod ต้องสร้างหน้ารับผลก่อนเปิดใช้',
        'payment.paypal.cancel' => 'PayPalProvider: เกตเวย์ปิดบน prod ต้องสร้างหน้ารับผลก่อนเปิดใช้',
        'payment.paypal.success' => 'PayPalProvider: เกตเวย์ปิดบน prod ต้องสร้างหน้ารับผลก่อนเปิดใช้',
        'payment.stripe.callback' => 'StripeProvider: เกตเวย์ปิดบน prod ต้องสร้างหน้ารับผลก่อนเปิดใช้',
        'payment.truemoney.callback' => 'TrueMoneyProvider: เกตเวย์ปิดบน prod ต้องสร้างหน้ารับผลก่อนเปิดใช้',
        'payment.truemoney.cancel' => 'TrueMoneyProvider: เกตเวย์ปิดบน prod ต้องสร้างหน้ารับผลก่อนเปิดใช้',
        'payment.truemoney.success' => 'TrueMoneyProvider: เกตเวย์ปิดบน prod ต้องสร้างหน้ารับผลก่อนเปิดใช้',
        'payment.process' => 'InstallmentController: ยังไม่มีช่องทางจ่ายค่างวด (installment_plans บน prod = 0)',
        'password.email' => 'auth/passwords/email ไม่มีใครเรนเดอร์: เว็บยังไม่มีระบบลืมรหัสผ่าน',
        'password.update' => 'auth/passwords/reset ไม่มีใครเรนเดอร์: เว็บยังไม่มีระบบลืมรหัสผ่าน',
    ];

    /**
     * ทุกชื่อที่เขียนตรงๆ ในโค้ดต้องมี route จริง ยกเว้นรายการที่รู้อยู่แล้วหรือมี Route::has() กันไว้
     */
    public function test_every_literal_route_call_points_at_a_defined_route(): void
    {
        $missing = [];

        foreach ($this->undefinedCalls() as $name => $files) {
            if (! array_key_exists($name, self::KNOWN_GAPS)) {
                $missing[] = $name.' @ '.implode(', ', $files);
            }
        }

        $this->assertSame([], $missing, "เรียก route ที่ไม่มีอยู่จริง:\n".implode("\n", $missing));
    }

    /**
     * รายการ KNOWN_GAPS ต้องไม่ค้าง: แก้เสร็จแล้วต้องเอาออก ไม่ปล่อยให้กลายเป็นช่องโหว่ของเทสต์นี้
     */
    public function test_known_gaps_list_has_no_stale_entries(): void
    {
        $stale = array_diff(array_keys(self::KNOWN_GAPS), array_keys($this->undefinedCalls()));

        $this->assertSame([], array_values($stale), 'เอาชื่อเหล่านี้ออกจาก KNOWN_GAPS ได้แล้ว');
    }

    /**
     * ชื่อที่ประกอบจากตัวแปรสแกนจากข้อความไม่ได้ จึงเช็คปลายทางของ wizard ตรงๆ
     */
    public function test_tpix_wizard_redirects_have_a_route_for_every_step(): void
    {
        foreach (range(1, 7) as $step) {
            $this->assertTrue(Route::has("admin.tpix.deployment.step{$step}"), "ไม่มี route ของ step {$step}");
        }
    }

    /**
     * หาชื่อ route ที่ถูกเรียกแต่ไม่มีอยู่จริง
     *
     * @return array<string, list<string>> ชื่อ => ไฟล์ที่เรียก
     */
    private function undefinedCalls(): array
    {
        $files = Finder::create()->files()->name('*.php')->in([app_path(), resource_path('views')]);
        $undefined = [];

        foreach ($files as $file) {
            $source = $this->withoutComments($file->getContents(), str_ends_with($file->getFilename(), '.blade.php'));

            foreach ($this->routeNamesIn($source) as $name) {
                if (Route::has($name) || $this->isGuarded($source, $name)) {
                    continue;
                }

                $undefined[$name][] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        return $undefined;
    }

    /**
     * ดึงชื่อ route จาก route(), to_route(), URL::route() และ redirect()->route()
     *
     * ข้าม $request->route('id') (อ่านพารามิเตอร์) และชื่อที่ต่อสตริงกับตัวแปร
     *
     * @return list<string>
     */
    private function routeNamesIn(string $source): array
    {
        $patterns = [
            '/(?<![\w$>:])(?:route|to_route)\(\s*[\'"]([^\'"$]+)[\'"]\s*[,)]/',
            '/(?:URL::|redirect\(\)\s*->|back\(\)\s*->|^\s*->)route\(\s*[\'"]([^\'"$]+)[\'"]\s*[,)]/m',
        ];

        $names = [];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $source, $matches);
            $names = array_merge($names, $matches[1]);
        }

        return array_values(array_unique($names));
    }

    /**
     * ไฟล์เดียวกันมี Route::has('ชื่อ') กันไว้หรือไม่
     */
    private function isGuarded(string $source, string $name): bool
    {
        return preg_match('/Route(?:Facade)?::has\(\s*[\'"]'.preg_quote($name, '/').'[\'"]/', $source) === 1;
    }

    /**
     * ตัด comment ออกก่อนสแกน ตัวอย่างใน comment ไม่ใช่การเรียกจริง
     */
    private function withoutComments(string $source, bool $isBlade): string
    {
        if ($isBlade) {
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source);

            return preg_replace('~^\s*(?://|\*).*$~m', '', $source);
        }

        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }
}
