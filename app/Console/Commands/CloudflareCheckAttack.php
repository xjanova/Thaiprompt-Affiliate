<?php

namespace App\Console\Commands;

use App\Services\CloudflareService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CloudflareCheckAttack Command
 *
 * ตรวจสอบสถานะการโจมตีและเปิด/ปิด Under Attack Mode อัตโนมัติ
 *
 * Usage: php artisan cloudflare:check-attack
 *
 * ตั้งค่า cron job เพื่อให้ทำงานทุกนาที:
 * * * * * * cd /path/to/project && php artisan cloudflare:check-attack >> /dev/null 2>&1
 */
class CloudflareCheckAttack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudflare:check-attack
                            {--dry-run : ทดสอบโดยไม่เปลี่ยนแปลงจริง}
                            {--force-enable : บังคับเปิด Under Attack Mode}
                            {--force-disable : บังคับปิด Under Attack Mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ตรวจสอบสถานะการโจมตีและเปิด/ปิด Under Attack Mode อัตโนมัติ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cloudflare = new CloudflareService();

        // ตรวจสอบว่า Cloudflare ได้ตั้งค่าหรือยัง
        if (!$cloudflare->isConfigured()) {
            $this->error('Cloudflare ยังไม่ได้ตั้งค่า Zone ID หรือ API Token');
            return 1;
        }

        // Force enable/disable
        if ($this->option('force-enable')) {
            $this->info('บังคับเปิด Under Attack Mode...');

            if ($this->option('dry-run')) {
                $this->warn('[DRY-RUN] จะเปิด Under Attack Mode');
                return 0;
            }

            $result = $cloudflare->autoEnableUnderAttackMode('Force enabled via CLI');

            if ($result['success']) {
                $this->info('เปิด Under Attack Mode สำเร็จ');
                return 0;
            } else {
                $this->error('ไม่สามารถเปิด Under Attack Mode: ' . ($result['message'] ?? 'Unknown error'));
                return 1;
            }
        }

        if ($this->option('force-disable')) {
            $this->info('บังคับปิด Under Attack Mode...');

            if ($this->option('dry-run')) {
                $this->warn('[DRY-RUN] จะปิด Under Attack Mode');
                return 0;
            }

            $result = $cloudflare->disableUnderAttackMode();

            if ($result['success']) {
                // ล้าง auto-enabled flag
                \App\Models\Setting::set('cloudflare_under_attack_auto_enabled', false, 'boolean', 'cloudflare');
                $this->info('ปิด Under Attack Mode สำเร็จ');
                return 0;
            } else {
                $this->error('ไม่สามารถปิด Under Attack Mode: ' . ($result['message'] ?? 'Unknown error'));
                return 1;
            }
        }

        // ตรวจสอบการตั้งค่า Auto Mode
        $settings = $cloudflare->getAutoUnderAttackSettings();

        if (!$settings['enabled']) {
            $this->line('Auto Under Attack Mode ถูกปิดใช้งาน');
            return 0;
        }

        $this->info('กำลังตรวจสอบสถานะการโจมตี...');

        // ตรวจสอบสถานะปัจจุบัน
        $status = $cloudflare->getUnderAttackStatus();
        $checkResult = $cloudflare->checkAutoUnderAttackStatus();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Failed Logins', $checkResult['metrics']['failed_logins'] ?? 0],
                ['Rate Limit Exceeded', $checkResult['metrics']['rate_limit_exceeded'] ?? 0],
                ['Turnstile Failures', $checkResult['metrics']['turnstile_failures'] ?? 0],
                ['Unique Attack IPs', $checkResult['metrics']['unique_attack_ips'] ?? 0],
                ['Threat Score', $checkResult['threat_score'] ?? 0],
                ['Threshold', $checkResult['threshold'] ?? 0],
                ['Time Window', ($checkResult['time_window'] ?? 0) . ' นาที'],
            ]
        );

        $this->line('');
        $this->line('สถานะปัจจุบัน: ' . ($status['is_under_attack'] ? 'Under Attack Mode เปิดอยู่' : 'ปกติ'));
        $this->line('Security Level: ' . ($status['current_security_level'] ?? 'unknown'));
        $this->line('');

        // ถ้าตรวจพบการโจมตีและยังไม่ได้เปิด Under Attack Mode
        if ($checkResult['should_activate'] && !$status['is_under_attack']) {
            $this->warn('ตรวจพบการโจมตี! ' . $checkResult['reason']);

            if ($this->option('dry-run')) {
                $this->warn('[DRY-RUN] จะเปิด Under Attack Mode');
                return 0;
            }

            $result = $cloudflare->autoEnableUnderAttackMode($checkResult['reason']);

            if ($result['success']) {
                $this->info('เปิด Under Attack Mode อัตโนมัติสำเร็จ');
                Log::critical('CloudflareCheckAttack: เปิด Under Attack Mode อัตโนมัติ', [
                    'reason' => $checkResult['reason'],
                    'threat_score' => $checkResult['threat_score'],
                    'metrics' => $checkResult['metrics'],
                ]);
            } else {
                $this->error('ไม่สามารถเปิด Under Attack Mode: ' . ($result['message'] ?? 'Unknown error'));
                return 1;
            }

            return 0;
        }

        // ถ้าเปิด Under Attack Mode อัตโนมัติอยู่ ตรวจสอบว่าควรปิดหรือยัง
        if ($status['auto_enabled'] && $status['is_under_attack']) {
            $this->line('ตรวจสอบว่าควรปิด Under Attack Mode อัตโนมัติหรือยัง...');

            if ($this->option('dry-run')) {
                $result = $cloudflare->autoDisableUnderAttackMode();
                if ($result['success']) {
                    $this->warn('[DRY-RUN] จะปิด Under Attack Mode');
                } else {
                    $this->line('[DRY-RUN] ' . ($result['message'] ?? 'ยังไม่ถึงเวลาปิด'));
                }
                return 0;
            }

            $result = $cloudflare->autoDisableUnderAttackMode();

            if ($result['success']) {
                $this->info('ปิด Under Attack Mode อัตโนมัติสำเร็จ');
                Log::info('CloudflareCheckAttack: ปิด Under Attack Mode อัตโนมัติ');
            } else {
                $this->line($result['message'] ?? 'ยังไม่ถึงเวลาปิด');
            }

            return 0;
        }

        $this->info('ไม่พบการโจมตีที่ต้องตอบสนอง');
        return 0;
    }
}
