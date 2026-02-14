<?php

namespace App\Console\Commands;

use App\Jobs\FlowAccountSyncJob;
use Illuminate\Console\Command;

/**
 * FlowAccount Sync Command
 *
 * คำสั่งสำหรับซิงค์ข้อมูลกับ FlowAccount
 * สามารถรันผ่าน scheduler หรือรันมือได้
 */
class FlowAccountSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flowaccount:sync
                            {--connection= : ID ของ connection ที่จะซิงค์ (เว้นว่าง = ทั้งหมด)}
                            {--type= : ประเภทข้อมูล (contacts, products, invoices, expenses)}
                            {--sync : รัน synchronous แทน queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ซิงค์ข้อมูลกับ FlowAccount';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connectionId = $this->option('connection') ? (int) $this->option('connection') : null;
        $syncType = $this->option('type');
        $runSync = $this->option('sync');

        $this->info('เริ่มต้นการซิงค์ FlowAccount...');

        if ($connectionId) {
            $this->info("  - Connection ID: {$connectionId}");
        } else {
            $this->info('  - Connection: ทั้งหมดที่เปิดใช้งาน auto_sync');
        }

        if ($syncType) {
            $this->info("  - ประเภท: {$syncType}");
        } else {
            $this->info('  - ประเภท: ทั้งหมด');
        }

        try {
            if ($runSync) {
                // รัน synchronous
                $this->info('รันแบบ synchronous...');
                $job = new FlowAccountSyncJob($connectionId, $syncType);
                $job->handle(app(\App\Services\FlowAccountService::class));
            } else {
                // ส่งเข้า queue
                $this->info('ส่งเข้า queue...');
                FlowAccountSyncJob::dispatch($connectionId, $syncType);
            }

            $this->info('การซิงค์เริ่มต้นสำเร็จ!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('เกิดข้อผิดพลาด: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
