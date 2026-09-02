{{--
    กล่องแทนรูป KYC ที่เปิดไม่ขึ้น (สำหรับหน้าแอดมินเท่านั้น)

    ใช้เมื่อ "มีพาธในฐานข้อมูล แต่ไม่มีไฟล์จริงบนดิสก์" หรือ "ไม่ได้บันทึกพาธไว้เลย"
    เดิมหน้าแอดมินยิง <img> ตรงๆ พอไฟล์หายจะได้แค่ไอคอนรูปแตก แอดมินไม่รู้ว่า
    เป็นที่รูปหาย หรือที่ระบบพัง — กล่องนี้บอกสาเหตุตรงๆ พร้อมพาธไว้ให้ไล่ต่อ

    @param string|null $path พาธที่เก็บในฐานข้อมูล (relative กับ storage/app/public)
--}}
@props(['path' => null])

<div class="aspect-[4/3] border-2 border-dashed border-amber-300 dark:border-amber-700 rounded-lg
            bg-amber-50 dark:bg-amber-900/20 flex flex-col items-center justify-center text-center p-4">
    <i class="fas fa-exclamation-triangle text-3xl text-amber-500 dark:text-amber-400 mb-3"></i>

    <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
        @if(filled($path))
            ไม่พบไฟล์รูปภาพบนเซิร์ฟเวอร์
        @else
            ไม่ได้บันทึกรูปภาพไว้
        @endif
    </p>

    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
        @if(filled($path))
            มีพาธในฐานข้อมูล แต่ไฟล์ถูกลบหรือย้ายไปแล้ว
        @else
            รายการนี้ถูกสร้างโดยไม่มีไฟล์แนบ (เช่น ข้อมูลตัวอย่าง)
        @endif
    </p>

    @if(filled($path))
        <code class="mt-2 px-2 py-1 rounded bg-amber-100 dark:bg-amber-900/40
                     text-[10px] text-amber-800 dark:text-amber-200 break-all">
            storage/{{ $path }}
        </code>
    @endif

    <p class="text-xs text-amber-700 dark:text-amber-300 mt-3">
        <i class="fas fa-info-circle mr-1"></i>ต้องให้ผู้ใช้ส่งเอกสารใหม่ก่อนจึงจะตรวจสอบได้
    </p>
</div>
