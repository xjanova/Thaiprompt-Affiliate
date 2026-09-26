<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * ช่วงที่ deploy.sh ปิดเว็บซ่อม (STEP 3 `artisan down` → STEP 20 `artisan up` ~60-90 วิ)
 *
 * 🛠️ (2026-09-26) ก่อนหน้านี้ git clean ใน deploy.sh ลบ storage/framework/down ทิ้งทุกรอบ
 *    เว็บจึงไม่เคยปิดจริง (STEP 20 ขึ้น "Application is already up." ทุก deploy)
 *    พอแก้ให้ปิดจริง มี 3 อย่างที่ต้องยังทำงานระหว่างปิดซ่อม ไม่งั้นเสียของจริง:
 *      1. webhook/callback จากภายนอก — LINE ไม่ส่งซ้ำ, SMS เงินเข้า, payment gateway
 *         `artisan down` อ่านรายการยกเว้นจาก App\Http\Middleware\PreventRequestsDuringMaintenance
 *         (ชื่อคลาสนี้ hardcode อยู่ใน DownCommand ของ Laravel 11) แล้วฝังลงไฟล์ down
 *         ที่ public/index.php เช็คก่อนบูตแอป — ไม่มีคลาสนี้ = `"except": []` เงียบ ๆ
 *      2. task ที่ตั้งเวลาเป๊ะ (ดวงรายวัน dailyAt HH:00 ฯลฯ) — scheduler ข้าม task ที่ไม่ได้
 *         evenInMaintenanceMode() ⇒ deploy คร่อมเวลานั้น = โพสหายทั้งวัน
 *      3. แต่ migrate ต้อง "ไม่" รันระหว่างปิดซ่อม — ชนกับ migration ของ deploy.sh
 *
 * ไม่แตะ DB เลย (ไม่มี RefreshDatabase) — ห้ามเพิ่มเทสต์ที่ยิงเข้า controller จริงในคลาสนี้ ข้อมูลจะค้างข้ามเทสต์
 */
class DeployMaintenanceWindowTest extends TestCase
{
    /** path ขาเข้าจากระบบภายนอกที่ต้องรับได้ระหว่างปิดซ่อม */
    private const WEBHOOK_PATHS = [
        'webhook/*',
        'api/webhook/*',
        'api/webhooks/*',
        'api/v1/sms-payment/*',
        'api/v1/juntra/server/*',
        'payment/callback/*',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // storage/framework/ ไม่อยู่ใน git — checkout ใหม่บน CI ไม่มีโฟลเดอร์นี้ แล้ว `down` จะล้มเงียบ ๆ (คืน 1)
        @mkdir(storage_path('framework'), 0775, true);
    }

    protected function tearDown(): void
    {
        // กันไฟล์ค้างให้เทสต์อื่นเห็นว่าเว็บปิดอยู่
        @unlink(storage_path('framework/down'));
        @unlink(storage_path('framework/maintenance.php'));

        parent::tearDown();
    }

    public function test_the_app_maintenance_middleware_excludes_webhooks_and_replaces_the_framework_one(): void
    {
        $excluded = $this->app->make(PreventRequestsDuringMaintenance::class)->getExcludedPaths();
        foreach (self::WEBHOOK_PATHS as $path) {
            $this->assertContains($path, $excluded, "{$path} ต้องอยู่ในรายการยกเว้น maintenance");
        }

        // ฝั่ง HTTP ต้องใช้คลาสเดียวกับที่ `artisan down` อ่าน ไม่งั้น webhook ผ่านไฟล์ down มาแล้วโดน 503 ใน Laravel อีกชั้น
        $global = $this->app->make(HttpKernel::class)->getGlobalMiddleware();
        $this->assertContains(PreventRequestsDuringMaintenance::class, $global);
        $this->assertNotContains(\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class, $global);
    }

    public function test_artisan_down_bakes_the_webhook_exceptions_into_the_down_file(): void
    {
        $this->assertSame(0, Artisan::call('down'), 'artisan down ต้องสำเร็จ');

        $payload = json_decode((string) file_get_contents(storage_path('framework/down')), true);

        $this->assertIsArray($payload);
        foreach (self::WEBHOOK_PATHS as $path) {
            $this->assertContains($path, $payload['except'] ?? [], "ไฟล์ down ต้องมี {$path} — public/index.php ใช้รายการนี้ก่อนบูตแอป");
        }
    }

    public function test_pages_answer_503_but_webhooks_get_through_while_down(): void
    {
        $this->assertSame(0, Artisan::call('down'), 'artisan down ต้องสำเร็จ');

        // ⚠️ เรียก middleware ตรง ๆ ห้ามยิงเข้า controller จริง — เทสต์นี้ไม่มี RefreshDatabase
        //   handler ของ webhook เขียน DB (FortuneTellingSetting::getGlobalSettings() สร้างแถวเองถ้าตารางว่าง)
        //   แถวนั้นค้างข้ามเทสต์ ทำ FacebookOAuthPsidMatchTest พัง 8 ข้อบน CI (2026-09-26)
        $middleware = $this->app->make(PreventRequestsDuringMaintenance::class);
        $next = fn () => new Response('passed');

        foreach (['/webhook/line/fortune', '/webhook/facebook', '/api/webhook/line', '/api/webhook/bot/facebook',
            '/api/v1/sms-payment/notify', '/api/v1/juntra/server/bills', '/payment/callback/123'] as $uri) {
            $this->assertSame('passed', $middleware->handle(Request::create($uri, 'POST'), $next)->getContent(), "{$uri} ต้องผ่านระหว่างปิดซ่อม");
        }

        // หน้าเว็บโดน maintenance
        try {
            $middleware->handle(Request::create('/', 'GET'), $next);
            $this->fail('หน้าแรกต้องโดน 503 ระหว่างปิดซ่อม');
        } catch (HttpException $e) {
            $this->assertSame(503, $e->getStatusCode());
        }
    }

    public function test_the_down_files_are_git_ignored_so_the_deploy_stash_leaves_them_alone(): void
    {
        // deploy.sh STEP 4.1 `git stash push -u` กวาดไฟล์ untracked ที่ "ไม่ถูก ignore" ทั้งหมด
        // ถ้าหลุดจาก .gitignore เว็บจะกลับมาเปิดกลาง deploy (stash บน prod เคยมีทั้ง 2 ไฟล์จริง)
        $gitignore = preg_split('/\R/', (string) file_get_contents(base_path('.gitignore')));

        $this->assertContains('/storage/framework/down', $gitignore);
        $this->assertContains('/storage/framework/maintenance.php', $gitignore);
    }

    public function test_scheduled_tasks_keep_running_during_maintenance_except_migrate(): void
    {
        $events = $this->app->make(Schedule::class)->events();

        $this->assertNotEmpty($events, 'ต้องโหลด routes/console.php แล้ว');

        foreach ($events as $event) {
            $label = $event->description ?: (string) $event->command;

            if (str_contains((string) $event->command, ' migrate')) {
                $this->assertFalse($event->runsInMaintenanceMode(), "{$label}: migrate ห้ามรันชนกับ deploy");
            } else {
                $this->assertTrue($event->runsInMaintenanceMode(), "{$label}: ต้องรันระหว่าง deploy ปิดเว็บ ไม่งั้นงานตั้งเวลาหาย");
            }
        }
    }
}
