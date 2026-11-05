<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * เปลือกหลังบ้าน (theme-v4) ต้องเบา — กันไม่ให้ sidebar บวมกลับไปเป็นหลายร้อย KB
 *
 * เดิม sidebar แอดมินหนัก 423KB จาก 519KB ทั้งหน้า (201 แถว × ~1.9KB:
 * inline style + @js ข้อมูลปักหมุด + binding Alpine ต่อแถว) ทำให้หลังบ้านหน่วง
 */
class AdminShellPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'แอดมินวัดความเร็ว']);
    }

    /**
     * ตัด <aside> ของ sidebar ออกมาจาก HTML ทั้งหน้า
     *
     * @param  string  $html  HTML ทั้งหน้า
     * @return string HTML เฉพาะ sidebar
     */
    private function sidebarHtml(string $html): string
    {
        $start = strpos($html, '<aside');
        $end = strpos($html, '</aside>', (int) $start);
        $this->assertNotFalse($start, 'ไม่พบ <aside> ของ sidebar');
        $this->assertNotFalse($end, 'ไม่พบ </aside> ของ sidebar');

        return substr($html, $start, $end - $start + 8);
    }

    /**
     * รายงานขนาดหน้าออก STDERR — ใช้เทียบก่อน/หลังแก้
     */
    private function report(string $label, string $html, string $aside, float $ms): void
    {
        fwrite(STDERR, sprintf(
            "\n[admin-shell] %s: page=%.1fKB sidebar=%.1fKB rows=%d elements=%d alpine_bindings=%d inline_styles=%d server_ms_median=%.0f\n",
            $label,
            strlen($html) / 1024,
            strlen($aside) / 1024,
            substr_count($aside, 'tp-mrow'),
            preg_match_all('/<[a-z]/i', $aside),
            preg_match_all('/\s(?:@[a-z]|:[a-z]|x-[a-z])[\w.:-]*="/i', $aside),
            substr_count($aside, 'style="'),
            $ms,
        ));
    }

    /**
     * sidebar ต้องมีเมนูครบทุกตัวจาก config/menus.php และขนาดต้องไม่บวม
     */
    public function test_admin_sidebar_renders_every_menu_and_stays_small(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertOk();

        // เวลาฝั่งเซิร์ฟเวอร์ (median 5 รอบ หลังอุ่นเครื่องแล้ว) — ไว้เทียบก่อน/หลัง
        $times = [];
        for ($i = 0; $i < 5; $i++) {
            $t = hrtime(true);
            $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk();
            $times[] = (hrtime(true) - $t) / 1e6;
        }
        sort($times);

        $html = $response->getContent();
        $aside = $this->sidebarHtml($html);
        $this->report('dashboard', $html, $aside, $times[2]);

        // เมนูทุกตัวที่ MenuService ส่งมาต้องอยู่ใน sidebar (ไม่หายจากการลดขนาด)
        $menus = app(MenuService::class)->getMenuForRole('admin', $this->admin);
        $expected = 0;
        foreach ($menus as $group) {
            foreach (($group['submenu'] ?? []) as $sub) {
                $url = is_array($sub) ? ($sub['url'] ?? null) : null;
                $label = is_array($sub) ? ($sub['label'] ?? '') : '';
                if (! $url || $label === '' || $label === '---') {
                    continue;
                }
                $expected++;
                $this->assertStringContainsString('href="'.e($url).'"', $aside, "เมนู {$label} หายจาก sidebar");
            }
        }
        $this->assertGreaterThan(50, $expected, 'เมนูแอดมินน้อยผิดปกติ — config/menus.php โหลดไม่ขึ้น?');

        // ปุ่มปักหมุดใช้ data-attribute + handler ตัวเดียว ไม่ใช่ binding Alpine ต่อแถว
        $this->assertStringNotContainsString('$store.pinnedMenus.isPinned(', $aside);
        $this->assertStringContainsString('data-pin>', $aside);
        $this->assertStringNotContainsString('JSON.parse(', $aside);

        // งบขนาด: เดิม ~423KB บน prod — ต้องเหลือไม่ถึงครึ่ง
        $this->assertLessThan(160 * 1024, strlen($aside), 'sidebar แอดมินบวมเกินงบ 160KB');
    }

    /**
     * สถิติผู้เข้าชมบน dashboard ต้องมาจากแคช — ไม่ยิง COUNT ตาราง page_views ทุกครั้งที่เปิดหน้า
     * (นับเฉพาะ SELECT — middleware TrackPageView ยัง INSERT แถวการเข้าชมตามปกติ)
     */
    public function test_dashboard_page_view_stats_are_cached(): void
    {
        Cache::flush();

        $selects = 0;
        DB::listen(function ($q) use (&$selects) {
            if (str_contains($q->sql, 'page_views') && str_starts_with(strtolower(ltrim($q->sql)), 'select')) {
                $selects++;
            }
        });

        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk();
        $first = $selects;
        $this->assertGreaterThan(0, $first, 'รอบแรกต้องคำนวณสถิติจาก page_views');

        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk();
        $second = $selects - $first;
        $this->assertSame(0, $second, "รอบสองยังยิง SELECT page_views {$second} ครั้ง (รอบแรก {$first}) — แคชไม่ทำงาน");
    }
}
