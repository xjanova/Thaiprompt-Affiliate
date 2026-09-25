{{--
 | สถานะว่าง (ยังไม่มีข้อมูล) ของแผงร้านค้า V4
 | ใช้: <x-seller-kit.empty icon="📦" title="ยังไม่มีรายการ" text="คำอธิบาย"> ปุ่ม (ถ้ามี) </x-seller-kit.empty>
 --}}
@props(['icon' => '📭', 'title' => 'ยังไม่มีข้อมูล', 'text' => null])

<div style="padding:40px 20px; text-align:center;">
    <div class="tp-inset" style="width:74px; height:74px; margin:0 auto; border-radius:22px; display:grid; place-items:center; font-size:32px;" aria-hidden="true">{{ $icon }}</div>
    <div style="font-size:16px; font-weight:800; color:var(--ink); margin-top:14px;">{{ $title }}</div>
    @if($text)
        <div style="font-size:12.5px; color:var(--ink2); margin:6px auto 0; max-width:420px; line-height:1.65;">{{ $text }}</div>
    @endif
    @if(trim((string) $slot) !== '')
        <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:8px; margin-top:16px;">{{ $slot }}</div>
    @endif
</div>
