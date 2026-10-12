{{--
    รายละเอียดงานไรเดอร์ (user.rider.jobs.show) — อ่านอย่างเดียว
    Controller: User\RiderController@showJob (403 ถ้าไม่ใช่งานของตัวเอง/ไม่ได้เสนอให้)
    ตัวแปร: rider, job, jobData (JobDetail), isMine, codAmount, pageTitle
    (2026-10-13) owner ตัดสิน: รับงาน/คืนงาน/อัปเดตสถานะ/ส่งมอบ/ส่งไม่สำเร็จ ทำในแอป Thai Prompt APP เท่านั้น
    → ไม่มีฟอร์มทำงานบนเว็บแล้ว (route เดิมตอบ APP_ONLY) — งานที่ยังเปิด/กำลังส่ง แสดงแบนเนอร์ดาวน์โหลดแอป
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'รายละเอียดงาน')

@push('styles')
    @include('user.rider.partials.styles')
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
    $steps = $ui::progressSteps($status, $timeline);

    $completedAt = $ui::carbon($jobData['completed_at'] ?? null);
    $celebrate = $isMine && $status === 'completed' && $completedAt && $completedAt->gt(now()->subMinutes(30));

    $pickupMap = $ui::mapUrl($pickup['latitude'] ?? null, $pickup['longitude'] ?? null, $pickup['address'] ?? null);
    $pickupNav = $ui::directionsUrl($pickup['latitude'] ?? null, $pickup['longitude'] ?? null, $pickup['address'] ?? null);
    $dropApprox = !empty($drop['is_approximate']);
    $dropMap = $dropApprox ? null : $ui::mapUrl($drop['latitude'] ?? null, $drop['longitude'] ?? null, $drop['address'] ?? null);
    $dropNav = $dropApprox ? null : $ui::directionsUrl($drop['latitude'] ?? null, $drop['longitude'] ?? null, $drop['address'] ?? null);

    $timelineRows = array_filter([
        ['สร้างงาน', $timeline['created_at'] ?? null, 'fa-circle-plus'],
        ['รับงาน', $timeline['accepted_at'] ?? null, 'fa-hand-pointer'],
        ['รับของ', $timeline['picked_up_at'] ?? null, 'fa-box'],
        ['ส่งถึงลูกค้า', $timeline['delivered_at'] ?? null, 'fa-house-circle-check'],
        ['ปิดงานสำเร็จ', $timeline['completed_at'] ?? null, 'fa-circle-check'],
        ['ยกเลิก', $timeline['cancelled_at'] ?? null, 'fa-ban'],
        ['ส่งไม่สำเร็จ', $timeline['failed_at'] ?? null, 'fa-triangle-exclamation'],
    ], fn ($r) => !empty($r[1]));
@endphp

@section('content')
<div class="rd-scope">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'jobs'])

    <a href="{{ route('user.rider.jobs', ['tab' => $isActive ? 'current' : ($isOpen ? 'available' : 'history')]) }}" class="rd-link rd-small" style="align-self:flex-start;">
        <i class="fas fa-arrow-left"></i> กลับไปหน้างาน
    </a>

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
        <section class="tp-card rd-celebrate">
            <div class="burst" aria-hidden="true"></div>
            <div style="font-size:40px;">🎉</div>
            <div class="rd-h2" style="justify-content:center; margin-top:6px;">ส่งสำเร็จ! ขอบคุณที่ส่งของอย่างตั้งใจ</div>
            <div class="big" style="margin-top:10px;">+฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}</div>
            <div class="rd-muted" style="font-size:13px; margin-top:8px;">
                @if(!empty($jobData['earnings_settled']))
                    รายได้เข้ากระเป๋าเงินของคุณแล้ว
                @else
                    ระบบกำลังบันทึกรายได้ จะเข้ากระเป๋าภายในไม่กี่นาที
                @endif
            </div>
            <div class="rd-row" style="justify-content:center; margin-top:16px;">
                <a href="{{ route('user.rider.earnings', ['period' => 'today']) }}" class="rd-btn3d rd-tone-ok"><i class="fas fa-coins"></i> ดูรายได้วันนี้</a>
                <a href="{{ route('user.rider.jobs', ['tab' => 'history']) }}" class="tp-btn"><i class="fas fa-clock-rotate-left"></i> ประวัติงาน</a>
            </div>
        </section>
    @endif

    {{-- ── หัวงาน ─────────────────────────────────────────────── --}}
    <section class="tp-card rd-hero">
        <div class="rd-hero-in">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:18px; font-size:22px;"><i class="fas {{ $ui::jobIcon($jobData['job_type'] ?? null) }}"></i></span>
            <div style="flex:1 1 200px; min-width:0;">
                <div class="rd-muted rd-small">{{ $jobData['job_type_text'] ?? 'ส่งของ' }} · สร้างเมื่อ {{ $ui::date($jobData['created_at'] ?? null) }}</div>
                <h1 class="rd-h1">งาน #{{ $jobData['job_number'] }}</h1>
                <div class="rd-row" style="gap:8px; margin-top:6px;">
                    <span class="rd-pill solid rd-tone-{{ $ui::jobTone($status) }}">{{ $jobData['status_text'] ?? '' }}</span>
                    @if(!empty($jobData['is_cod']))
                        <span class="rd-pill rd-tone-warn"><i class="fas fa-money-bill-wave"></i> COD ฿{{ $ui::money($codAmount) }}</span>
                    @endif
                    @if($isActive && empty($jobData['gps_active']))
                        <span class="rd-pill rd-tone-bad"><i class="fas fa-satellite-dish"></i> GPS หยุดส่ง</span>
                    @endif
                </div>
            </div>
            <div style="text-align:right;">
                <div class="rd-small rd-muted">รายได้ของคุณ</div>
                <div class="rd-money" style="font-size:clamp(28px, 7vw, 36px); line-height:1;">฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}</div>
                <div class="rd-small rd-muted" style="margin-top:4px;">{{ $ui::distance($jobData['distance_km'] ?? null) }} · ~{{ (int) ($jobData['estimated_duration_minutes'] ?? 0) }} นาที</div>
            </div>
        </div>
    </section>

    @if($isMine || !$isOpen)
        <section class="tp-card">
            <div class="rd-steps" aria-label="ความคืบหน้างาน">
                @foreach($steps as $step)
                    <div class="st {{ $step['state'] }}">
                        <div class="bar"></div>
                        <div class="lb">{{ $step['label'] }}@if($step['at'])<br><span class="rd-muted" style="font-weight:600;">{{ $ui::time($step['at']) }}</span>@endif</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── จุดรับ / จุดส่ง ───────────────────────────────────────── --}}
    <div class="rd-grid" style="--rd-min:280px;">
        <section class="tp-card rd-stack rd-tone-gold">
            <div class="rd-row" style="gap:10px;">
                <span class="rd-dot"></span>
                <h2 class="rd-h2">จุดรับของ</h2>
            </div>
            <div>
                <div style="font-weight:800; font-size:15px;">{{ ($pickup['name'] ?? null) ?: 'จุดรับของ' }}</div>
                @if(!empty($pickup['address']))<div class="rd-muted" style="font-size:13px; margin-top:3px; overflow-wrap:anywhere;">{{ $pickup['address'] }}</div>@endif
                @if(!empty($pickup['notes']))<div class="rd-small" style="margin-top:6px;"><i class="fas fa-note-sticky" style="color:var(--accent1);"></i> {{ $pickup['notes'] }}</div>@endif
            </div>
            <div class="rd-row">
                @if(!empty($pickup['phone']))
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $pickup['phone']) }}" class="tp-btn"><i class="fas fa-phone"></i> โทรหาร้าน</a>
                @endif
                @if($pickupNav)
                    <a href="{{ $pickupNav }}" target="_blank" rel="noopener noreferrer" class="tp-btn"><i class="fas fa-diamond-turn-right"></i> นำทาง</a>
                @endif
                @if($pickupMap)
                    <a href="{{ $pickupMap }}" target="_blank" rel="noopener noreferrer" class="tp-btn"><i class="fas fa-map"></i> ดูแผนที่</a>
                @endif
            </div>
        </section>

        <section class="tp-card rd-stack rd-tone-ok">
            <div class="rd-row" style="gap:10px;">
                <span class="rd-dot"></span>
                <h2 class="rd-h2">จุดส่งของ</h2>
            </div>
            @if($dropApprox)
                <div>
                    <div style="font-weight:800; font-size:15px;">{{ ($drop['area'] ?? null) ?: 'พื้นที่ใกล้เคียง' }}</div>
                    <div class="rd-muted rd-small" style="margin-top:4px;"><i class="fas fa-lock"></i> ชื่อ ที่อยู่ และเบอร์ลูกค้าจะแสดงหลังรับงาน เพื่อความปลอดภัยของลูกค้า</div>
                </div>
            @else
                <div>
                    <div style="font-weight:800; font-size:15px;">{{ ($drop['name'] ?? null) ?: 'ลูกค้า' }}</div>
                    @if(!empty($drop['address']))<div class="rd-muted" style="font-size:13px; margin-top:3px; overflow-wrap:anywhere;">{{ $drop['address'] }}</div>@endif
                    @if(!empty($drop['notes']))<div class="rd-small" style="margin-top:6px;"><i class="fas fa-note-sticky" style="color:var(--accent1);"></i> {{ $drop['notes'] }}</div>@endif
                </div>
                <div class="rd-row">
                    @if(!empty($drop['phone']))
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $drop['phone']) }}" class="tp-btn"><i class="fas fa-phone"></i> โทรหาลูกค้า</a>
                    @endif
                    @if($dropNav)
                        <a href="{{ $dropNav }}" target="_blank" rel="noopener noreferrer" class="tp-btn"><i class="fas fa-diamond-turn-right"></i> นำทาง</a>
                    @endif
                    @if($dropMap)
                        <a href="{{ $dropMap }}" target="_blank" rel="noopener noreferrer" class="tp-btn"><i class="fas fa-map"></i> ดูแผนที่</a>
                    @endif
                </div>
            @endif

            {{-- ตำแหน่งสดของลูกค้า — มีเฉพาะเมื่อลูกค้ายินยอมแชร์ + งานยังวิ่ง + อัปเดตไม่เกิน 5 นาที --}}
            @if($customerLive)
                @php $liveUrl = $ui::directionsUrl($customerLive['latitude'] ?? null, $customerLive['longitude'] ?? null); @endphp
                <div class="rd-alert rd-tone-info">
                    <i class="fas fa-location-crosshairs"></i>
                    <div>
                        <b>ลูกค้าแชร์ตำแหน่งสดกับคุณ</b> · อัปเดต {{ $ui::time($customerLive['updated_at'] ?? null) }} น.
                        @if($liveUrl)
                            <div style="margin-top:6px;"><a href="{{ $liveUrl }}" target="_blank" rel="noopener noreferrer" class="rd-link"><i class="fas fa-diamond-turn-right"></i> นำทางไปยังตำแหน่งลูกค้าตอนนี้</a></div>
                        @endif
                    </div>
                </div>
            @elseif($isMine && $isActive)
                <div class="rd-hint"><i class="fas fa-circle-info"></i> ลูกค้ายังไม่ได้แชร์ตำแหน่งสด — ใช้ที่อยู่ด้านบนในการนำทาง</div>
            @endif
        </section>
    </div>

    {{-- ── เงินของงานนี้ ───────────────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <h2 class="rd-h2"><i class="fas fa-coins" style="color:var(--accent1);"></i> ค่าส่งและรายได้</h2>
        <div class="rd-grid" style="--rd-min:150px; gap:10px;">
            <div class="rd-meta" style="flex-direction:column; align-items:flex-start; padding:12px 14px;">
                <span>ค่าส่งรวม</span><span class="rd-num" style="font-size:18px; color:var(--ink);">฿{{ $ui::money($jobData['total_fee'] ?? 0) }}</span>
            </div>
            <div class="rd-meta" style="flex-direction:column; align-items:flex-start; padding:12px 14px;">
                <span>ค่าบริการระบบ</span><span class="rd-num" style="font-size:18px; color:var(--ink);">฿{{ $ui::money($jobData['platform_fee'] ?? 0) }}</span>
            </div>
            <div class="rd-meta" style="flex-direction:column; align-items:flex-start; padding:12px 14px; box-shadow:var(--inset-sm), 0 0 0 2px color-mix(in srgb, var(--accent1) 45%, transparent);">
                <span>รายได้ของคุณ</span><span class="rd-money" style="font-size:20px;">฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}</span>
            </div>
            @if($codAmount > 0)
                <div class="rd-meta" style="flex-direction:column; align-items:flex-start; padding:12px 14px;">
                    <span>เก็บเงินปลายทาง</span><span class="rd-num" style="font-size:18px; color:var(--rd-warn);">฿{{ $ui::money($codAmount) }}</span>
                </div>
            @endif
        </div>
        @if($codAmount > 0)
            <div class="rd-hint">
                @if(!empty($jobData['cod']['settled_at']))
                    <i class="fas fa-circle-check" style="color:var(--rd-ok);"></i> นำส่งยอดเงินปลายทางเข้าระบบแล้วเมื่อ {{ $ui::date($jobData['cod']['settled_at']) }}
                @elseif($status === 'completed')
                    <i class="fas fa-hourglass-half" style="color:var(--rd-warn);"></i> ระบบกำลังหักยอดนำส่งเงินปลายทางจากกระเป๋าของคุณ — กรุณาคงเงินในกระเป๋าไว้ให้พอ
                @else
                    <i class="fas fa-circle-info"></i> ต้องมีเงินในกระเป๋าพอสำหรับยอดนำส่ง (เงินปลายทาง − รายได้ของคุณ) ระบบหักอัตโนมัติหลังส่งสำเร็จ
                @endif
            </div>
        @endif
        @if(!empty($jobData['items_summary']) || !empty($jobData['description']))
            <div class="tp-divider"></div>
            <div>
                <div class="rd-label" style="margin-bottom:4px;"><i class="fas fa-box-open" style="color:var(--accent1);"></i> รายการของ</div>
                <div style="font-size:13.5px; white-space:pre-line; overflow-wrap:anywhere;">{{ $jobData['items_summary'] ?: $jobData['description'] }}</div>
            </div>
        @endif
    </section>

    {{-- ── ผลงาน: ส่งไม่สำเร็จ / ยกเลิก ─────────────────────────── --}}
    @if(!empty($jobData['failure']))
        <div class="rd-alert rd-tone-bad">
            <i class="fas fa-triangle-exclamation"></i>
            <div><b>ส่งไม่สำเร็จ:</b> {{ $jobData['failure']['reason_text'] ?? '' }}@if(!empty($jobData['failure']['note'])) — {{ $jobData['failure']['note'] }}@endif</div>
        </div>
    @endif
    @if(!empty($jobData['cancellation']))
        <div class="rd-alert rd-tone-muted">
            <i class="fas fa-ban"></i>
            <div><b>งานถูกยกเลิก</b>@if(!empty($jobData['cancellation']['reason'])) — {{ $jobData['cancellation']['reason'] }}@endif</div>
        </div>
    @endif

    {{-- ── รูปยืนยัน ───────────────────────────────────────────── --}}
    @php
        $photoRows = array_filter([
            ['รูปตอนรับของ', $photos['pickup'] ?? null],
            ['รูปยืนยันการส่ง', $photos['delivery'] ?? null],
            ['รูปประกอบส่งไม่สำเร็จ', $photos['failure'] ?? null],
        ], fn ($p) => !empty($p[1]));
    @endphp
    @if(count($photoRows) > 0)
        <section class="tp-card rd-stack">
            <h2 class="rd-h2"><i class="fas fa-camera" style="color:var(--accent1);"></i> รูปยืนยัน</h2>
            <div class="rd-grid" style="--rd-min:180px;">
                @foreach($photoRows as [$label, $url])
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
                        <img src="{{ $url }}" alt="{{ $label }}" class="rd-thumb" loading="lazy">
                        <div class="rd-small rd-muted" style="margin-top:6px;">{{ $label }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── ไทม์ไลน์ ────────────────────────────────────────────── --}}
    @if(count($timelineRows) > 0)
        <section class="tp-card rd-stack">
            <h2 class="rd-h2"><i class="fas fa-timeline" style="color:var(--accent1);"></i> ไทม์ไลน์</h2>
            <div class="rd-list">
                @foreach($timelineRows as [$label, $at, $icon])
                    <div class="rd-item rd-tone-gold" style="min-height:48px;">
                        <span class="ic" style="width:36px; height:36px;"><i class="fas {{ $icon }}"></i></span>
                        <span class="main"><span class="ttl" style="display:block;">{{ $label }}</span></span>
                        <span class="end rd-small rd-muted">{{ $ui::date($at) }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
