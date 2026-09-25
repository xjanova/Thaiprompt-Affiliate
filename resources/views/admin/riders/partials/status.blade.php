{{--
 | ป้ายสถานะตามชนิดข้อมูล (ชื่อไทยตรงกับ accessor ของโมเดล)
 | ตัวแปร (ขึ้นต้น status กันชนกับตัวแปรหน้าแม่): $statusKind rider|availability|job|fm_order|payment|listing|seller,
 |   $statusValue สถานะดิบ, $statusLabel (ไม่บังคับ — ใช้แทนชื่อไทยในตาราง)
--}}
@php
    $statusMaps = [
        'rider' => [
            'pending' => ['warn', 'รอตรวจสอบ', 'fa-clock'],
            'approved' => ['ok', 'อนุมัติแล้ว', 'fa-circle-check'],
            'rejected' => ['bad', 'ถูกปฏิเสธ', 'fa-circle-xmark'],
            'suspended' => ['bad', 'ถูกระงับ', 'fa-ban'],
            'inactive' => ['mute', 'ไม่ใช้งาน', 'fa-moon'],
        ],
        'availability' => [
            'online' => ['ok', 'พร้อมรับงาน', 'fa-signal'],
            'busy' => ['violet', 'กำลังส่งงาน', 'fa-motorcycle'],
            'offline' => ['mute', 'ออฟไลน์', 'fa-power-off'],
        ],
        'job' => [
            'pending' => ['warn', 'รอไรเดอร์รับงาน', 'fa-hourglass-half'],
            'accepted' => ['info', 'ไรเดอร์รับงานแล้ว', 'fa-handshake'],
            'picking_up' => ['info', 'กำลังไปรับของ', 'fa-person-biking'],
            'picked_up' => ['violet', 'รับของแล้ว', 'fa-box'],
            'delivering' => ['violet', 'กำลังจัดส่ง', 'fa-truck-fast'],
            'delivered' => ['ok', 'ส่งแล้ว', 'fa-box-open'],
            'completed' => ['ok', 'เสร็จสิ้น', 'fa-circle-check'],
            'cancelled' => ['bad', 'ยกเลิก', 'fa-ban'],
            'failed' => ['bad', 'ส่งไม่สำเร็จ', 'fa-triangle-exclamation'],
        ],
        'fm_order' => [
            'pending' => ['warn', 'รอร้านยืนยัน', 'fa-hourglass-half'],
            'accepted' => ['info', 'ร้านรับออเดอร์แล้ว', 'fa-handshake'],
            'preparing' => ['info', 'กำลังจัดเตรียม', 'fa-kitchen-set'],
            'ready' => ['violet', 'พร้อมส่ง/พร้อมรับ', 'fa-box'],
            'delivering' => ['violet', 'กำลังจัดส่ง', 'fa-truck-fast'],
            'delivered' => ['ok', 'ส่งถึงแล้ว', 'fa-box-open'],
            'completed' => ['ok', 'เสร็จสิ้น', 'fa-circle-check'],
            'cancelled' => ['bad', 'ยกเลิกแล้ว', 'fa-ban'],
            'delivery_failed' => ['bad', 'จัดส่งไม่สำเร็จ', 'fa-triangle-exclamation'],
        ],
        'payment' => [
            'pending' => ['warn', 'รอชำระ', 'fa-clock'],
            'paid' => ['ok', 'ชำระแล้ว', 'fa-circle-check'],
            'released' => ['info', 'โอนให้ร้านแล้ว', 'fa-money-bill-transfer'],
            'refunded' => ['violet', 'คืนเงินแล้ว', 'fa-rotate-left'],
            'failed' => ['bad', 'ชำระไม่สำเร็จ', 'fa-circle-xmark'],
        ],
        'listing' => [
            'draft' => ['mute', 'แบบร่าง', 'fa-pen'],
            'active' => ['ok', 'เปิดขาย', 'fa-store'],
            'sold_out' => ['warn', 'สินค้าหมด', 'fa-box-open'],
            'expired' => ['mute', 'หมดอายุ', 'fa-calendar-xmark'],
            'suspended' => ['bad', 'ถูกระงับ', 'fa-ban'],
        ],
        'seller' => [
            'active' => ['ok', 'เปิดขาย', 'fa-store'],
            'unverified' => ['warn', 'รอยืนยัน', 'fa-user-clock'],
            'inactive' => ['mute', 'ปิดร้าน', 'fa-door-closed'],
            'suspended' => ['bad', 'ถูกระงับ', 'fa-ban'],
        ],
    ];

    [$statusTone, $statusText, $statusIcon] = $statusMaps[$statusKind ?? 'job'][(string) ($statusValue ?? '')]
        ?? ['mute', (string) ($statusValue ?? '-'), 'fa-circle'];
@endphp
@include('admin.riders.partials.pill', ['pillTone' => $statusTone, 'pillText' => $statusLabel ?? $statusText, 'pillIcon' => $statusIcon, 'pillTitle' => null])
