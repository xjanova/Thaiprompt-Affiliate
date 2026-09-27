{{-- 500 ระบบขัดข้อง — ธีมโนวา · ห้ามแตะฐานข้อมูลในหน้านี้ (อาจแสดงตอนฐานข้อมูลล่ม) --}}
@include('errors.nova-page', [
    'nvCode' => '500',
    'nvTitle' => 'ระบบขัดข้องชั่วคราว',
    'nvMessage' => 'ขออภัยในความไม่สะดวก ทีมงานได้รับแจ้งแล้ว กรุณาลองใหม่อีกครั้งในอีกสักครู่',
    'nvDeco' => 'elephant.webp',
    'nvActions' => [
        ['href' => url()->current(), 'label' => 'ลองใหม่อีกครั้ง', 'icon' => 'fa-rotate-right', 'primary' => true],
        ['href' => url('/'), 'label' => 'กลับหน้าแรก', 'icon' => 'fa-house'],
    ],
])
