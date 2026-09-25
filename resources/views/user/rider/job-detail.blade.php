{{--
    รายละเอียดงานไรเดอร์ (user.rider.jobs.show) — แสดงปุ่มเฉพาะที่ทำได้ตอนนี้ (allowedActions)
    Controller: User\RiderController@showJob (403 ถ้าไม่ใช่งานของตัวเอง/ไม่ได้เสนอให้)
    ตัวแปร: rider, job, jobData (JobDetail), isMine, allowedActions, failureReasons, codAmount,
            endpoints{accept,reject,release,status,deliver,fail,gps_lost,gps_off,location,show}, activeJobPageUrl, pageTitle
    ฟอร์ม (ทำงานได้ทั้ง AJAX และโพสต์ปกติ):
      accept {latitude?, longitude?} · reject · release {reason?} · status {status, photo?}
      deliver multipart {photo*, latitude?, longitude?, cod_collected=1 (เมื่อมี COD), note?} · fail {reason_code*, note?, photo?}
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
    $acceptBlock = (! $isMine && in_array('accept', $allowedActions, true)) ? $rider->acceptBlockReason() : null;
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

    $detailCfg = [
        'activeJobUrl' => route('taladsod.rider.active-job', ['job' => '__ID__']),
        'jobsUrl' => route('user.rider.jobs'),
        'earnings' => (float) ($jobData['rider_earnings'] ?? 0),
    ];
@endphp

@section('content')
<div class="rd-scope" x-data="rdJobDetail({{ \Illuminate\Support\Js::from($detailCfg) }})">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'jobs'])

    <a href="{{ route('user.rider.jobs', ['tab' => $isActive ? 'current' : ($isOpen ? 'available' : 'history')]) }}" class="rd-link rd-small" style="align-self:flex-start;">
        <i class="fas fa-arrow-left"></i> กลับไปหน้างาน
    </a>

    {{-- ── ฉลองส่งสำเร็จ (หลังส่งภายใน 30 นาที / หลังกดส่งสำเร็จผ่าน AJAX) ── --}}
    <template x-if="done">
        <section class="tp-card rd-celebrate">
            <div class="burst" aria-hidden="true"></div>
            <div style="font-size:40px;">🎉</div>
            <div class="rd-h2" style="justify-content:center; margin-top:6px;">ส่งสำเร็จ! เก่งมาก</div>
            <div class="big" style="margin-top:10px;" x-text="'+฿' + window.rdApi.money(done.earnings)"></div>
            <div class="rd-muted rd-small" style="margin-top:8px;">กำลังอัปเดตข้อมูลงาน...</div>
        </section>
    </template>
    @if($celebrate)
        <section class="tp-card rd-celebrate" x-show="!done">
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
                <a href="{{ route('user.rider.jobs', ['tab' => 'available']) }}" class="rd-btn3d rd-tone-ok"><i class="fas fa-bolt"></i> รับงานถัดไป</a>
                <a href="{{ route('user.rider.earnings', ['period' => 'today']) }}" class="tp-btn"><i class="fas fa-coins"></i> ดูรายได้วันนี้</a>
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

    @if($activeJobPageUrl)
        <a href="{{ $activeJobPageUrl }}" class="rd-btn3d rd-tone-gold lg block">
            <i class="fas fa-map-location-dot"></i> เปิดหน้าทำงาน (แผนที่ + ส่งตำแหน่ง GPS)
        </a>
    @endif

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

    {{-- ── ปุ่มทำงาน (เฉพาะที่ทำได้ตอนนี้) ───────────────────────── --}}
    @if(count($allowedActions) > 0)
        <section class="tp-card rd-stack" style="box-shadow:var(--card-shadow), 0 0 0 2px color-mix(in srgb, var(--accent1) 40%, transparent);">
            <h2 class="rd-h2"><i class="fas fa-hand-point-right" style="color:var(--accent1);"></i> ขั้นตอนถัดไป</h2>

            {{-- รับงาน --}}
            @if(in_array('accept', $allowedActions, true))
                @if($acceptBlock)
                    <div class="rd-alert rd-tone-warn">
                        <i class="fas fa-circle-exclamation"></i>
                        <div>
                            <b>ยังรับงานไม่ได้:</b> {{ $acceptBlock['message'] }}
                            @if(($acceptBlock['code'] ?? '') === 'CONSENT_REQUIRED' || ($acceptBlock['code'] ?? '') === 'OFFLINE')
                                <div style="margin-top:6px;"><a href="{{ route('user.rider.dashboard') }}" class="rd-link">ไปที่แดชบอร์ดเพื่อ{{ ($acceptBlock['code'] ?? '') === 'OFFLINE' ? 'เปิดรับงาน' : 'ให้ความยินยอม' }} <i class="fas fa-arrow-right"></i></a></div>
                            @endif
                        </div>
                    </div>
                @endif
                <form method="POST" action="{{ $endpoints['accept'] }}" x-on:submit.prevent="submit($event, 'accept', { geo: true })">
                    @csrf
                    <button type="submit" class="rd-btn3d rd-tone-ok lg block" :disabled="busy !== null || {{ $acceptBlock ? 'true' : 'false' }}" @if($acceptBlock) disabled @endif>
                        <i class="fas" :class="busy === 'accept' ? 'fa-circle-notch rd-spin' : 'fa-hand-pointer'"></i>
                        รับงานนี้ · ได้ ฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}
                    </button>
                </form>
                <form method="POST" action="{{ $endpoints['reject'] }}" x-on:submit.prevent="submit($event, 'reject', {})">
                    @csrf
                    <button type="submit" class="tp-btn" style="width:100%;" :disabled="busy !== null"><i class="fas fa-eye-slash"></i> ไม่สนใจงานนี้</button>
                </form>
            @endif

            {{-- เดินทางไปรับของ --}}
            @if(in_array('picking_up', $allowedActions, true))
                <form method="POST" action="{{ $endpoints['status'] }}" x-on:submit.prevent="submit($event, 'picking_up', {})">
                    @csrf
                    <input type="hidden" name="status" value="picking_up">
                    <button type="submit" class="rd-btn3d rd-tone-info block" :disabled="busy !== null">
                        <i class="fas" :class="busy === 'picking_up' ? 'fa-circle-notch rd-spin' : 'fa-motorcycle'"></i> เริ่มเดินทางไปรับของ
                    </button>
                </form>
            @endif

            {{-- รับของแล้ว (แนบรูปได้) --}}
            @if(in_array('picked_up', $allowedActions, true))
                <form method="POST" action="{{ $endpoints['status'] }}" enctype="multipart/form-data" class="rd-stack"
                      x-on:submit.prevent="submit($event, 'picked_up', {})">
                    @csrf
                    <input type="hidden" name="status" value="picked_up">
                    <div class="rd-field">
                        <label for="pickup-photo">รูปสินค้าตอนรับของ (ไม่บังคับ แต่แนะนำ)</label>
                        <input id="pickup-photo" type="file" name="photo" accept="image/*" capture="environment" class="tp-input" x-on:change="preview($event, 'pickupPreview')">
                        <template x-if="pickupPreview"><img :src="pickupPreview" alt="ตัวอย่างรูปรับของ" class="rd-thumb"></template>
                    </div>
                    <button type="submit" class="rd-btn3d rd-tone-gold block" :disabled="busy !== null">
                        <i class="fas" :class="busy === 'picked_up' ? 'fa-circle-notch rd-spin' : 'fa-box'"></i> ยืนยันรับของแล้ว
                    </button>
                </form>
            @endif

            {{-- เริ่มนำส่ง --}}
            @if(in_array('delivering', $allowedActions, true))
                <form method="POST" action="{{ $endpoints['status'] }}" x-on:submit.prevent="submit($event, 'delivering', {})">
                    @csrf
                    <input type="hidden" name="status" value="delivering">
                    <button type="submit" class="rd-btn3d rd-tone-info block" :disabled="busy !== null">
                        <i class="fas" :class="busy === 'delivering' ? 'fa-circle-notch rd-spin' : 'fa-truck-fast'"></i> เริ่มนำส่งให้ลูกค้า
                    </button>
                </form>
            @endif

            {{-- ส่งสำเร็จ (รูปบังคับ + ยืนยันเก็บเงินปลายทาง) --}}
            @if(in_array('deliver', $allowedActions, true))
                <form method="POST" action="{{ $endpoints['deliver'] }}" enctype="multipart/form-data" class="rd-stack"
                      x-on:submit.prevent="submit($event, 'deliver', { geo: true })"
                      style="padding:16px; border-radius:18px; box-shadow:var(--inset-sm);">
                    @csrf
                    <div class="rd-h2" style="font-size:15px;"><i class="fas fa-house-circle-check" style="color:var(--rd-ok);"></i> ส่งของถึงมือลูกค้า</div>
                    <div class="rd-field">
                        <label for="deliver-photo">รูปยืนยันการส่ง <span class="req">*</span></label>
                        <input id="deliver-photo" type="file" name="photo" accept="image/*" capture="environment" required class="tp-input" x-on:change="preview($event, 'deliverPreview')">
                        <span class="rd-hint">ถ่ายให้เห็นสินค้ากับหน้าบ้าน/ผู้รับ (ไม่เกิน 10MB)</span>
                        <template x-if="deliverPreview"><img :src="deliverPreview" alt="ตัวอย่างรูปยืนยันการส่ง" class="rd-thumb"></template>
                    </div>
                    @if($codAmount > 0)
                        <label class="rd-alert rd-tone-warn" style="cursor:pointer;">
                            <input type="checkbox" name="cod_collected" value="1" required style="width:22px; height:22px; accent-color:var(--accent1); flex:none; margin-top:1px;">
                            <span>
                                <b>ฉันเก็บเงินสด ฿{{ $ui::money($codAmount) }} จากลูกค้าแล้ว</b><br>
                                <span class="rd-small">เงินนี้รวมค่าส่งของคุณแล้ว — ระบบจะหักส่วนที่เป็นของร้าน/ระบบ ฿{{ $ui::money(max(0, $codAmount - (float) ($jobData['rider_earnings'] ?? 0))) }} จากกระเป๋าของคุณอัตโนมัติ</span>
                            </span>
                        </label>
                    @endif
                    <div class="rd-field">
                        <label for="deliver-note">หมายเหตุ (ไม่บังคับ)</label>
                        <input id="deliver-note" type="text" name="note" maxlength="500" class="tp-input" placeholder="เช่น ฝากไว้กับ รปภ.">
                    </div>
                    <button type="submit" class="rd-btn3d rd-tone-ok lg block" :disabled="busy !== null">
                        <i class="fas" :class="busy === 'deliver' ? 'fa-circle-notch rd-spin' : 'fa-circle-check'"></i> ยืนยันส่งสำเร็จ · รับ ฿{{ $ui::money($jobData['rider_earnings'] ?? 0) }}
                    </button>
                </form>
            @endif

            {{-- ส่งไม่สำเร็จ --}}
            @if(in_array('fail', $allowedActions, true))
                <details class="rd-details tp-card" style="padding:4px 16px;">
                    <summary><i class="fas fa-triangle-exclamation" style="color:var(--rd-bad);"></i> ส่งไม่สำเร็จ? <i class="fas fa-chevron-down chev"></i></summary>
                    <form method="POST" action="{{ $endpoints['fail'] }}" enctype="multipart/form-data" class="rd-stack" style="padding-bottom:14px;"
                          x-on:submit.prevent="submit($event, 'fail', { confirm: 'ยืนยันว่าส่งของไม่สำเร็จ? ทีมงานจะติดต่อเพื่อจัดการคืนสินค้า' })">
                        @csrf
                        <div class="rd-field">
                            <span class="rd-label">เหตุผล <span class="req">*</span></span>
                            <div class="rd-grid" style="--rd-min:170px; gap:8px;">
                                @foreach($failureReasons as $code => $label)
                                    <label class="rd-choice">
                                        <input type="radio" name="reason_code" value="{{ $code }}" required x-model="failReason">
                                        <span class="box" style="min-height:48px; padding:8px 12px;"><span style="font-size:13px; font-weight:700;">{{ $label }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="rd-field">
                            <label for="fail-note">รายละเอียด <span class="req" x-show="failReason === 'other'">*</span></label>
                            <textarea id="fail-note" name="note" maxlength="1000" class="tp-input" :required="failReason === 'other'" placeholder="เล่าสั้น ๆ ว่าเกิดอะไรขึ้น"></textarea>
                        </div>
                        <div class="rd-field">
                            <label for="fail-photo">รูปประกอบ (ไม่บังคับ)</label>
                            <input id="fail-photo" type="file" name="photo" accept="image/*" capture="environment" class="tp-input" x-on:change="preview($event, 'failPreview')">
                            <template x-if="failPreview"><img :src="failPreview" alt="ตัวอย่างรูปประกอบ" class="rd-thumb"></template>
                        </div>
                        <button type="submit" class="rd-btn3d rd-tone-bad block" :disabled="busy !== null">
                            <i class="fas" :class="busy === 'fail' ? 'fa-circle-notch rd-spin' : 'fa-flag'"></i> บันทึกว่าส่งไม่สำเร็จ
                        </button>
                    </form>
                </details>
            @endif

            {{-- คืนงาน (ก่อนรับของ) --}}
            @if(in_array('release', $allowedActions, true))
                <details class="rd-details tp-card" style="padding:4px 16px;">
                    <summary><i class="fas fa-rotate-left" style="color:var(--ink2);"></i> คืนงานนี้ (ก่อนรับของเท่านั้น) <i class="fas fa-chevron-down chev"></i></summary>
                    <form method="POST" action="{{ $endpoints['release'] }}" class="rd-stack" style="padding-bottom:14px;"
                          x-on:submit.prevent="submit($event, 'release', { confirm: 'ยืนยันคืนงานนี้? ระบบจะส่งงานให้ไรเดอร์คนอื่น' })">
                        @csrf
                        <div class="rd-hint">คืนงานบ่อยอาจทำให้ได้รับงานน้อยลง ใช้เมื่อจำเป็นเท่านั้น</div>
                        <div class="rd-field">
                            <label for="release-reason">เหตุผล (ไม่บังคับ)</label>
                            <input id="release-reason" type="text" name="reason" maxlength="255" class="tp-input" placeholder="เช่น รถเสีย / ร้านปิด">
                        </div>
                        <button type="submit" class="tp-btn rd-btn-ghost rd-tone-bad" style="width:100%;" :disabled="busy !== null">
                            <i class="fas" :class="busy === 'release' ? 'fa-circle-notch rd-spin' : 'fa-rotate-left'"></i> คืนงาน
                        </button>
                    </form>
                </details>
            @endif
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

@push('scripts')
    @include('user.rider.partials.scripts')
    <script>
    window.rdJobDetail = function (cfg) {
        return {
            busy: null,
            done: null,
            failReason: '',
            pickupPreview: null,
            deliverPreview: null,
            failPreview: null,
            preview(event, key) {
                const file = event.target.files && event.target.files[0];
                if (this[key]) { URL.revokeObjectURL(this[key]); this[key] = null; }
                if (!file) { return; }
                if (file.size > 10 * 1024 * 1024) {
                    window.rdApi.toast('รูปต้องมีขนาดไม่เกิน 10MB กรุณาถ่ายใหม่หรือเลือกรูปที่เล็กลง', 'error');
                    event.target.value = '';
                    return;
                }
                if (/^image\//.test(file.type)) { this[key] = URL.createObjectURL(file); }
            },
            async submit(event, action, opts) {
                const form = event.target;
                if (this.busy) { return; }
                if (typeof form.reportValidity === 'function' && !form.reportValidity()) { return; }
                if (opts.confirm && !window.confirm(opts.confirm)) { return; }
                this.busy = action;

                const data = new FormData(form);
                const photo = data.get('photo');
                if (photo instanceof File) {
                    if (photo.size > 0) { data.set('photo', await window.rdApi.shrink(photo)); } else { data.delete('photo'); }
                }
                if (opts.geo) {
                    const pos = await window.rdApi.geo(8000);
                    if (pos) { data.set('latitude', pos.latitude); data.set('longitude', pos.longitude); }
                }

                const res = await window.rdApi.post(form.action, data);
                if (!res.ok) {
                    this.busy = null;
                    window.rdApi.toast(res.message, res.code === 'JOB_TAKEN' ? 'warning' : 'error');
                    if (res.code === 'JOB_TAKEN') { setTimeout(() => { window.location.href = cfg.jobsUrl; }, 1500); }
                    return;
                }

                window.rdApi.toast(res.message || 'บันทึกแล้ว', 'success');
                if (action === 'accept') {
                    const id = (res.data && res.data.job && res.data.job.id) ? res.data.job.id : '';
                    window.location.href = id ? String(cfg.activeJobUrl).replace('__ID__', String(id)) : window.location.href;
                    return;
                }
                if (action === 'release' || action === 'reject') {
                    window.location.href = cfg.jobsUrl;
                    return;
                }
                if (action === 'deliver') {
                    const job = res.data && res.data.job ? res.data.job : null;
                    this.done = { earnings: job && job.rider_earnings !== undefined ? job.rider_earnings : cfg.earnings };
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    setTimeout(() => window.location.reload(), 2600);
                    return;
                }
                setTimeout(() => window.location.reload(), 500);
            }
        };
    };
    </script>
@endpush
