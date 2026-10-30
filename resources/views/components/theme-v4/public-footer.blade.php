{{--
 | ส่วนท้ายสาธารณะ ธีม V4 (ใช้คู่กับ x-theme-v4.public-header)
 |
 | ตัวอย่าง:  <x-theme-v4.public-footer />
 |           <x-theme-v4.public-footer :shop-links="[['href' => '#products', 'label' => 'สินค้าแนะนำ']]" />
 |
 | props:
 |   shopLinks  array|null  ลิงก์คอลัมน์ "ร้านค้า & บริการ" [['href' => ..., 'label' => ...]] (null = ชุดมาตรฐาน)
 --}}
@props([
    'shopLinks' => null,
])

<x-nova.footer />
