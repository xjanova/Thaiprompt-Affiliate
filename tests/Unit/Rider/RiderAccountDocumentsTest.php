<?php

namespace Tests\Unit\Rider;

use App\Models\Rider;
use App\Services\RiderAccountService;
use App\Services\RiderNotificationService;
use PHPUnit\Framework\TestCase;

/**
 * กติกาเอกสารไรเดอร์ (ก่อนอนุมัติ) + การปิดบังเลขบัตร — ฟังก์ชันล้วน ไม่แตะฐานข้อมูล
 *
 * ที่มา audit RIDER-12: แอดมินเคยอนุมัติไรเดอร์ได้โดยไม่มีเอกสารเลย
 */
class RiderAccountDocumentsTest extends TestCase
{
    private function service(): RiderAccountService
    {
        return new RiderAccountService($this->createMock(RiderNotificationService::class));
    }

    public function test_required_documents_depend_on_vehicle(): void
    {
        $service = $this->service();

        $this->assertSame(['id_card', 'profile', 'driver_license', 'vehicle_registration'], $service->requiredDocumentTypes('motorcycle'));
        $this->assertSame(['id_card', 'profile', 'driver_license', 'vehicle_registration'], $service->requiredDocumentTypes('car'));
        $this->assertSame(['id_card', 'profile'], $service->requiredDocumentTypes('bicycle'));
        $this->assertSame(['id_card', 'profile'], $service->requiredDocumentTypes('walk'));
    }

    public function test_missing_documents_and_flags(): void
    {
        $service = $this->service();

        $rider = new Rider;
        $rider->forceFill([
            'vehicle_type' => 'motorcycle',
            'id_card_image' => 'riders/1/id_card/a.jpg',
            'profile_image' => 'riders/1/profile/b.jpg',
        ]);

        $this->assertSame(
            ['id_card' => true, 'driver_license' => false, 'vehicle_registration' => false, 'profile' => true],
            $service->documentFlags($rider)
        );
        $this->assertSame(['driver_license', 'vehicle_registration'], $service->missingDocuments($rider));
        $this->assertFalse($service->documentsComplete($rider));
        $this->assertSame('ใบอนุญาตขับขี่, สำเนาทะเบียนรถ', $service->documentLabels($service->missingDocuments($rider)));

        // เดินเท้าไม่ต้องมีใบขับขี่/ทะเบียนรถ
        $rider->forceFill(['vehicle_type' => 'walk']);
        $this->assertSame([], $service->missingDocuments($rider));
        $this->assertTrue($service->documentsComplete($rider));
    }

    public function test_document_list_only_links_uploaded_files(): void
    {
        $service = $this->service();

        $rider = new Rider;
        $rider->forceFill(['vehicle_type' => 'bicycle', 'id_card_image' => 'riders/1/id_card/a.jpg']);

        $list = collect($service->documentList($rider, fn (Rider $r, string $type) => "https://example.test/{$type}"))
            ->keyBy('type');

        $this->assertSame('https://example.test/id_card', $list['id_card']['url']);
        $this->assertTrue($list['id_card']['required']);
        $this->assertNull($list['profile']['url']);
        $this->assertTrue($list['profile']['required']);
        $this->assertFalse($list['driver_license']['required']);
        $this->assertFalse($list['driver_license']['uploaded']);
    }

    public function test_mask_id_card_shows_only_edges(): void
    {
        $service = $this->service();

        $this->assertSame('110xxxxxxx708', $service->maskIdCard('1101700230708'));
        $this->assertSame('110xxxxxxx708', $service->maskIdCard('1-1017-00230-70-8'));
        $this->assertNull($service->maskIdCard(null));
        $this->assertSame('xxxx', $service->maskIdCard('1234'));
    }
}
