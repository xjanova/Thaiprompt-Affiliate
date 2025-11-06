<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosOfflineQueue extends Model
{
    use HasFactory;

    protected $table = 'pos_offline_queue';

    protected $fillable = [
        'pos_device_id',
        'queue_type',
        'data',
        'status',
        'retry_count',
        'max_retries',
        'error_message',
        'attempted_at',
        'completed_at',
        'priority',
    ];

    protected $casts = [
        'data' => 'array',
        'attempted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function posDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('pos_device_id', $deviceId);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')
                     ->orderBy('created_at', 'asc');
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
                     ->whereColumn('retry_count', '<', 'max_retries');
    }

    /**
     * Helper Methods
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canRetry(): bool
    {
        return $this->isFailed() && $this->retry_count < $this->max_retries;
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'attempted_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function retry(): void
    {
        if (!$this->canRetry()) {
            throw new \Exception('Queue item cannot be retried');
        }

        $this->update([
            'status' => 'pending',
            'error_message' => null,
        ]);
    }

    public function process(): bool
    {
        try {
            $this->markAsProcessing();

            // Process based on queue type
            $result = match ($this->queue_type) {
                'transaction' => $this->processTransaction(),
                'stock_update' => $this->processStockUpdate(),
                'session' => $this->processSession(),
                default => throw new \Exception("Unknown queue type: {$this->queue_type}"),
            };

            if ($result) {
                $this->markAsCompleted();
                return true;
            }

            throw new \Exception('Processing failed');
        } catch (\Exception $e) {
            $this->markAsFailed($e->getMessage());
            return false;
        }
    }

    protected function processTransaction(): bool
    {
        $transactionData = $this->data;

        // Create or update transaction
        $transaction = PosTransaction::updateOrCreate(
            ['transaction_code' => $transactionData['transaction_code']],
            $transactionData
        );

        // Mark as synced
        $transaction->markAsSynced();

        return true;
    }

    protected function processStockUpdate(): bool
    {
        $stockData = $this->data;

        foreach ($stockData['items'] as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $product->decrement('stock_quantity', $item['quantity']);
            }
        }

        return true;
    }

    protected function processSession(): bool
    {
        $sessionData = $this->data;

        // Update session
        $session = PosSession::updateOrCreate(
            ['session_code' => $sessionData['session_code']],
            $sessionData
        );

        return true;
    }

    /**
     * Static Helper Methods
     */
    public static function queueTransaction(int $deviceId, array $transactionData, int $priority = 0): self
    {
        return static::create([
            'pos_device_id' => $deviceId,
            'queue_type' => 'transaction',
            'data' => $transactionData,
            'priority' => $priority,
            'status' => 'pending',
        ]);
    }

    public static function queueStockUpdate(int $deviceId, array $stockData, int $priority = 0): self
    {
        return static::create([
            'pos_device_id' => $deviceId,
            'queue_type' => 'stock_update',
            'data' => $stockData,
            'priority' => $priority,
            'status' => 'pending',
        ]);
    }

    public static function queueSession(int $deviceId, array $sessionData, int $priority = 0): self
    {
        return static::create([
            'pos_device_id' => $deviceId,
            'queue_type' => 'session',
            'data' => $sessionData,
            'priority' => $priority,
            'status' => 'pending',
        ]);
    }

    public static function processPendingForDevice(int $deviceId, int $limit = 100): int
    {
        $queue = static::pending()
            ->forDevice($deviceId)
            ->byPriority()
            ->limit($limit)
            ->get();

        $processed = 0;
        foreach ($queue as $item) {
            if ($item->process()) {
                $processed++;
            }
        }

        return $processed;
    }
}
