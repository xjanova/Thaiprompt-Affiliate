<?php

namespace Tests\Feature;

use App\Console\AutoMigrateSchedule;
use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * ล็อกว่าแอปไม่รัน migrate เองตอนบูต และ auto-migrate ปิดเป็นค่าเริ่มต้น
 *
 * 🚨 (2026-09-26) เดิม AppServiceProvider::boot() เรียก Artisan::call('migrate --force') วันละครั้ง
 *    ในทุกโปรเซสที่บูตแอป — deploy.sh ลบไฟล์ธงทุกรอบ ทำให้ artisan ตัวแรกของ deploy
 *    รัน migrate ก่อน composer install และก่อน migrate:smart (ตัวที่ล้มเหลือแค่ log warning)
 *    ส่วนใน php-fpm โดน timeout ตัดกลางทางได้ = schema ครึ่งๆ กลางๆ ไม่มี log
 *
 * ไม่ใช้ DB — Artisan::spy() กันไม่ให้คำสั่งรันจริง แค่บันทึกว่ามีการเรียก
 */
class AutoMigrateScheduleTest extends TestCase
{
    /**
     * บูต AppServiceProvider แล้วต้องไม่มีการเรียก migrate ใดๆ เลย
     * (ลบไฟล์ธงรายวันของโค้ดเดิมก่อน — ถ้าโค้ดเดิมกลับมา เทสต์นี้จะแดง)
     */
    public function test_booting_the_app_never_calls_migrate(): void
    {
        config(['app.auto_migrate' => false]);
        @unlink(storage_path('framework/cache/migration_check.txt'));

        Artisan::spy();

        (new AppServiceProvider($this->app))->boot();

        Artisan::shouldNotHaveReceived('call', function ($command) {
            return str_contains((string) $command, 'migrate');
        });
    }

    /**
     * เปิด flag ก็ต้องไม่รันตอนบูต — flag มีผลแค่กับ scheduler เท่านั้น
     */
    public function test_booting_the_app_never_calls_migrate_even_when_enabled(): void
    {
        config(['app.auto_migrate' => true]);
        @unlink(storage_path('framework/cache/migration_check.txt'));

        Artisan::spy();

        (new AppServiceProvider($this->app))->boot();

        Artisan::shouldNotHaveReceived('call', function ($command) {
            return str_contains((string) $command, 'migrate');
        });
    }

    /**
     * ค่าเริ่มต้นต้องปิด (prod ไม่ตั้ง APP_AUTO_MIGRATE → deploy.sh เป็นตัวรันตัวเดียว)
     */
    public function test_auto_migrate_is_off_by_default(): void
    {
        $this->assertFalse(config('app.auto_migrate'));
    }

    /**
     * ปิดอยู่ = ไม่ลงทะเบียนงาน migrate ใน schedule เลย
     */
    public function test_disabled_registers_nothing(): void
    {
        config(['app.auto_migrate' => false]);
        $schedule = new Schedule;

        $this->assertNull(AutoMigrateSchedule::register($schedule));
        $this->assertCount(0, $schedule->events());
    }

    /**
     * เปิดแล้ว = migrate --force วันละครั้ง เป็นโปรเซสแยก ไม่ซ้อนกัน
     */
    public function test_enabled_schedules_daily_migrate_in_its_own_process(): void
    {
        config(['app.auto_migrate' => true]);
        $schedule = new Schedule;

        $event = AutoMigrateSchedule::register($schedule);

        $this->assertNotNull($event);
        $this->assertCount(1, $schedule->events());
        $this->assertStringContainsString('migrate --force', $event->command);
        $this->assertSame('10 4 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->onOneServer);
        $this->assertTrue($event->runInBackground);
        $this->assertSame(AutoMigrateSchedule::NAME, $event->description);
        $this->assertSame(storage_path('logs/auto-migrate.log'), $event->output);
    }
}
