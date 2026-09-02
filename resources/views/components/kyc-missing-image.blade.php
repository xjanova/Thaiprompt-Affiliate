{{--
    กล่องแทนรูป KYC ที่เปิดไม่ขึ้น (หน้าแอดมิน ธีม V4 นวลทองคำ)

    ใช้เมื่อ "มีพาธในฐานข้อมูล แต่ไม่มีไฟล์จริงบนดิสก์" หรือ "ไม่ได้บันทึกพาธไว้เลย"
    เดิมหน้าแอดมินยิง <img> ตรงๆ พอไฟล์หายจะได้แค่ไอคอนรูปแตก แอดมินไม่รู้ว่า
    เป็นที่รูปหาย หรือที่ระบบพัง — กล่องนี้บอกสาเหตุตรงๆ พร้อมพาธไว้ให้ไล่ต่อ

    @param string|null $path พาธที่เก็บในฐานข้อมูล (relative กับ storage/app/public)
--}}
@props(['path' => null])

<div style="aspect-ratio:4/3; border-radius:12px; border:2px dashed color-mix(in srgb, #e0a52e 45%, transparent);
            background:color-mix(in srgb, #e0a52e 8%, transparent);
            display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:16px; gap:4px;">

    <i class="fas fa-triangle-exclamation" style="font-size:26px; color:#e0a52e; margin-bottom:6px;"></i>

    <div style="font-size:13.5px; font-weight:700; color:var(--ink);">
        @if(filled($path))
            ไม่พบไฟล์รูปภาพบนเซิร์ฟเวอร์
        @else
            ไม่ได้บันทึกรูปภาพไว้
        @endif
    </div>

    <div style="font-size:12px; color:var(--ink2);">
        @if(filled($path))
            มีพาธในฐานข้อมูล แต่ไฟล์ถูกลบหรือย้ายไปแล้ว
        @else
            รายการนี้ถูกสร้างโดยไม่มีไฟล์แนบ (เช่น ข้อมูลตัวอย่าง)
        @endif
    </div>

    @if(filled($path))
        <code style="margin-top:8px; padding:4px 8px; border-radius:6px; font-size:10.5px; word-break:break-all;
                     background:color-mix(in srgb, #e0a52e 16%, transparent); color:var(--ink);">
            storage/{{ $path }}
        </code>
    @endif

    <div style="font-size:11.5px; color:var(--ink2); margin-top:8px;">
        <i class="fas fa-circle-info" style="margin-right:4px;"></i>ต้องให้ผู้ใช้ส่งเอกสารใหม่ก่อนจึงจะตรวจสอบได้
    </div>
</div>
