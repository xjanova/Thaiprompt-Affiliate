{{--
    แบนเนอร์ "รับงานและทำงานส่งได้ผ่านแอป Thai Prompt APP เท่านั้น" (owner ตัดสิน 2026-10-13)
    ใช้ในหน้าเว็บไรเดอร์: แดชบอร์ด / งาน / รายละเอียดงาน — บนเว็บดูข้อมูลได้ ทำงานส่งไม่ได้
    ตัวแปร (ไม่บังคับ): $appOnlyNote — ข้อความรองเฉพาะหน้า
--}}
@php
    $appDownloadUrl = url((string) config('rider.app_download_path', '/app/download'));
@endphp
<div role="note"
     class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-amber-900 shadow-sm backdrop-blur-sm sm:flex-row sm:items-center dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-lg text-white shadow-md" aria-hidden="true">
        <i class="fas fa-mobile-screen-button"></i>
    </span>
    <div class="min-w-0 flex-1">
        <p class="font-bold leading-snug">{{ \App\Exceptions\RiderJobException::APP_ONLY_MESSAGE }}</p>
        <p class="mt-1 text-sm leading-relaxed text-amber-800 dark:text-amber-200/80">
            {{ $appOnlyNote ?? 'บนเว็บดูงาน รายได้ เอกสาร และตั้งค่าได้ — เปิดรับงาน รับงาน และอัปเดตสถานะงานทำในแอป' }}
        </p>
    </div>
    <a href="{{ $appDownloadUrl }}"
       class="inline-flex min-h-[44px] w-full flex-none items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-2.5 text-sm font-bold text-white no-underline shadow-md transition hover:from-amber-600 hover:to-orange-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 sm:w-auto dark:focus:ring-offset-gray-900">
        <i class="fas fa-download" aria-hidden="true"></i> ดาวน์โหลดแอป
    </a>
</div>
