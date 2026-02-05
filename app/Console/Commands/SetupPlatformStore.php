<?php

namespace App\Console\Commands;

use App\Models\SmsCheckerDevice;
use App\Models\VendorStore;
use Illuminate\Console\Command;

/**
 * Setup Platform Store และอัพเดท SMS Checker Devices
 *
 * คำสั่งนี้จะ:
 * 1. สร้าง Platform Store ถ้ายังไม่มี
 * 2. อัพเดท SMS Checker Devices ที่ไม่มี store_id ให้ใช้ Platform Store
 *
 * Usage: php artisan smschecker:setup-platform-store
 */
class SetupPlatformStore extends Command
{
    protected $signature = 'smschecker:setup-platform-store
                            {--force : Force update all devices without store_id}';

    protected $description = 'Setup Platform Store and update SMS Checker devices';

    public function handle(): int
    {
        $this->info('Setting up Platform Store...');
        $this->newLine();

        // 1. สร้าง/ดึง Platform Store
        $platformStore = VendorStore::getPlatformStore();

        $this->info("Platform Store:");
        $this->table(
            ['ID', 'Name', 'Slug', 'Status'],
            [[$platformStore->id, $platformStore->store_name, $platformStore->store_slug, $platformStore->status ?? 'active']]
        );
        $this->newLine();

        // 2. ดึง devices ที่ไม่มี store_id
        $devicesWithoutStore = SmsCheckerDevice::whereNull('store_id')->get();

        if ($devicesWithoutStore->isEmpty()) {
            $this->info('All devices already have store_id assigned.');
            return Command::SUCCESS;
        }

        $this->warn("Found {$devicesWithoutStore->count()} device(s) without store_id:");
        $this->table(
            ['ID', 'Device ID', 'Device Name', 'Status'],
            $devicesWithoutStore->map(fn($d) => [$d->id, $d->device_id, $d->device_name, $d->status])
        );
        $this->newLine();

        // 3. ถาม confirm
        if (!$this->option('force')) {
            if (!$this->confirm("Do you want to assign these devices to Platform Store (ID: {$platformStore->id})?")) {
                $this->warn('Aborted.');
                return Command::FAILURE;
            }
        }

        // 4. อัพเดท devices
        $updated = SmsCheckerDevice::whereNull('store_id')
            ->update(['store_id' => $platformStore->id]);

        $this->info("Updated {$updated} device(s) to use Platform Store.");
        $this->newLine();

        // 5. แสดงผลลัพธ์
        $this->info('Final device configuration:');
        $allDevices = SmsCheckerDevice::with('store')->get();
        $this->table(
            ['ID', 'Device ID', 'Device Name', 'Store ID', 'Store Name', 'Status'],
            $allDevices->map(fn($d) => [
                $d->id,
                $d->device_id,
                $d->device_name,
                $d->store_id,
                $d->store?->store_name ?? 'N/A',
                $d->status,
            ])
        );

        $this->newLine();
        $this->info('Platform Store setup completed!');

        return Command::SUCCESS;
    }
}
