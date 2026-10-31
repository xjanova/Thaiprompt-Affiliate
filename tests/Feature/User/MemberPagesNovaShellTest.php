<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * หน้าสมาชิกทุกหน้า (layouts.user-v4) วิ่งในเปลือกโนวาผ่านโหมดสะพาน
 *
 * ไล่เปิดทุก GET route ฝั่งสมาชิกที่ไม่มีพารามิเตอร์ ด้วยสมาชิกปกติหนึ่งคน:
 *  - ต้องไม่มีหน้าไหนล่ม (5xx)
 *  - หน้าที่ render เลย์เอาต์ V4 ต้องได้ <html class="nvm-v4"> + CSS สะพาน (ไม่หลุดกลับไปเปลือกเก่า)
 */
class MemberPagesNovaShellTest extends TestCase
{
    use RefreshDatabase;

    /** path ที่ไม่ใช่หน้าเว็บ (ดาวน์โหลด/ออกจากระบบ/JSON) — เปิดในเทสต์นี้ไม่มีความหมาย */
    private const SKIP = ['export', 'download', 'logout', 'callback', 'webhook', 'stream', 'pdf', 'print', 'json', 'ajax', 'poll', 'check-', 'status-check'];

    /**
     * หน้าที่ล่มอยู่แล้วก่อนเปลี่ยนเปลือก (เปิดด้วยเปลือก V4 เดิมก็ 500 ชุดเดียวกันเป๊ะ — วัด 29 ต.ค. 2026)
     * สาเหตุเป็นของหน้าเอง: view/method/route ไม่มีจริง, @show ใน x-on ของ Alpine, ตารางไม่มีใน schema,
     * สมาชิกที่ยังไม่มี MLM member → อ่าน property บน null
     * ⚠️ แก้หน้าไหนแล้วให้ลบออกจากรายการนี้ — เทสต์จะเฝ้าไม่ให้พังซ้ำ
     */
    private const KNOWN_BROKEN = [
        'user/coupons', 'user/coupons/available', 'user/coupons/history',
        'user/dex/add-liquidity', 'user/dex/pools', 'user/dex/swap-history',
        'user/interactions/following-stores', 'user/interactions/recently-viewed', 'user/interactions/wishlist',
        'user/marketing/recruit', 'user/marketing/recruit/analytics',
        'user/premium-stores', 'user/ranks/dashboard', 'user/ranks/widget-data',
        'user/star-upgrade', 'user/store-rating/history', 'user/team-transfer/approvals/list',
        'user/tokens/my/balances', 'user/tokens/my/portfolio', 'user/tokens/my/tokens', 'user/tokens/referrals/my-codes',
        'user/tpix/deposit', 'user/tpix/send', 'user/tpix/transactions', 'user/tpix/wallet', 'user/tpix/withdrawal',
    ];

    public function test_every_member_page_renders_in_the_nova_shell(): void
    {
        Http::fake();

        $user = User::factory()->create(['role' => 'user', 'kyc_status' => 'approved']);
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => 500]);

        $crashed = [];
        $oldShell = [];
        $novaPages = 0;

        foreach ($this->memberPageUris() as $uri) {
            $res = $this->actingAs($user)->get('/'.$uri);
            $status = $res->getStatusCode();

            if ($status >= 500) {
                $crashed[] = $uri.' → '.$status.' '.Str::limit((string) optional($res->exception)->getMessage(), 180);

                continue;
            }

            $html = $status === 200 ? (string) $res->getContent() : '';
            if (! str_contains($html, '<html')) {
                continue;
            }
            if (str_contains($html, 'class="nvm-v4') || str_contains($html, 'nvm-body')) {
                $novaPages++;
            }
            if (str_contains($html, 'class="tp-root"')) {
                $oldShell[] = $uri;
            }
        }

        $this->assertSame([], $crashed, "หน้าสมาชิกที่ล่ม:\n".implode("\n", $crashed));
        $this->assertSame([], $oldShell, "หน้าที่ยังได้เปลือก V4 เก่า:\n".implode("\n", $oldShell));
        $this->assertGreaterThan(40, $novaPages, 'เปิดหน้าในเปลือกโนวาได้น้อยผิดปกติ — route ฝั่งสมาชิกหายหรือเด้งหมด?');
    }

    /**
     * GET route ฝั่งสมาชิกที่ไม่มีพารามิเตอร์ (user/*, ตลาดสด, คำสั่งซื้อร้านค้า)
     *
     * @return list<string>
     */
    private function memberPageUris(): array
    {
        $uris = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! in_array('GET', $route->methods(), true) || str_contains($uri, '{')) {
                continue;
            }
            if (! Str::startsWith($uri, ['user/', 'taladsod', 'shop/orders'])) {
                continue;
            }
            if (Str::contains($uri, self::SKIP) || in_array($uri, self::KNOWN_BROKEN, true)) {
                continue;
            }
            $uris[$uri] = true;
        }

        return array_keys($uris);
    }
}
