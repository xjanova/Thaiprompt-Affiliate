<?php

namespace Tests\Unit\Services;

use App\Models\AiApiKey;
use App\Models\FortuneReading;
use App\Services\FortuneAIService;
use ReflectionClass;
use Tests\TestCase;

/**
 * ทดสอบการผูก reading_id เข้ากับ ai_api_key_usage_logs
 *
 * ที่มา: reading_id เป็น NULL 100% บน prod (2,355 แถวใน 30 วัน)
 *   → คิดต้นทุน AI ต่อ 1 ใบดูดวงไม่ได้
 *
 * เทสต์นี้ล็อก 2 ฝั่งของสัญญาไว้ด้วยกัน:
 *   1. forReading() ส่งคีย์ 'reading_id' ออกมาจริง
 *   2. customerContextColumns() ฝั่งรับ อ่านคีย์นั้นจริง
 * ถ้าใครเปลี่ยนชื่อคีย์ข้างใดข้างหนึ่ง เทสต์นี้จะแดงทันที
 * (เดิมพังเงียบ — log เขียนสำเร็จแต่ reading_id ว่าง ไม่มี error ให้เห็น)
 *
 * ไม่แตะ DB: ใช้ newInstanceWithoutConstructor + model ที่ยังไม่ save
 */
class FortuneAiUsageReadingContextTest extends TestCase
{
    /**
     * สร้าง FortuneAIService โดยข้าม constructor
     * (constructor จะไปดึง settings/pool = ต้องใช้ DB ซึ่งเทสต์นี้ไม่ต้องการ)
     */
    private function makeService(): FortuneAIService
    {
        return (new ReflectionClass(FortuneAIService::class))->newInstanceWithoutConstructor();
    }

    /**
     * อ่าน $callContext (protected) ออกมาตรวจ
     */
    private function readCallContext(FortuneAIService $service): ?array
    {
        $prop = (new ReflectionClass(FortuneAIService::class))->getProperty('callContext');
        $prop->setAccessible(true);

        return $prop->getValue($service);
    }

    /**
     * เรียก customerContextColumns() (private) บน AiApiKey
     *
     * @param  array<string,mixed>|null  $context
     * @return array<string,mixed>
     */
    private function mapToColumns(?array $context): array
    {
        $key = (new ReflectionClass(AiApiKey::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(AiApiKey::class))->getMethod('customerContextColumns');
        $method->setAccessible(true);

        return $method->invoke($key, $context);
    }

    /** @test */
    public function for_reading_ใส่_reading_id_ลง_call_context(): void
    {
        $reading = new FortuneReading;
        $reading->id = 11007;
        $reading->user_id = 42;
        $reading->facebook_user_id = 'psid-abc';
        $reading->facebook_user_name = 'สมชาย ใจดี';

        $service = $this->makeService();
        $service->forReading($reading);

        $context = $this->readCallContext($service);

        $this->assertIsArray($context);
        $this->assertSame(11007, $context['reading_id']);
        $this->assertSame(42, $context['user_id']);
        $this->assertSame('psid-abc', $context['fb_user_id']);
        $this->assertSame('สมชาย ใจดี', $context['customer_name']);
    }

    /**
     * 🎯 หัวใจของเทสต์: คีย์ที่ forReading ส่ง ต้องเป็นคีย์ที่ฝั่งรับอ่านจริง
     *
     * @test
     */
    public function คีย์จาก_for_reading_ถูก_map_ลงคอลัมน์จริง(): void
    {
        $reading = new FortuneReading;
        $reading->id = 11007;
        $reading->user_id = 42;
        $reading->facebook_user_id = 'psid-abc';
        $reading->facebook_user_name = 'สมชาย ใจดี';

        $service = $this->makeService();
        $service->forReading($reading);

        $columns = $this->mapToColumns($this->readCallContext($service));

        // reading_id ต้องรอดมาถึงคอลัมน์ — นี่คือสิ่งที่ prod ขาดไป
        $this->assertArrayHasKey('reading_id', $columns);
        $this->assertSame(11007, $columns['reading_id']);
        $this->assertSame(42, $columns['user_id']);
        $this->assertSame('psid-abc', $columns['fb_user_id']);
        $this->assertSame('สมชาย ใจดี', $columns['customer_name']);
    }

    /** @test */
    public function ใบดูดวงที่ไม่มี_facebook_id_ยังได้_reading_id(): void
    {
        // เคส LINE / เว็บ juntra — ไม่มี PSID แต่ต้องคิดต้นทุนต่อใบได้
        $reading = new FortuneReading;
        $reading->id = 900;
        $reading->user_id = 7;

        $service = $this->makeService();
        $service->forReading($reading);

        $columns = $this->mapToColumns($this->readCallContext($service));

        $this->assertSame(900, $columns['reading_id']);
        $this->assertSame(7, $columns['user_id']);
        $this->assertArrayNotHasKey('fb_user_id', $columns);
    }

    /** @test */
    public function for_reading_null_ล้าง_context_ไม่ระเบิด(): void
    {
        $service = $this->makeService();
        $service->forReading(null);

        $this->assertNull($this->readCallContext($service));
        $this->assertSame([], $this->mapToColumns(null));
    }

    /**
     * one-shot semantics: context ถูกล้างหลัง generate ทุกครั้ง
     * ถ้ามีใครทำให้มัน "ค้าง" ตัวตนลูกค้าจะรั่วข้ามคน — เทสต์นี้กันไว้
     *
     * @test
     */
    public function for_reading_ทับของเดิมไม่ปนกันข้ามลูกค้า(): void
    {
        $first = new FortuneReading;
        $first->id = 1;
        $first->facebook_user_id = 'psid-first';
        $first->facebook_user_name = 'ลูกค้าคนแรก';

        $second = new FortuneReading;
        $second->id = 2;
        $second->facebook_user_id = 'psid-second';
        $second->facebook_user_name = 'ลูกค้าคนที่สอง';

        $service = $this->makeService();
        $service->forReading($first);
        $service->forReading($second);

        $columns = $this->mapToColumns($this->readCallContext($service));

        $this->assertSame(2, $columns['reading_id']);
        $this->assertSame('psid-second', $columns['fb_user_id']);
        $this->assertSame('ลูกค้าคนที่สอง', $columns['customer_name']);
    }

    /**
     * ไม่ lazy-load relation user (กัน N+1 เพราะถูกเรียกก่อน AI call ทุกครั้ง)
     *
     * @test
     */
    public function ไม่_lazy_load_relation_user(): void
    {
        $reading = new FortuneReading;
        $reading->id = 55;
        $reading->facebook_user_name = 'ชื่อจากเฟซบุ๊ก';
        // ไม่ setRelation('user') → ต้อง fallback ชื่อ FB ไม่ใช่ยิง query

        $service = $this->makeService();
        $service->forReading($reading);

        $context = $this->readCallContext($service);

        $this->assertFalse($reading->relationLoaded('user'));
        $this->assertSame('ชื่อจากเฟซบุ๊ก', $context['customer_name']);
    }
}
