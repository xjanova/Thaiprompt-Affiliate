{{--
    รายละเอียดงานไรเดอร์ (user.rider.jobs.show) — อ่านอย่างเดียว · ธีมโนวาเต็ม
    Controller: User\RiderController@showJob (403 ถ้าไม่ใช่งานของตัวเอง/ไม่ได้เสนอให้)
    ตัวแปร: rider, job, jobData (JobDetail), isMine, codAmount, pageTitle
    (2026-10-13) owner ตัดสิน: รับงาน/คืนงาน/อัปเดตสถานะ/ส่งมอบ/ส่งไม่สำเร็จ ทำในแอป Thai Prompt APP เท่านั้น
    → ไม่มีฟอร์มทำงานบนเว็บแล้ว (route เดิมตอบ APP_ONLY) — งานที่ยังเปิด/กำลังส่ง แสดงแบนเนอร์ดาวน์โหลดแอป
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'รายละเอียดงาน')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/rider.css') }}?v={{ filemtime(public_path('theme-nova/member/rider.css')) }}">
@endpush

@php
    $ui = \App\Support\RiderWebUi::class;
    $status = (string) ($jobData['status'] ?? '');
    $pickup = $jobData['pickup'] ?? [];
    $drop = $jobData['dropoff'] ?? [];
    $timeline = $jobData['timeline'] ?? [];
    $photos = $jobData['photos'] ?? [];
    $customerLive = $jobData['customer_live_location'] ?? null;
    $isOpen = $job->isOpen();
    $isActive = in_array($status, \App\Models\RiderJob::ACTIVE_STATUSES, true);
    $steps = array_map(fn ($s) => $s + ['sub' => $s['at'] ? $ui::time($s['at']) : null], $ui::progressSteps($status, $timeline));

    $completedAt = $ui::carbon($jobData['completed_at'] ?? null);
    $celebrate = $isMine && $status === 'completed' && $completedAt && $completedAt->gt(now()->subMinutes(30));

    $pickupMap = $ui::mapUrl($pickup['latitude'] ?? null, $pickup['longitude'] ?? null, $pickup['address'] ?? null);
    $pickupNav = $ui::directionsUrl($pickup['latitude'] ?? null, $pickup['longitude'] ?? null, $pickup['address'] ?? null);
    $dropApprox = !empty($drop['is_approximate']);
    $dropMap = $dropApprox ? null : $ui::mapUrl($drop['latitude'] ?? null, $drop['longitude'] ?? null, $drop['address'] ?? null);
    $dropNav = $dropApprox ? null : $ui::directionsUrl($drop['latitude'] ?? null, $drop['longitude'] ?? null, $drop['address'] ?? null);

    $timelineRows = array_filter([
        ['สร้างงาน', $timeline['created_at'] ?? null, 'plus', ''],
        ['รับงาน', $timeline['accepted_at'] ?? null, 'check', ''],
        ['รับของ', $timeline['picked_up_at'] ?? null, 'bag', ''],
        ['ส่งถึงลูกค้า', $timeline['delivered_at'] ?? null, 'home', 'is-in'],
        ['ปิดงานสำเร็จ', $timeline['completed_at'] ?? null, 'check', 'is-in'],
        ['ยกเลิก', $timeline['cancelled_at'] ?? null, 'x', 'is-due'],
        ['ส่งไม่สำเร็จ', $timeline['failed_at'] ?? null, 'info', 'is-due'],
    ], fn ($r) => !empty($r[1]));

    $photoRows = array_filter([
        ['รูปตอนรับของ', $photos['pickup'] ?? null],
        ['รูปยืนยันการส่ง', $photos['delivery'] ?? null],
        ['รูปประกอบส่งไม่สำเร็จ', $photos['failure'] ?? null],
    ], fn ($p) => !empty($p[1]));

    $tel = fn ($phone) => 'tel:'.preg_replace('/[^0-9+]/', '', (string) $phone);
@endphp

@section('content')
<div class="nvm-stack">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'jobs'])

    {{-- ── หัวงาน ─────────────────────────────────────────────── --}}
    <section class="nvm-ph" aria-labelledby="nvm-rd-job-h">
        <div class="nvm-ph__in">
            <div class="nvm-ph__txt">
                <a class="nvm-ph__back" href="{{ route('user.rider.jobs', ['tab' => $isActive ? 'current' : ($isOpen ? 'available' : 'history')]) }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กลับไปหน้างาน</a>
                <div class="nvm-eyebrow nvm-latin">{{ $jobData['job_type_text'] ?? 'ส่งของ' }} · {{ $ui::date($jobData['created_at'] ?? null) }}</div>
                <h1 id="nvm-rd-job-h">งาน <span class="nvm-num">#{{ $jobData['job_number'] }}</span></h1>
                <div class="nvm-bal">
                    <small>รายได้ของคุณ</small>
                    <b class="nvm-foil nvm-num">฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}</b>
                    <em>{{ $ui::distance($jobData['distance_km'] ?? null) }} · ~{{ (int) ($jobData['estimated_duration_minutes'] ?? 0) }} นาที</em>
                </div>
                <div class="nvm-pills">
                    <span class="nvm-pill {{ $ui::jobTone($status) === 'bad' ? 'nvm-pill--bad' : ($ui::jobTone($status) === 'ok' ? 'nvm-pill--ok' : 'nvm-pill--gold') }}">{{ $jobData['status_text'] ?? '' }}</span>
                    @if(!empty($jobData['is_cod']))
                        <span class="nvm-pill nvm-pill--warn"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>COD ฿{{ $ui::money($codAmount) }}</span>
                    @endif
                    @if($isActive && empty($jobData['gps_active']))
                        <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>GPS หยุดส่ง</span>
                    @endif
                </div>
            </div>
            <img class="nvm-ph__art" src="{{ asset('images/nova/icons/'.($ui::novaJobIcon($jobData['job_type'] ?? null) === 'basket' ? 'basket' : 'scooter').'.webp') }}" alt="" width="200" height="200">
        </div>
    </section>

    {{-- ── รับงาน/ทำงานส่งได้ในแอปเท่านั้น (งานที่ยังเปิดรับ หรือกำลังส่ง) ── --}}
    @if($isOpen || ($isMine && $isActive))
        @include('user.rider.partials.app-only-banner', [
            'appOnlyNote' => $isOpen
                ? 'งานนี้ยังรอไรเดอร์ — เปิดแอปแล้วกดรับงานจากรายการงานใกล้คุณ'
                : 'อัปเดตสถานะ ส่งมอบของ (สแกน QR / ถ่ายรูป) และส่งตำแหน่ง GPS ของงานนี้ในแอป',
        ])
    @endif

    {{-- ── ฉลองส่งสำเร็จ (หลังส่งภายใน 30 นาที) ── --}}
    @if($celebrate)
        <section class="nvm-card nvm-alert nvm-alert--ok" role="status">
            <img class="nvm-alert__img" src="{{ asset('images/nova/mascot/present.webp') }}" alt="" width="58" height="58" loading="lazy">
            <div class="nvm-alert__txt">
                <b>ส่งสำเร็จ! ขอบคุณที่ส่งของอย่างตั้งใจ</b>
                <p>{{ !empty($jobData['earnings_settled']) ? 'รายได้เข้ากระเป๋าเงินของคุณแล้ว' : 'ระบบกำลังบันทึกรายได้ จะเข้ากระเป๋าภายในไม่กี่นาที' }}</p>
            </div>
            <div class="nvm-alert__side">
                <b class="nvm-num">+฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}</b>
                <small><a class="nvm-link" href="{{ route('user.rider.earnings', ['period' => 'today']) }}">ดูรายได้วันนี้</a></small>
            </div>
        </section>
    @endif

    @if($isMine || !$isOpen)
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-prog-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Progress</small><h2 id="nvm-rd-prog-h">ความคืบหน้างาน</h2></div></div>
            @include('user.rider.partials.steps', ['steps' => $steps, 'stepsLabel' => 'ความคืบหน้างาน'])
        </section>
    @endif

    {{-- ── จุดรับ / จุดส่ง ───────────────────────────────────────── --}}
    <div class="nvm-rd-2">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-pick-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Pick up</small><h2 id="nvm-rd-pick-h">จุดรับของ</h2></div></div>
            <ul class="nvm-rd-route">
                <li>
                    <small>ร้าน / ผู้ส่ง</small>
                    <b>{{ ($pickup['name'] ?? null) ?: 'จุดรับของ' }}</b>
                    @if(!empty($pickup['address']))<p>{{ $pickup['address'] }}</p>@endif
                    @if(!empty($pickup['notes']))<p>หมายเหตุ: {{ $pickup['notes'] }}</p>@endif
                </li>
            </ul>
            <div class="nvm-rd-acts nvm-gap-t">
                @if(!empty($pickup['phone']))
                    <a href="{{ $tel($pickup['phone']) }}" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>โทรหาร้าน</a>
                @endif
                @if($pickupNav)
                    <a href="{{ $pickupNav }}" target="_blank" rel="noopener noreferrer" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>นำทาง</a>
                @endif
                @if($pickupMap)
                    <a href="{{ $pickupMap }}" target="_blank" rel="noopener noreferrer" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูแผนที่</a>
                @endif
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-drop-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Drop off</small><h2 id="nvm-rd-drop-h">จุดส่งของ</h2></div></div>
            @if($dropApprox)
                <ul class="nvm-rd-route">
                    <li>
                        <small>พื้นที่ส่ง</small>
                        <b>{{ ($drop['area'] ?? null) ?: 'พื้นที่ใกล้เคียง' }}</b>
                    </li>
                </ul>
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ชื่อ ที่อยู่ และเบอร์ลูกค้าจะแสดงหลังรับงาน เพื่อความปลอดภัยของลูกค้า</p>
            @else
                <ul class="nvm-rd-route">
                    <li>
                        <small>ลูกค้า</small>
                        <b>{{ ($drop['name'] ?? null) ?: 'ลูกค้า' }}</b>
                        @if(!empty($drop['address']))<p>{{ $drop['address'] }}</p>@endif
                        @if(!empty($drop['notes']))<p>หมายเหตุ: {{ $drop['notes'] }}</p>@endif
                    </li>
                </ul>
                <div class="nvm-rd-acts nvm-gap-t">
                    @if(!empty($drop['phone']))
                        <a href="{{ $tel($drop['phone']) }}" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>โทรหาลูกค้า</a>
                    @endif
                    @if($dropNav)
                        <a href="{{ $dropNav }}" target="_blank" rel="noopener noreferrer" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>นำทาง</a>
                    @endif
                    @if($dropMap)
                        <a href="{{ $dropMap }}" target="_blank" rel="noopener noreferrer" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูแผนที่</a>
                    @endif
                </div>
            @endif

            {{-- ตำแหน่งสดของลูกค้า — มีเฉพาะเมื่อลูกค้ายินยอมแชร์ + งานยังวิ่ง + อัปเดตไม่เกิน 5 นาที --}}
            @if($customerLive)
                @php $liveUrl = $ui::directionsUrl($customerLive['latitude'] ?? null, $customerLive['longitude'] ?? null); @endphp
                <div class="nvm-status nvm-gap-t">
                    <svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>
                    <span>ลูกค้าแชร์ตำแหน่งสดกับคุณ<small class="nvm-num">อัปเดต {{ $ui::time($customerLive['updated_at'] ?? null) }} น.</small></span>
                </div>
                @if($liveUrl)
                    <a href="{{ $liveUrl }}" target="_blank" rel="noopener noreferrer" class="nvm-btn nvm-btn--gold nvm-btn--sm nvm-gap-t"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>นำทางไปยังตำแหน่งลูกค้าตอนนี้</a>
                @endif
            @elseif($isMine && $isActive)
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ลูกค้ายังไม่ได้แชร์ตำแหน่งสด — ใช้ที่อยู่ด้านบนในการนำทาง</p>
            @endif
        </section>
    </div>

    {{-- ── เงินของงานนี้ ───────────────────────────────────────── --}}
    <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-fee-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Fees</small><h2 id="nvm-rd-fee-h">ค่าส่งและรายได้</h2></div></div>
        <div class="nvm-minis">
            <div class="nvm-mini"><small>ค่าส่งรวม</small><b class="nvm-num">฿{{ $ui::money($jobData['total_fee'] ?? 0) }}</b></div>
            <div class="nvm-mini"><small>ค่าบริการระบบ</small><b class="nvm-num">฿{{ $ui::money($jobData['platform_fee'] ?? 0) }}</b></div>
            <div class="nvm-mini {{ $codAmount > 0 ? '' : 'nvm-mini--wide' }}"><small>รายได้ของคุณ</small><b class="nvm-num is-in">฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}</b></div>
            @if($codAmount > 0)
                <div class="nvm-mini"><small>เก็บเงินปลายทาง</small><b class="nvm-num is-out">฿{{ $ui::money($codAmount) }}</b></div>
            @endif
        </div>
        @if($codAmount > 0)
            @if(!empty($jobData['cod']['settled_at']))
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>นำส่งยอดเงินปลายทางเข้าระบบแล้วเมื่อ {{ $ui::date($jobData['cod']['settled_at']) }}</p>
            @elseif($status === 'completed')
                <p class="nvm-note is-bad"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>ระบบกำลังหักยอดนำส่งเงินปลายทางจากกระเป๋าของคุณ — กรุณาคงเงินในกระเป๋าไว้ให้พอ</p>
            @else
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ต้องมีเงินในกระเป๋าพอสำหรับยอดนำส่ง (เงินปลายทาง − รายได้ของคุณ) ระบบหักอัตโนมัติหลังส่งสำเร็จ</p>
            @endif
        @endif
        @if(!empty($jobData['items_summary']) || !empty($jobData['description']))
            <div class="nvm-field nvm-gap-t">
                <span class="nvm-label">รายการของ</span>
                <p class="nvm-desc nvm-rd-pre">{{ $jobData['items_summary'] ?: $jobData['description'] }}</p>
            </div>
        @endif
    </section>

    {{-- ── ผลงาน: ส่งไม่สำเร็จ / ยกเลิก ─────────────────────────── --}}
    @if(!empty($jobData['failure']))
        <section class="nvm-card nvm-alert nvm-alert--bad" role="status">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt"><b>ส่งไม่สำเร็จ: {{ $jobData['failure']['reason_text'] ?? '' }}</b>@if(!empty($jobData['failure']['note']))<p>{{ $jobData['failure']['note'] }}</p>@endif</div>
        </section>
    @endif
    @if(!empty($jobData['cancellation']))
        <section class="nvm-card nvm-alert" role="status">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg></span>
            <div class="nvm-alert__txt"><b>งานถูกยกเลิก</b>@if(!empty($jobData['cancellation']['reason']))<p>{{ $jobData['cancellation']['reason'] }}</p>@endif</div>
        </section>
    @endif

    <div class="nvm-rd-2">
        {{-- ── รูปยืนยัน ─────────────────────────────────────── --}}
        @if(count($photoRows) > 0)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-photo-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Proof photos</small><h2 id="nvm-rd-photo-h">รูปยืนยัน</h2></div></div>
                <div class="nvm-rd-photos">
                    @foreach($photoRows as [$label, $url])
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $url }}" alt="{{ $label }}" class="nvm-rd-thumb" loading="lazy">
                            <small>{{ $label }}</small>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ── ไทม์ไลน์ ──────────────────────────────────────── --}}
        @if(count($timelineRows) > 0)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-tl-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Timeline</small><h2 id="nvm-rd-tl-h">ไทม์ไลน์</h2></div></div>
                <ul class="nvm-txs">
                    @foreach($timelineRows as [$label, $at, $icon, $tone])
                        <li class="nvm-tx">
                            <span class="nvm-tx__ic {{ $tone }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                            <div class="nvm-tx__t"><b>{{ $label }}</b></div>
                            <div class="nvm-tx__r"><time class="nvm-num">{{ $ui::date($at) }}</time></div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</div>
@endsection
