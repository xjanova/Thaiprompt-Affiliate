{{--
    แบนเนอร์ "รับงานและทำงานส่งได้ผ่านแอป Thai Prompt APP เท่านั้น" (owner ตัดสิน 2026-10-13) — ธีมโนวา
    ใช้ในหน้าเว็บไรเดอร์: แดชบอร์ด / งาน / รายละเอียดงาน — บนเว็บดูข้อมูลได้ ทำงานส่งไม่ได้
    ตัวแปร (ไม่บังคับ): $appOnlyNote — ข้อความรองเฉพาะหน้า
--}}
@php
    $appDownloadUrl = url((string) config('rider.app_download_path', '/app/download'));
@endphp
<section class="nvm-card nvm-alert nvm-alert--info" role="note">
    <img class="nvm-alert__img" src="{{ asset('images/nova/svc/rider.webp') }}" alt="" width="58" height="58" loading="lazy">
    <div class="nvm-alert__txt">
        <b>{{ \App\Exceptions\RiderJobException::APP_ONLY_MESSAGE }}</b>
        <p>{{ $appOnlyNote ?? 'บนเว็บดูงาน รายได้ เอกสาร และตั้งค่าได้ — เปิดรับงาน รับงาน และอัปเดตสถานะงานทำในแอป' }}</p>
    </div>
    <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ $appDownloadUrl }}"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>ดาวน์โหลดแอป</a>
</section>
