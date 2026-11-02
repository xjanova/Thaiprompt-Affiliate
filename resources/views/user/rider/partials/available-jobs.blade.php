{{--
    รายการงานรอรับ (อ่านอย่างเดียว) + สถานะว่าง/เหตุผลที่ยังไม่เห็นงาน (แดชบอร์ด + แท็บงานรอรับ) — ธีมโนวา
    ตัวแปร: $availableJobs (JobSummary[]), $availableReason (null|not_approved|busy|offline|no_location),
            $canAcceptJobs (bool), $blockReason (array{code,message}|null)
    (2026-10-13) รับงาน/ไม่สนใจงาน ทำในแอป Thai Prompt APP เท่านั้น — การ์ดงานบนเว็บไม่มีปุ่มรับงาน
--}}
@php
    $reasonCards = [
        'not_approved' => ['img' => 'deco/umbrella', 'title' => 'บัญชียังรับงานไม่ได้', 'text' => $blockReason['message'] ?? 'บัญชีไรเดอร์ยังไม่พร้อมใช้งาน'],
        'busy' => ['img' => 'deco/scooter', 'title' => 'ทำงานปัจจุบันให้เสร็จก่อน', 'text' => 'ส่งงานที่รับไว้ให้เสร็จในแอป แล้วงานใหม่ใกล้คุณจะแสดงที่นี่'],
        'offline' => ['img' => 'deco/lantern', 'title' => 'คุณปิดรับงานอยู่', 'text' => 'เปิดรับงานในแอป Thai Prompt APP เพื่อดูงานใกล้คุณและรับแจ้งเตือนงานใหม่'],
        'no_location' => ['img' => 'deco/lamp', 'title' => 'ยังไม่ทราบตำแหน่งของคุณ', 'text' => 'เปิดแอป Thai Prompt APP และอนุญาตตำแหน่ง ระบบจะค้นหางานใกล้คุณให้'],
    ];
@endphp
@if($availableReason !== null)
    @php $rc = $reasonCards[$availableReason] ?? $reasonCards['offline']; @endphp
    <div class="nvm-card nvm-empty">
        <img src="{{ asset('images/nova/'.$rc['img'].'.webp') }}" alt="" width="86" height="86" loading="lazy">
        <b>{{ $rc['title'] }}</b>
        {{ $rc['text'] }}
    </div>
@elseif(count($availableJobs) === 0)
    <div class="nvm-card nvm-empty">
        <img src="{{ asset('images/nova/deco/lotus.webp') }}" alt="" width="86" height="86" loading="lazy">
        <b>ยังไม่มีงานใกล้คุณตอนนี้</b>
        แอป Thai Prompt APP จะแจ้งเตือนทันทีที่มีงานใหม่ในรัศมีของคุณ
        <div><button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-data x-on:click="window.location.reload()"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>โหลดงานใหม่</button></div>
    </div>
@else
    @if(!$canAcceptJobs && $blockReason)
        <section class="nvm-card nvm-alert" role="status">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt"><b>ยังรับงานไม่ได้</b><p>{{ $blockReason['message'] }}</p></div>
        </section>
    @endif
    <div class="nvm-rd-bar">
        <span class="nvm-help">พบ {{ count($availableJobs) }} งานใกล้คุณ · เรียงจากใกล้ที่สุด · กดรับงานในแอป</span>
        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-data x-on:click="window.location.reload()"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>รีเฟรช</button>
    </div>
    <div class="nvm-rd-jobs">
        @foreach($availableJobs as $job)
            @include('user.rider.partials.job-card', ['job' => $job])
        @endforeach
    </div>
@endif
