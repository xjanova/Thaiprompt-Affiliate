{{-- 404 ไม่พบหน้า — ธีมโนวา (หน้าตาอยู่ใน errors/nova-page) --}}
@include('errors.nova-page', [
    'nvCode' => '404',
    'nvTitle' => 'ไม่พบหน้าที่คุณต้องการ',
    'nvMessage' => 'หน้านี้อาจถูกย้ายหรือไม่มีอยู่แล้ว ลองกลับไปหน้าแรก หรือเลือกดูสินค้าและบริการของเราได้เลย',
    'nvDeco' => 'lamp.webp',
    'nvActions' => array_values(array_filter([
        ['href' => url('/'), 'label' => 'กลับหน้าแรก', 'icon' => 'fa-house', 'primary' => true],
        ['href' => url('/').'#products', 'label' => 'ดูสินค้าทั้งหมด', 'icon' => 'fa-bag-shopping'],
        \Illuminate\Support\Facades\Route::has('contact') ? ['href' => route('contact'), 'label' => 'ติดต่อเรา', 'icon' => 'fa-headset'] : null,
    ])),
])
