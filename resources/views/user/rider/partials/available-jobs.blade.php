{{--
    รายการงานรอรับ (อ่านอย่างเดียว) + สถานะว่าง/เหตุผลที่ยังไม่เห็นงาน (แดชบอร์ด + แท็บงานรอรับ)
    ตัวแปร: $availableJobs (JobSummary[]), $availableReason (null|not_approved|busy|offline|no_location),
            $canAcceptJobs (bool), $blockReason (array{code,message}|null)
    (2026-10-13) รับงาน/ไม่สนใจงาน ทำในแอป Thai Prompt APP เท่านั้น — การ์ดงานบนเว็บไม่มีปุ่มรับงาน
--}}
@php
    $reasonCards = [
        'not_approved' => ['icon' => 'fa-user-lock', 'title' => 'บัญชียังรับงานไม่ได้', 'text' => $blockReason['message'] ?? 'บัญชีไรเดอร์ยังไม่พร้อมใช้งาน'],
        'busy' => ['icon' => 'fa-person-biking', 'title' => 'ทำงานปัจจุบันให้เสร็จก่อน', 'text' => 'ส่งงานที่รับไว้ให้เสร็จในแอป แล้วงานใหม่ใกล้คุณจะแสดงที่นี่'],
        'offline' => ['icon' => 'fa-power-off', 'title' => 'คุณปิดรับงานอยู่', 'text' => 'เปิดรับงานในแอป Thai Prompt APP เพื่อดูงานใกล้คุณและรับแจ้งเตือนงานใหม่'],
        'no_location' => ['icon' => 'fa-location-crosshairs', 'title' => 'ยังไม่ทราบตำแหน่งของคุณ', 'text' => 'เปิดแอป Thai Prompt APP และอนุญาตตำแหน่ง ระบบจะค้นหางานใกล้คุณให้'],
    ];
@endphp
<div class="rd-stack">
    @if($availableReason !== null)
        @php $rc = $reasonCards[$availableReason] ?? $reasonCards['offline']; @endphp
        <div class="tp-card rd-empty">
            <div class="ic"><i class="fas {{ $rc['icon'] }}"></i></div>
            <div style="font-weight:800; font-size:16px; color:var(--ink);">{{ $rc['title'] }}</div>
            <p class="rd-muted" style="font-size:13px; margin:6px auto 0; max-width:420px;">{{ $rc['text'] }}</p>
        </div>
    @elseif(count($availableJobs) === 0)
        <div class="tp-card rd-empty">
            <div class="ic"><i class="fas fa-mug-hot"></i></div>
            <div style="font-weight:800; font-size:16px; color:var(--ink);">ยังไม่มีงานใกล้คุณตอนนี้</div>
            <p class="rd-muted" style="font-size:13px; margin:6px auto 0; max-width:420px;">แอป Thai Prompt APP จะแจ้งเตือนทันทีที่มีงานใหม่ในรัศมีของคุณ</p>
            <button type="button" class="tp-btn" style="margin-top:14px;" x-on:click="window.location.reload()"><i class="fas fa-rotate"></i> โหลดงานใหม่</button>
        </div>
    @else
        @if(!$canAcceptJobs && $blockReason)
            <div class="rd-alert rd-tone-warn">
                <i class="fas fa-circle-exclamation"></i>
                <div><b>ยังรับงานไม่ได้:</b> {{ $blockReason['message'] }}</div>
            </div>
        @endif
        <div class="rd-row" style="justify-content:space-between;">
            <span class="rd-muted rd-small">พบ {{ count($availableJobs) }} งานใกล้คุณ · เรียงจากใกล้ที่สุด · กดรับงานในแอป</span>
            <button type="button" class="tp-btn tp-btn-sm" x-on:click="window.location.reload()"><i class="fas fa-rotate"></i> รีเฟรช</button>
        </div>
        <div class="rd-grid" style="--rd-min:300px;">
            @foreach($availableJobs as $job)
                @include('user.rider.partials.job-card', ['job' => $job])
            @endforeach
        </div>
    @endif
</div>
