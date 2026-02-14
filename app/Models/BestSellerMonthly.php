<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * BestSellerMonthly Model
 *
 * สินค้าขายดีรายเดือน
 * - คำนวณจากยอดขายในแต่ละเดือน
 * - จำนวนที่แสดงตั้งค่าได้โดย Admin
 * - อัพเดททุกต้นเดือนอัตโนมัติ
 *
 * @property int $id
 * @property int $product_id
 * @property int $store_id
 * @property int $month
 * @property int $year
 * @property int $sales_count
 * @property float $sales_amount
 * @property int $rank_position
 * @property bool $is_active
 * @property \Carbon\Carbon|null $calculated_at
 */
class BestSellerMonthly extends Model
{
    /**
     * ตารางที่ใช้
     *
     * @var string
     */
    protected $table = 'best_seller_monthly';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'store_id',
        'month',
        'year',
        'sales_count',
        'sales_amount',
        'rank_position',
        'is_active',
        'calculated_at',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'sales_count' => 'integer',
        'sales_amount' => 'decimal:2',
        'rank_position' => 'integer',
        'is_active' => 'boolean',
        'calculated_at' => 'datetime',
    ];

    // ===== Relationships =====

    /**
     * สินค้า
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ร้านค้า
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    // ===== Scopes =====

    /**
     * Active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เดือนนี้
     */
    public function scopeThisMonth($query)
    {
        return $query->where('month', now()->month)
            ->where('year', now()->year);
    }

    /**
     * เดือนที่ระบุ
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('month', $month)
            ->where('year', $year);
    }

    /**
     * เรียงตามอันดับ
     */
    public function scopeOrderByRank($query)
    {
        return $query->orderBy('rank_position');
    }

    // ===== Static Methods =====

    /**
     * คำนวณและอัพเดทสินค้าขายดีรายเดือน
     *
     * @param  int  $limit  จำนวนที่ต้องการแสดง
     */
    public static function calculateBestSellers(?int $month = null, ?int $year = null, int $limit = 10): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        // คำนวณวันที่เริ่มต้นและสิ้นสุดของเดือน
        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth();

        return DB::transaction(function () use ($month, $year, $startDate, $endDate, $limit) {
            // ปิด best seller เดิมทั้งหมดของเดือนนี้
            self::where('month', $month)
                ->where('year', $year)
                ->update(['is_active' => false]);

            // ถอดออกจาก Official Shop
            $oldEntries = OfficialShopProduct::bestSellers()->active()->get();
            foreach ($oldEntries as $entry) {
                $entry->removeFromOfficialShop('สินค้าขายดีเปลี่ยนเดือน');
            }

            // คำนวณยอดขายจาก order_items
            $topProducts = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->where('orders.status', 'completed')
                ->whereNotNull('products.store_id')
                ->select(
                    'products.id as product_id',
                    'products.store_id',
                    DB::raw('SUM(order_items.quantity) as total_qty'),
                    DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_amount')
                )
                ->groupBy('products.id', 'products.store_id')
                ->orderByDesc('total_qty')
                ->limit($limit)
                ->get();

            $results = [];
            $rank = 1;

            foreach ($topProducts as $item) {
                // สร้างหรืออัพเดท best seller
                $bestSeller = self::updateOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'store_id' => $item->store_id,
                        'sales_count' => $item->total_qty,
                        'sales_amount' => $item->total_amount,
                        'rank_position' => $rank,
                        'is_active' => true,
                        'calculated_at' => now(),
                    ]
                );

                // เพิ่มเข้า Official Shop
                $product = Product::find($item->product_id);
                if ($product) {
                    OfficialShopProduct::addProduct(
                        $product,
                        OfficialShopProduct::TYPE_BEST_SELLER,
                        0, // ไม่มี AI score
                        [
                            'month' => $month,
                            'year' => $year,
                            'sales_count' => $item->total_qty,
                            'rank' => $rank,
                        ]
                    );
                }

                $results[] = [
                    'rank' => $rank,
                    'product_id' => $item->product_id,
                    'store_id' => $item->store_id,
                    'sales_count' => $item->total_qty,
                    'sales_amount' => $item->total_amount,
                ];

                $rank++;
            }

            return [
                'month' => $month,
                'year' => $year,
                'limit' => $limit,
                'products' => $results,
                'calculated_at' => now()->toDateTimeString(),
            ];
        });
    }

    /**
     * ดึงสินค้าขายดีเดือนนี้
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getThisMonthBestSellers(int $limit = 10)
    {
        return self::with(['product.category', 'product.images', 'store'])
            ->thisMonth()
            ->active()
            ->orderByRank()
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงสินค้าขายดีของเดือนที่ระบุ
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBestSellersForPeriod(int $month, int $year, int $limit = 10)
    {
        return self::with(['product.category', 'product.images', 'store'])
            ->forPeriod($month, $year)
            ->active()
            ->orderByRank()
            ->limit($limit)
            ->get();
    }

    // ===== Accessors =====

    /**
     * ชื่อเดือน (ภาษาไทย)
     */
    public function getMonthNameAttribute(): string
    {
        $thaiMonths = [
            1 => 'มกราคม',
            2 => 'กุมภาพันธ์',
            3 => 'มีนาคม',
            4 => 'เมษายน',
            5 => 'พฤษภาคม',
            6 => 'มิถุนายน',
            7 => 'กรกฎาคม',
            8 => 'สิงหาคม',
            9 => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม',
        ];

        return $thaiMonths[$this->month] ?? '';
    }

    /**
     * ชื่อเดือน/ปี แสดงผล
     */
    public function getPeriodLabelAttribute(): string
    {
        return "{$this->month_name} {$this->year}";
    }

    /**
     * อันดับแสดงผล
     */
    public function getRankLabelAttribute(): string
    {
        return "อันดับ {$this->rank_position}";
    }
}
