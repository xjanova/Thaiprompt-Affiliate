<?php

namespace App\Jobs;

use App\Models\AccountingFlowaccountConnection;
use App\Services\FlowAccountService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * FlowAccount Auto Sync Job
 *
 * Job สำหรับซิงค์ข้อมูลกับ FlowAccount อัตโนมัติ
 * สามารถตั้งเวลาผ่าน Scheduler ได้
 */
class FlowAccountSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Connection ID ที่จะซิงค์ (null = ทั้งหมด)
     */
    protected ?int $connectionId;

    /**
     * ประเภทข้อมูลที่จะซิงค์ (null = ทั้งหมด)
     */
    protected ?string $syncType;

    /**
     * จำนวนครั้งที่จะ retry เมื่อ job ล้มเหลว
     */
    public int $tries = 3;

    /**
     * Timeout ของ job (วินาที)
     */
    public int $timeout = 300;

    /**
     * Constructor
     *
     * @param int|null $connectionId ID ของ connection ที่จะซิงค์
     * @param string|null $syncType ประเภทข้อมูล (contacts, products, invoices, expenses)
     */
    public function __construct(?int $connectionId = null, ?string $syncType = null)
    {
        $this->connectionId = $connectionId;
        $this->syncType = $syncType;
    }

    /**
     * ดำเนินการ Job
     *
     * @param FlowAccountService $service
     * @return void
     */
    public function handle(FlowAccountService $service): void
    {
        Log::info('FlowAccount sync job started', [
            'connection_id' => $this->connectionId,
            'sync_type' => $this->syncType,
        ]);

        try {
            // ดึง connections ที่ต้องซิงค์
            $query = AccountingFlowaccountConnection::where('is_active', true)
                ->where('auto_sync', true);

            if ($this->connectionId) {
                $query->where('id', $this->connectionId);
            }

            $connections = $query->get();

            if ($connections->isEmpty()) {
                Log::info('FlowAccount sync: No active connections with auto_sync enabled');
                return;
            }

            foreach ($connections as $connection) {
                $this->syncConnection($connection, $service);
            }

            Log::info('FlowAccount sync job completed');

        } catch (\Exception $e) {
            Log::error('FlowAccount sync job failed: ' . $e->getMessage());
            throw $e; // Re-throw เพื่อให้ job retry
        }
    }

    /**
     * ซิงค์ข้อมูลสำหรับ connection หนึ่ง
     *
     * @param AccountingFlowaccountConnection $connection
     * @param FlowAccountService $service
     * @return void
     */
    protected function syncConnection(AccountingFlowaccountConnection $connection, FlowAccountService $service): void
    {
        Log::info("FlowAccount syncing for connection ID: {$connection->id}");

        $syncSettings = $connection->sync_settings ?? [];
        $userId = $connection->user_id;
        $results = [];

        try {
            // ซิงค์ผู้ติดต่อ
            if ($this->shouldSync('contacts', $syncSettings)) {
                $results['contacts'] = $service->syncContactsFromFlowAccount($connection, $userId);
                Log::info("FlowAccount contacts synced", $results['contacts']);
            }

            // ซิงค์สินค้า
            if ($this->shouldSync('products', $syncSettings)) {
                $results['products'] = $service->syncProductsFromFlowAccount($connection, $userId);
                Log::info("FlowAccount products synced", $results['products']);
            }

            // ซิงค์ใบแจ้งหนี้ไปยัง FlowAccount
            if ($this->shouldSync('invoices', $syncSettings)) {
                $results['invoices'] = $this->syncInvoices($connection, $service);
                Log::info("FlowAccount invoices synced", $results['invoices']);
            }

            // ซิงค์ค่าใช้จ่ายไปยัง FlowAccount
            if ($this->shouldSync('expenses', $syncSettings)) {
                $results['expenses'] = $this->syncExpenses($connection, $service);
                Log::info("FlowAccount expenses synced", $results['expenses']);
            }

            // อัปเดตเวลาซิงค์
            $connection->update(['last_sync_at' => now()]);

            Log::info("FlowAccount sync completed for connection ID: {$connection->id}", $results);

        } catch (\Exception $e) {
            Log::error("FlowAccount sync failed for connection ID: {$connection->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ตรวจสอบว่าควรซิงค์ประเภทนี้หรือไม่
     *
     * @param string $type
     * @param array $settings
     * @return bool
     */
    protected function shouldSync(string $type, array $settings): bool
    {
        // ถ้าระบุ syncType มา ให้ซิงค์เฉพาะประเภทนั้น
        if ($this->syncType !== null) {
            return $this->syncType === $type;
        }

        // ตรวจสอบจาก settings
        return $settings["sync_{$type}"] ?? true;
    }

    /**
     * ซิงค์ใบแจ้งหนี้ไปยัง FlowAccount
     *
     * @param AccountingFlowaccountConnection $connection
     * @param FlowAccountService $service
     * @return array
     */
    protected function syncInvoices(AccountingFlowaccountConnection $connection, FlowAccountService $service): array
    {
        $synced = 0;
        $failed = 0;

        $invoices = \App\Models\AccountingInvoice::where('user_id', $connection->user_id)
            ->whereNull('flowaccount_id')
            ->where('status', '!=', 'draft')
            ->get();

        foreach ($invoices as $invoice) {
            $result = $service->syncInvoice($invoice, $connection);
            if ($result) {
                $synced++;
            } else {
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    /**
     * ซิงค์ค่าใช้จ่ายไปยัง FlowAccount
     *
     * @param AccountingFlowaccountConnection $connection
     * @param FlowAccountService $service
     * @return array
     */
    protected function syncExpenses(AccountingFlowaccountConnection $connection, FlowAccountService $service): array
    {
        $synced = 0;
        $failed = 0;

        $expenses = \App\Models\AccountingExpense::where('user_id', $connection->user_id)
            ->whereNull('flowaccount_id')
            ->where('status', '!=', 'draft')
            ->get();

        foreach ($expenses as $expense) {
            $result = $service->syncExpense($expense, $connection);
            if ($result) {
                $synced++;
            } else {
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    /**
     * จัดการเมื่อ job ล้มเหลว
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FlowAccount sync job failed permanently', [
            'connection_id' => $this->connectionId,
            'sync_type' => $this->syncType,
            'error' => $exception->getMessage(),
        ]);

        // สามารถส่ง notification ได้ที่นี่ถ้าต้องการ
    }
}
