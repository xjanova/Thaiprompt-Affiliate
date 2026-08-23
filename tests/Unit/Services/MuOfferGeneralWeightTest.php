<?php

namespace Tests\Unit\Services;

use App\Models\MarketplaceProduct;
use App\Services\Marketplace\MuProductPicker;
use App\Services\Marketplace\ProductQueryParser;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

/**
 * ใบที่ 3 (ของทั่วไป) ต้องไม่ให้ของแพงกินโอกาสไปหมด
 *
 * วัดจากพูลจริง 687 ชิ้นบน prod (2026-08-23): ตัวถ่วงค่าคอมเต็มแรงทำให้
 * ของ 11% ที่แพงสุดกินโอกาสไป 30% ส่วนของถูก 23% ของพูลได้แค่ 3.8%
 * ⇒ อาการ "บอทวนของไม่กี่อย่าง" ย้ายมาเกิดซ้ำในพูลของทั่วไป
 *
 * DB-free — สร้างโมเดลลอยๆ ไม่ save
 */
class MuOfferGeneralWeightTest extends TestCase
{
    private MuProductPicker $picker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->picker = new MuProductPicker(new ProductQueryParser);
    }

    private function invokePrivate(string $method, array $args): mixed
    {
        $m = new ReflectionMethod(MuProductPicker::class, $method);
        $m->setAccessible(true);

        return $m->invokeArgs($this->picker, $args);
    }

    private function product(int $id, float $price, float $rate): MarketplaceProduct
    {
        $p = new MarketplaceProduct(['price' => $price, 'commission_rate' => $rate]);
        $p->id = $id;

        return $p;
    }

    /** @test */
    public function ค่า_exponent_ที่พิมพ์มั่วในตาราง_settings_ต้องไม่ทำให้พังเงียบ(): void
    {
        // ค่าปกติผ่านตรงๆ
        $this->assertSame(0.35, $this->invokePrivate('normalizeExponent', [0.35]));
        $this->assertSame(0.5, $this->invokePrivate('normalizeExponent', ['0.5']));

        // เกินช่วง → บีบเข้ากรอบ ไม่ใช่ปล่อยผ่าน
        $this->assertSame(1.0, $this->invokePrivate('normalizeExponent', [5]), 'ใส่ 5 = ของแพงกินหมดยิ่งกว่าเดิม');
        $this->assertSame(0.0, $this->invokePrivate('normalizeExponent', [-2]), 'ค่าติดลบ = pow กลับด้าน ของถูกชนะขาด');

        // ไม่ใช่ตัวเลข → กลับไปใช้ค่าตั้งต้น ห้ามกลายเป็น 0 (0 = ทิ้งการถ่วงค่าคอมทั้งหมด)
        $this->assertSame(0.5, $this->invokePrivate('normalizeExponent', ['']));
        $this->assertSame(0.5, $this->invokePrivate('normalizeExponent', ['ปิด']));
        $this->assertSame(0.5, $this->invokePrivate('normalizeExponent', [null]));
    }

    /** @test */
    public function ลดความแรงการถ่วงแล้วของถูกต้องมีโอกาสออกจริง(): void
    {
        // ของถูกได้ค่าคอม ฿1 (พื้นขั้นต่ำ) · ของแพงได้ ฿10,000 — ต่างกัน 10,000 เท่า
        $pool = new Collection([
            $this->product(1, 25, 4),        // earning ต่ำกว่า 1 → โดนพื้น max(1.0)
            $this->product(2, 50000, 20),    // earning = 10,000
        ]);

        $countCheap = function (float $exponent) use ($pool): int {
            mt_srand(20260823); // ล็อกผลให้เทสต์ไม่แกว่ง
            $hits = 0;
            for ($i = 0; $i < 2000; $i++) {
                if ($this->invokePrivate('weightedPick', [$pool, $exponent])?->id === 1) {
                    $hits++;
                }
            }

            return $hits;
        };

        $full = $countCheap(1.0);   // คาดหวัง ~0.2 ครั้งจาก 2000
        $half = $countCheap(0.5);   // คาดหวัง ~20 ครั้งจาก 2000
        $flat = $countCheap(0.0);   // คาดหวัง ~1000 ครั้งจาก 2000

        $this->assertLessThanOrEqual(3, $full, "ถ่วงเต็มแรง ของถูกแทบไม่มีโอกาสออก (ได้ {$full}/2000)");
        $this->assertGreaterThan(5, $half, "ลดความแรงแล้วของถูกต้องได้ออกบ้าง (ได้ {$half}/2000)");
        $this->assertGreaterThan($full, $half, 'exponent ต่ำลง = ของถูกต้องมีโอกาสมากขึ้น');
        $this->assertGreaterThan(800, $flat, "exponent 0 = สุ่มเท่ากัน ควรได้ราวครึ่ง (ได้ {$flat}/2000)");
    }

    /** @test */
    public function พูลชิ้นเดียวหรือพูลว่าง_ต้องไม่พังไม่ว่า_exponent_เท่าไหร่(): void
    {
        foreach ([0.0, 0.5, 1.0] as $e) {
            $this->assertNull($this->invokePrivate('weightedPick', [new Collection, $e]));

            $only = $this->product(9, 100, 10);
            $this->assertSame(9, $this->invokePrivate('weightedPick', [new Collection([$only]), $e])?->id);
        }
    }
}
