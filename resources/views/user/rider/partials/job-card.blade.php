{{--
    การ์ดงานรอรับ 1 งาน (อ่านอย่างเดียว — รับงานในแอป Thai Prompt APP เท่านั้น ตั้งแต่ 2026-10-13) — ธีมโนวา
    ตัวแปร: $job (JobSummary จาก RiderJob::toApiSummary — ก่อนรับงาน จุดส่งเป็นพื้นที่หยาบ ไม่มีชื่อ/เบอร์)
--}}
@php
    $ui = \App\Support\RiderWebUi::class;
    $jobId = (int) $job['id'];
    $pickup = $job['pickup'] ?? [];
    $dropoff = $job['dropoff'] ?? [];
@endphp
<article class="nvm-card nvm-box nvm-rd-job">
    <div class="nvm-rd-job__head">
        <div class="nvm-rd-job__who">
            <span class="nvm-rd-ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaJobIcon($job['job_type'] ?? null) }}"/></svg></span>
            <div>
                <b>{{ $job['job_type_text'] ?? 'ส่งของ' }}</b>
                <small class="nvm-num">#{{ $job['job_number'] }} · {{ $ui::shortDate($job['created_at'] ?? null) }}</small>
            </div>
        </div>
        <div class="nvm-rd-earn"><small>รายได้ของคุณ</small><b class="nvm-num">฿{{ $ui::money($job['rider_earnings'] ?? 0) }}</b></div>
    </div>

    <ul class="nvm-rd-route">
        <li>
            <small>รับของที่</small>
            <b>{{ ($pickup['name'] ?? null) ?: 'จุดรับของ' }}</b>
            @if(!empty($pickup['address']))<p>{{ \Illuminate\Support\Str::limit($pickup['address'], 90) }}</p>@endif
        </li>
        <li>
            <small>ส่งที่</small>
            <b>{{ ($dropoff['area'] ?? null) ?: 'พื้นที่ใกล้เคียง' }}</b>
            @if(!empty($dropoff['is_approximate']))<p>ชื่อ ที่อยู่ และเบอร์ลูกค้าจะแสดงหลังรับงาน</p>@endif
        </li>
    </ul>

    <div class="nvm-rd-meta">
        @if(isset($job['distance_to_pickup_km']) && $job['distance_to_pickup_km'] !== null)
            <span class="nvm-chip"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>ห่างคุณ {{ $ui::distance($job['distance_to_pickup_km']) }}</span>
        @endif
        <span class="nvm-chip"><svg class="nvm-ico"><use href="#nvm-i-scooter"/></svg>ระยะส่ง {{ $ui::distance($job['distance_km'] ?? null) }}</span>
        @if((int) ($job['estimated_duration_minutes'] ?? 0) > 0)
            <span class="nvm-chip"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>~{{ (int) $job['estimated_duration_minutes'] }} นาที</span>
        @endif
        @if(!empty($job['is_cod']))
            <span class="nvm-chip nvm-chip--warn"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>เก็บเงินปลายทาง ฿{{ $ui::money($job['cod_amount'] ?? 0) }}</span>
        @endif
    </div>

    @if(!empty($job['items_summary']))
        <p class="nvm-desc">{{ \Illuminate\Support\Str::limit($job['items_summary'], 140) }}</p>
    @endif

    <div class="nvm-rd-foot">
        <span><svg class="nvm-ico"><use href="#nvm-i-scooter"/></svg>รับงานได้ในแอป Thai Prompt APP</span>
        <a href="{{ route('user.rider.jobs.show', $jobId) }}" class="nvm-btn nvm-btn--line nvm-btn--sm">รายละเอียด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
    </div>
</article>
