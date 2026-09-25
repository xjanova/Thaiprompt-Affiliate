<?php

namespace App\Contracts;

use App\Models\RiderJob;

/**
 * สัญญากลางของ "ออเดอร์ที่ส่งด้วยไรเดอร์ได้"
 *
 * ออเดอร์ทุกชนิดที่ต้องการให้ไรเดอร์ไปรับ-ส่ง (ตลาดสด, ร้านค้า e-commerce ฯลฯ)
 * ต้อง implement interface นี้ เพื่อให้ RiderDispatchService สร้างงาน (RiderJob)
 * และแจ้งสถานะงานกลับมาที่ออเดอร์ได้ด้วยโค้ดชุดเดียว
 *
 * งานไรเดอร์ผูกกับออเดอร์ผ่าน rider_jobs.source_type + rider_jobs.source_id (morph)
 */
interface RiderDeliverable
{
    /**
     * จุดรับของ (ร้านค้า/ผู้ขาย)
     *
     * @return array{name: string, address: string, latitude: float, longitude: float, phone: ?string, notes: ?string}
     */
    public function riderPickupPoint(): array;

    /**
     * จุดส่งของ (ผู้ซื้อ)
     *
     * @return array{name: string, address: string, latitude: float, longitude: float, phone: ?string, notes: ?string}
     */
    public function riderDropoffPoint(): array;

    /**
     * ยอดเงินสดที่ไรเดอร์ต้องเก็บจากผู้ซื้อตอนส่ง (0 = จ่ายล่วงหน้าแล้ว ไม่ต้องเก็บ)
     */
    public function riderCodAmount(): float;

    /**
     * สรุปรายการสินค้าสั้นๆ ให้ไรเดอร์เห็น เช่น "ผักบุ้ง x2, มะนาว x1"
     */
    public function riderItemsSummary(): string;

    /**
     * user_id ของคนที่เกี่ยวข้องกับออเดอร์ (ใช้กันไรเดอร์รับงานของตัวเอง)
     *
     * @return array<int>
     */
    public function riderPartyUserIds(): array;

    /**
     * ถูกเรียกทุกครั้งหลังสถานะงานไรเดอร์เปลี่ยน (อยู่ใน transaction เดียวกับการเปลี่ยนสถานะ)
     * ให้ออเดอร์อัปเดตสถานะของตัวเองให้ตรงกับงาน เช่น picked_up → กำลังจัดส่ง, completed → ส่งถึงแล้ว
     */
    public function onRiderJobStatusChanged(RiderJob $job, string $fromStatus): void;
}
