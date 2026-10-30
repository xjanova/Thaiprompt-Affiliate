{{--
 | ส่วนหัวสาธารณะ ธีม V4 (ใช้กับทุกหน้าที่ @extends('layouts.frontend-v4'))
 | ใช้ร่วมกัน: หน้าแรก / ร้านค้า / ตะกร้า / ชำระเงิน / ตลาดสด / หน้าร้าน
 |
 | ตัวอย่าง:
 |   <x-theme-v4.public-header active="shop" :search="true" />
 |   <x-theme-v4.public-header active="home" :links="[['href' => '#services', 'label' => 'บริการ']]" />
 |
 | props:
 |   active            string|null  เมนูที่กำลังอยู่ home|shop|official|stores|taladsod|rider|merchant|cart|orders
 |   links             array        ลิงก์เสริมก่อนเมนูหลัก [['href' => '#x', 'label' => '...']]
 |   linksAfter        array        ลิงก์เสริมหลังเมนูหลัก
 |   search            bool         แสดงช่องค้นหาสินค้า (ค่าเริ่มต้นไม่แสดง)
 |   searchAction      string|null  ปลายทางฟอร์มค้นหา (ค่าเริ่มต้น storefront.index)
 |   searchName        string       ชื่อช่องค้นหา (ค่าเริ่มต้น search)
 |   searchValue       string|null  ค่าเริ่มต้นในช่องค้นหา
 |   searchPlaceholder string
 |   suggest           bool         แนะนำสินค้าขณะพิมพ์ (storefront.search) — ใช้กับการค้นหาร้านค้าออนไลน์เท่านั้น
 |   sticky            bool         ติดขอบบนเมื่อเลื่อน
 --}}
@props([
    'active' => null,
    'links' => [],
    'linksAfter' => [],
    'search' => false,
    'searchAction' => null,
    'searchName' => 'search',
    'searchValue' => null,
    'searchPlaceholder' => 'ค้นหาสินค้า ร้านค้า หรือหมวดหมู่...',
    'suggest' => true,
    'sticky' => true,
])

{{-- props ชุดเดียวกันส่งต่อให้แถบหัวโนวาแบบทึบ --}}
<x-nova.header :active="$active" :solid="true" :links="$links" :links-after="$linksAfter" :search="(bool) $search"
               :search-action="$searchAction" :search-name="$searchName" :search-value="$searchValue"
               :search-placeholder="$searchPlaceholder" :suggest="(bool) $suggest" :sticky="(bool) $sticky" />
