{{--
 | สถานะว่างของแผงผู้ขาย V4 (ไอคอน + หัวข้อ + คำอธิบาย + ปุ่มใน slot)
 --}}
@props([
    'icon' => '📭',
    'title' => 'ยังไม่มีข้อมูล',
    'text' => null,
])

<div style="text-align:center; padding:46px 20px;">
    <div style="font-size:50px; opacity:.6; line-height:1;">{{ $icon }}</div>
    <div style="margin-top:12px; font-weight:800; font-size:15px; color:var(--ink);">{{ $title }}</div>
    @if($text)
        <div style="font-size:12.5px; color:var(--ink2); margin-top:5px; max-width:420px; margin-inline:auto; line-height:1.6;">{{ $text }}</div>
    @endif
    @if(trim((string) $slot) !== '')
        <div style="margin-top:16px; display:flex; flex-wrap:wrap; justify-content:center; gap:8px;">{{ $slot }}</div>
    @endif
</div>
