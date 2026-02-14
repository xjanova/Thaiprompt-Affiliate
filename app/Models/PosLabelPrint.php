<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PosLabelPrint Model
 *
 * เก็บประวัติการพิมพ์ฉลากสำหรับ POS System
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $pos_transaction_id
 * @property int|null $template_id
 * @property string $print_type
 * @property array $products
 * @property int $total_labels
 * @property int $sheets_count
 * @property string|null $paper_size
 * @property float|null $paper_width
 * @property float|null $paper_height
 * @property array|null $print_settings
 * @property string|null $printer_name
 * @property string|null $printer_type
 * @property int|null $pos_device_id
 * @property string $status
 * @property \Carbon\Carbon|null $printed_at
 * @property string|null $error_message
 * @property array|null $metadata
 * @property string|null $print_session_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read User $user
 * @property-read PosTransaction|null $transaction
 * @property-read LabelTemplate|null $template
 * @property-read PosDevice|null $device
 */
class PosLabelPrint extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_label_prints';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'pos_transaction_id',
        'template_id',
        'print_type',
        'products',
        'total_labels',
        'sheets_count',
        'paper_size',
        'paper_width',
        'paper_height',
        'print_settings',
        'printer_name',
        'printer_type',
        'pos_device_id',
        'status',
        'printed_at',
        'error_message',
        'metadata',
        'print_session_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'products' => 'array',
        'print_settings' => 'array',
        'metadata' => 'array',
        'paper_width' => 'decimal:2',
        'paper_height' => 'decimal:2',
        'total_labels' => 'integer',
        'sheets_count' => 'integer',
        'printed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // สร้าง print_session_id อัตโนมัติ
        static::creating(function ($model) {
            if (empty($model->print_session_id)) {
                $model->print_session_id = 'PS-'.Str::upper(Str::random(12));
            }
        });
    }

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ PosTransaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    /**
     * ความสัมพันธ์กับ LabelTemplate
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(LabelTemplate::class, 'template_id');
    }

    /**
     * ความสัมพันธ์กับ PosDevice
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class, 'pos_device_id');
    }

    /**
     * Scope สำหรับกรองตามประเภท
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('print_type', $type);
    }

    /**
     * Scope สำหรับกรองตามสถานะ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope สำหรับการพิมพ์ที่สำเร็จ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope สำหรับการพิมพ์ที่ล้มเหลว
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope สำหรับ batch print session
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('print_session_id', $sessionId);
    }

    /**
     * ทำเครื่องหมายว่าพิมพ์สำเร็จ
     */
    public function markAsCompleted(): bool
    {
        $this->status = 'completed';
        $this->printed_at = now();

        return $this->save();
    }

    /**
     * ทำเครื่องหมายว่าพิมพ์ล้มเหลว
     */
    public function markAsFailed(string $errorMessage): bool
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;

        return $this->save();
    }

    /**
     * ยกเลิกการพิมพ์
     */
    public function cancel(): bool
    {
        $this->status = 'cancelled';

        return $this->save();
    }

    /**
     * ตรวจสอบว่าเป็นการพิมพ์แบบ batch หรือไม่
     */
    public function isBatch(): bool
    {
        return count($this->products) > 1;
    }

    /**
     * ดึงจำนวนสินค้าทั้งหมด
     */
    public function getTotalProductsAttribute(): int
    {
        return count($this->products);
    }

    /**
     * ดึงจำนวนฉลากทั้งหมดที่ต้องพิมพ์
     */
    public function calculateTotalLabels(): int
    {
        $total = 0;
        foreach ($this->products as $product) {
            $total += $product['quantity'] ?? 1;
        }

        return $total;
    }

    /**
     * คำนวณจำนวนแผ่นที่ต้องใช้
     */
    public function calculateSheets(int $labelsPerSheet): int
    {
        return (int) ceil($this->total_labels / $labelsPerSheet);
    }

    /**
     * ตรวจสอบว่าเป็นการพิมพ์แบบม้วนหรือไม่
     */
    public function isContinuousRoll(): bool
    {
        return $this->printer_type === 'thermal_roll' ||
               str_contains($this->paper_size ?? '', 'roll') ||
               str_contains($this->paper_size ?? '', 'thermal');
    }

    /**
     * ดึงชื่อประเภทการพิมพ์
     */
    public function getPrintTypeNameAttribute(): string
    {
        return match ($this->print_type) {
            'product_label' => 'ฉลากสินค้า',
            'shipping_label' => 'ใบปะสินค้า',
            'price_tag' => 'ฉลากราคา',
            'barcode_only' => 'Barcode',
            'custom' => 'กำหนดเอง',
            default => $this->print_type,
        };
    }

    /**
     * ดึงชื่อสถานะ
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอพิมพ์',
            'printing' => 'กำลังพิมพ์',
            'completed' => 'พิมพ์สำเร็จ',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * ดึงสีของสถานะ (สำหรับ badge)
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'printing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'primary',
        };
    }
}
