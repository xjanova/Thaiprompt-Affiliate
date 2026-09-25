{{--
    การ์ดงานรอรับ 1 งาน (ต้องอยู่ใน x-data="rdJobBoard(...)")
    ตัวแปร: $job (JobSummary จาก RiderJob::toApiSummary — ก่อนรับงาน จุดส่งเป็นพื้นที่หยาบ ไม่มีชื่อ/เบอร์)
--}}
@php
    $ui = \App\Support\RiderWebUi::class;
    $jobId = (int) $job['id'];
    $jobJs = [
        'id' => $jobId,
        'acceptUrl' => route('user.rider.jobs.accept', $jobId),
        'rejectUrl' => route('user.rider.jobs.reject', $jobId),
    ];
    $pickup = $job['pickup'] ?? [];
    $dropoff = $job['dropoff'] ?? [];
@endphp
<article class="tp-card rd-job" x-show="!isHidden({{ $jobId }})" x-transition.opacity>
    <div class="rd-row" style="justify-content:space-between; align-items:flex-start;">
        <div class="rd-row" style="gap:12px; flex:1 1 200px; min-width:0; flex-wrap:nowrap;">
            <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:18px;"><i class="fas {{ $ui::jobIcon($job['job_type'] ?? null) }}"></i></span>
            <div style="min-width:0;">
                <div style="font-weight:800; font-size:15px; color:var(--ink);">{{ $job['job_type_text'] ?? 'ส่งของ' }}</div>
                <div class="rd-muted rd-small">#{{ $job['job_number'] }} · {{ $ui::shortDate($job['created_at'] ?? null) }}</div>
            </div>
        </div>
        <div class="earn">
            <div class="rd-small rd-muted">รายได้ของคุณ</div>
            <div class="amt">฿{{ $ui::money($job['rider_earnings'] ?? 0) }}</div>
        </div>
    </div>

    <div class="rd-route">
        <div class="pt rd-tone-gold">
            <div class="t">รับของที่</div>
            <div class="v">{{ ($pickup['name'] ?? null) ?: 'จุดรับของ' }}</div>
            @if(!empty($pickup['address']))
                <div class="rd-small rd-muted">{{ \Illuminate\Support\Str::limit($pickup['address'], 90) }}</div>
            @endif
        </div>
        <div class="pt rd-tone-ok">
            <div class="t">ส่งที่</div>
            <div class="v">{{ ($dropoff['area'] ?? null) ?: 'พื้นที่ใกล้เคียง' }}</div>
            @if(!empty($dropoff['is_approximate']))
                <div class="rd-small rd-muted"><i class="fas fa-lock" style="font-size:10px;"></i> ชื่อ ที่อยู่ และเบอร์ลูกค้าจะแสดงหลังรับงาน</div>
            @endif
        </div>
    </div>

    <div class="meta">
        @if(isset($job['distance_to_pickup_km']) && $job['distance_to_pickup_km'] !== null)
            <span class="rd-meta"><i class="fas fa-location-arrow"></i> ห่างคุณ {{ $ui::distance($job['distance_to_pickup_km']) }}</span>
        @endif
        <span class="rd-meta"><i class="fas fa-route"></i> ระยะส่ง {{ $ui::distance($job['distance_km'] ?? null) }}</span>
        @if((int) ($job['estimated_duration_minutes'] ?? 0) > 0)
            <span class="rd-meta"><i class="far fa-clock"></i> ~{{ (int) $job['estimated_duration_minutes'] }} นาที</span>
        @endif
        @if(!empty($job['is_cod']))
            <span class="rd-pill rd-tone-warn"><i class="fas fa-money-bill-wave"></i> เก็บเงินปลายทาง ฿{{ $ui::money($job['cod_amount'] ?? 0) }}</span>
        @endif
    </div>

    @if(!empty($job['items_summary']))
        <div class="rd-small rd-muted"><i class="fas fa-box-open"></i> {{ \Illuminate\Support\Str::limit($job['items_summary'], 140) }}</div>
    @endif

    <div class="rd-row">
        <button type="button" class="rd-btn3d rd-tone-ok" style="flex:1 1 200px;"
                :class="{ 'is-disabled': !canAccept }"
                :disabled="busyId !== null"
                x-on:click="accept({{ \Illuminate\Support\Js::from($jobJs) }})">
            <i class="fas" :class="busyId === {{ $jobId }} ? 'fa-circle-notch rd-spin' : 'fa-hand-pointer'"></i>
            <span x-text="busyId === {{ $jobId }} ? 'กำลังรับงาน...' : 'รับงานนี้'">รับงานนี้</span>
        </button>
        <a href="{{ route('user.rider.jobs.show', $jobId) }}" class="tp-btn"><i class="fas fa-circle-info"></i> รายละเอียด</a>
        <button type="button" class="tp-btn" :disabled="busyId !== null" x-on:click="skip({{ \Illuminate\Support\Js::from($jobJs) }})">
            <i class="fas fa-eye-slash"></i> ไม่สนใจ
        </button>
    </div>
</article>
