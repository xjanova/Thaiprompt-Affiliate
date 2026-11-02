{{--
    การ์ด "งานที่กำลังทำ" (แดชบอร์ด + แท็บงานปัจจุบัน) — อ่านอย่างเดียว · ธีมโนวา
    ตัวแปร: $jobData (JobDetail จาก RiderJob::toApiDetail ของไรเดอร์เจ้าของงาน)
    (2026-10-13) อัปเดตสถานะ/ส่งมอบ/ส่ง GPS ทำในแอป Thai Prompt APP เท่านั้น — เว็บมีแค่ลิงก์รายละเอียด + นำทาง
--}}
@php
    $ui = \App\Support\RiderWebUi::class;
    $cjId = (int) $jobData['id'];
    $cjStatus = (string) ($jobData['status'] ?? '');
    $cjPickup = $jobData['pickup'] ?? [];
    $cjDrop = $jobData['dropoff'] ?? [];
    $cjSteps = $ui::progressSteps($cjStatus, $jobData['timeline'] ?? []);
    $cjBeforePickup = in_array($cjStatus, ['accepted', 'picking_up'], true);
    $cjNavTarget = $cjBeforePickup
        ? $ui::directionsUrl($cjPickup['latitude'] ?? null, $cjPickup['longitude'] ?? null, $cjPickup['address'] ?? null)
        : $ui::directionsUrl($cjDrop['latitude'] ?? null, $cjDrop['longitude'] ?? null, $cjDrop['address'] ?? null);
@endphp
<section class="nvm-card nvm-box nvm-rd-live nvm-rd-job" aria-labelledby="nvm-rd-cj-{{ $cjId }}">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">On the road</small><h2 id="nvm-rd-cj-{{ $cjId }}">งานที่กำลังทำ</h2></div>
        <span class="nvm-chip {{ $ui::novaChip($cjStatus) }}"><span class="nvm-rd-dot" aria-hidden="true"></span>{{ $jobData['status_text'] ?? '' }}</span>
    </div>

    <div class="nvm-rd-job__head">
        <div class="nvm-rd-job__who">
            <span class="nvm-rd-ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaJobIcon($jobData['job_type'] ?? null) }}"/></svg></span>
            <div>
                <b>{{ $jobData['job_type_text'] ?? 'ส่งของ' }}</b>
                <small class="nvm-num">#{{ $jobData['job_number'] }} · รับงานเมื่อ {{ $ui::time($jobData['accepted_at'] ?? null) }} น.</small>
            </div>
        </div>
        <div class="nvm-rd-earn"><small>รายได้งานนี้</small><b class="nvm-num">฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}</b></div>
    </div>

    @include('user.rider.partials.steps', ['steps' => $cjSteps, 'stepsLabel' => 'ความคืบหน้างาน'])

    <ul class="nvm-rd-route">
        <li>
            <small>รับของที่</small>
            <b>{{ ($cjPickup['name'] ?? null) ?: 'จุดรับของ' }}</b>
            @if(!empty($cjPickup['address']))<p>{{ $cjPickup['address'] }}</p>@endif
        </li>
        <li>
            <small>ส่งให้</small>
            <b>{{ ($cjDrop['name'] ?? null) ?: (($cjDrop['area'] ?? null) ?: 'ลูกค้า') }}</b>
            @if(!empty($cjDrop['address']))<p>{{ $cjDrop['address'] }}</p>@endif
        </li>
    </ul>

    @if(!empty($jobData['is_cod']))
        <p class="nvm-note is-bad"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg><span>งานนี้<b>เก็บเงินปลายทาง ฿{{ $ui::money($jobData['cod_amount'] ?? 0) }}</b> — รับเงินสดจากลูกค้าก่อนส่งมอบของในแอป</span></p>
    @endif

    <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>อัปเดตสถานะ ส่งมอบของ และส่งตำแหน่ง GPS ของงานนี้ในแอป Thai Prompt APP</p>

    <div class="nvm-rd-acts">
        <a href="{{ route('user.rider.jobs.show', $cjId) }}" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ดูรายละเอียดงาน</a>
        @if($cjNavTarget)
            <a href="{{ $cjNavTarget }}" target="_blank" rel="noopener noreferrer" class="nvm-btn nvm-btn--line nvm-btn--sm">
                <svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>{{ $cjBeforePickup ? 'นำทางไปร้าน' : 'นำทางไปลูกค้า' }}
            </a>
        @endif
    </div>
</section>
