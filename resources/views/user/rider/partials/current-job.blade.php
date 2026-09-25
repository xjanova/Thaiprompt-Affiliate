{{--
    การ์ด "งานที่กำลังทำ" (แดชบอร์ด + แท็บงานปัจจุบัน)
    ตัวแปร: $jobData (JobDetail จาก RiderJob::toApiDetail ของไรเดอร์เจ้าของงาน)
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
<section class="tp-card rd-stack" style="box-shadow:var(--card-shadow), 0 0 0 2px color-mix(in srgb, var(--accent1) 50%, transparent);">
    <div class="rd-row" style="justify-content:space-between;">
        <div class="rd-row" style="gap:10px;">
            <span class="rd-dot live rd-tone-ok"></span>
            <span class="rd-h2">งานที่กำลังทำ</span>
        </div>
        <span class="rd-pill solid rd-tone-{{ $ui::jobTone($cjStatus) }}">{{ $jobData['status_text'] ?? '' }}</span>
    </div>

    <div class="rd-row" style="justify-content:space-between; align-items:flex-end;">
        <div style="min-width:0;">
            <div style="font-weight:800; font-size:16px; color:var(--ink);"><i class="fas {{ $ui::jobIcon($jobData['job_type'] ?? null) }}" style="color:var(--deep1);"></i> {{ $jobData['job_type_text'] ?? 'ส่งของ' }}</div>
            <div class="rd-muted rd-small">#{{ $jobData['job_number'] }} · รับงานเมื่อ {{ $ui::time($jobData['accepted_at'] ?? null) }} น.</div>
        </div>
        <div style="text-align:right;">
            <div class="rd-small rd-muted">รายได้งานนี้</div>
            <div class="rd-money" style="font-size:26px; line-height:1;">฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}</div>
        </div>
    </div>

    <div class="rd-steps" aria-label="ความคืบหน้างาน">
        @foreach($cjSteps as $step)
            <div class="st {{ $step['state'] }}"><div class="bar"></div><div class="lb">{{ $step['label'] }}</div></div>
        @endforeach
    </div>

    <div class="rd-route">
        <div class="pt rd-tone-gold">
            <div class="t">รับของที่</div>
            <div class="v">{{ ($cjPickup['name'] ?? null) ?: 'จุดรับของ' }}</div>
            @if(!empty($cjPickup['address']))<div class="rd-small rd-muted">{{ $cjPickup['address'] }}</div>@endif
        </div>
        <div class="pt rd-tone-ok">
            <div class="t">ส่งให้</div>
            <div class="v">{{ ($cjDrop['name'] ?? null) ?: (($cjDrop['area'] ?? null) ?: 'ลูกค้า') }}</div>
            @if(!empty($cjDrop['address']))<div class="rd-small rd-muted">{{ $cjDrop['address'] }}</div>@endif
        </div>
    </div>

    @if(!empty($jobData['is_cod']))
        <div class="rd-alert rd-tone-warn">
            <i class="fas fa-money-bill-wave"></i>
            <div>งานนี้<b>เก็บเงินปลายทาง ฿{{ $ui::money($jobData['cod_amount'] ?? 0) }}</b> — รับเงินสดจากลูกค้าก่อนกดส่งสำเร็จ</div>
        </div>
    @endif

    <div class="rd-row">
        <a href="{{ route('taladsod.rider.active-job', $cjId) }}" class="rd-btn3d rd-tone-gold" style="flex:1 1 220px;">
            <i class="fas fa-map-location-dot"></i> ไปหน้าทำงาน (แผนที่ + GPS)
        </a>
        <a href="{{ route('user.rider.jobs.show', $cjId) }}" class="tp-btn"><i class="fas fa-list-check"></i> อัปเดตสถานะ</a>
        @if($cjNavTarget)
            <a href="{{ $cjNavTarget }}" target="_blank" rel="noopener noreferrer" class="tp-btn">
                <i class="fas fa-diamond-turn-right"></i> {{ $cjBeforePickup ? 'นำทางไปร้าน' : 'นำทางไปลูกค้า' }}
            </a>
        @endif
    </div>
</section>
