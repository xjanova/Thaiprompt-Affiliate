{{--
 | ไกด์มาสคอต "น้องพร้อม" (สาวไทยชุดไทยโกธิค) — ทักทายตอนเข้าเว็บ พูดตามการ์ดที่ชี้ แล้วย่อเป็นปุ่มรูปหน้ามุมซ้ายล่าง
 | (มุมขวาล่างเป็นของผู้ช่วย AI "น้อง Eve") · พฤติกรรมทั้งหมดอยู่ใน public/theme-nova/nova.js
 | ผู้ใช้กดปิดได้ (จำใน localStorage) · จอสัมผัสเริ่มแบบย่อเพื่อไม่บังเนื้อหา
 --}}
@php
    $rgTips = [
        'เลื่อนลงไปดูดีลเด็ดที่ยืนยันราคาแล้ววันนี้ได้เลยนะคะ',
        'อยากถามอะไร กดผู้ช่วยมุมขวาล่างคุยกับน้อง Eve ได้เลยค่ะ',
        'ของสดใกล้บ้านอยู่ในตลาดสด ไรเดอร์ในพื้นที่ส่งให้ถึงมือค่ะ',
        'ชี้การ์ดไหน น้องพร้อมเล่าให้ฟังทันทีค่ะ',
    ];
@endphp
<div id="nv-guide" class="nv-guide"
     data-welcome="{{ asset('images/nova/mascot/welcome.webp') }}"
     data-present="{{ asset('images/nova/mascot/present.webp') }}"
     data-tips="{{ implode('|', $rgTips) }}">
    <div class="nv-guide__bubble" id="nv-guide-bubble" role="status" aria-live="polite">
        <button type="button" class="nv-guide__x" id="nv-guide-x" aria-label="ปิดคำแนะนำของน้องพร้อม">&times;</button>
        <b>น้องพร้อม <small>ผู้ช่วย Thai Prompt</small></b>
        <span id="nv-guide-say">สวัสดีค่ะ ยินดีต้อนรับสู่ Thai Prompt — ชี้หรือแตะการ์ดเพื่อดูบริการได้เลยนะคะ</span>
    </div>
    <button type="button" class="nv-guide__fig" id="nv-guide-fig" aria-label="น้องพร้อม ผู้ช่วยแนะนำบริการ">
        {{-- มือถือ (< 720px) เริ่มแบบย่อเป็นรูปหน้า ไม่เคยเห็นตัวเต็ม → ให้ <source> แจกภาพโปร่ง 1 พิกเซลแทน (ประหยัด ~230KB) --}}
        <picture>
            <source media="(max-width: 719px)" srcset="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==">
            <img class="nv-guide__full" id="nv-guide-full" src="{{ asset('images/nova/mascot/welcome.webp') }}" alt="" width="443" height="720" draggable="false" decoding="async">
        </picture>
        <span class="nv-guide__face"><img src="{{ asset('images/nova/mascot/face.webp') }}" alt="" width="64" height="64" loading="lazy" decoding="async"></span>
    </button>
</div>
