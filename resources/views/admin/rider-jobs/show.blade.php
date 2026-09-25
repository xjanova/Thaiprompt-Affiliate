{{--
 | รายละเอียดงานไรเดอร์ (admin.rider-jobs.show) — ธีม V4 + OpenStreetMap/Leaflet
 | ตัวแปรจาก Admin\RiderJobController@show:
 |   $job (with rider, customer), $jobData (RiderJob::toApiDetail(null) — timeline/photos/failure/cancellation/cod/rider),
 |   $locationHistory (RiderLocation เก่า→ใหม่), $adminActions (cancel|fail|reassign|redispatch),
 |   $eligibleRiders[{id,full_name,phone,vehicle_type_text,availability,availability_text,distance_to_pickup_km,last_location_update}],
 |   $source{type,id,order_number,url}, $dispatchAttempts, $pageTitle
 | การกระทำ (ฟอร์ม POST ผ่านโมดัลยืนยัน):
 |   cancel → admin.rider-jobs.cancel {reason, redispatch?} · fail (รับของแล้ว) → ใช้ route cancel เดียวกัน (ระบบปิดเป็นส่งไม่สำเร็จ)
 |   reassign → admin.rider-jobs.reassign {rider_id} · redispatch → admin.rider-jobs.redispatch
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'รายละเอียดงาน')

@section('content')
@include('admin.riders.partials.v4-kit')
@include('admin.riders.partials.leaflet')
@include('admin.riders.partials.doc-viewer')
@php
    $timeline = $jobData['timeline'] ?? [];
    $steps = [
        ['created_at', 'สร้างงาน', 'fa-plus', 'mute'],
        ['accepted_at', 'ไรเดอร์รับงาน', 'fa-handshake', 'info'],
        ['picked_up_at', 'รับของจากร้าน', 'fa-box', 'violet'],
        ['delivered_at', 'ส่งถึงลูกค้า', 'fa-box-open', 'ok'],
        ['completed_at', 'ปิดงาน / จ่ายค่าส่ง', 'fa-circle-check', 'ok'],
        ['cancelled_at', 'ยกเลิก', 'fa-ban', 'bad'],
        ['failed_at', 'ส่งไม่สำเร็จ', 'fa-triangle-exclamation', 'bad'],
    ];
    $photos = collect($jobData['photos'] ?? [])->filter()->map(fn ($url, $key) => [
        'label' => ['pickup' => 'รูปตอนรับของ', 'delivery' => 'รูปตอนส่งของ', 'failure' => 'รูปประกอบส่งไม่สำเร็จ'][$key] ?? $key,
        'url' => $url,
    ])->values()->all();
    $attemptRiderIds = collect($dispatchAttempts)->pluck('rider_id')->filter()->unique()->values()->all();
    $attemptRiders = $attemptRiderIds !== [] ? \App\Models\Rider::withTrashed()->whereIn('id', $attemptRiderIds)->pluck('full_name', 'id') : collect();
    $attemptLabels = [
        'pending' => ['เสนองาน รอตอบ', 'warn'],
        'accepted' => ['รับงาน', 'ok'],
        'rejected' => ['ไม่รับ', 'mute'],
        'expired' => ['หมดเวลาตอบ', 'mute'],
        'released' => ['คืนงาน', 'bad'],
        'admin_assigned' => ['แอดมินมอบหมาย', 'info'],
    ];
    $mapPoints = [
        'pickup' => ($job->pickup_latitude !== null && $job->pickup_longitude !== null) ? [(float) $job->pickup_latitude, (float) $job->pickup_longitude] : null,
        'dropoff' => ($job->delivery_latitude !== null && $job->delivery_longitude !== null) ? [(float) $job->delivery_latitude, (float) $job->delivery_longitude] : null,
        'rider' => ($job->rider && $job->rider->last_latitude !== null && $job->rider->last_longitude !== null && $job->isTrackable())
            ? [(float) $job->rider->last_latitude, (float) $job->rider->last_longitude] : null,
        'path' => $locationHistory->map(fn ($p) => [(float) $p->latitude, (float) $p->longitude])->values()->all(),
    ];
    $hasMap = $mapPoints['pickup'] || $mapPoints['dropoff'] || count($mapPoints['path']) > 0;
    $sourceTypeLabel = ['FreshMarketOrder' => 'ออเดอร์ตลาดสด', 'Order' => 'ออเดอร์ร้านค้า'][$source['type'] ?? ''] ?? ($source['type'] ?? null);
@endphp
<div x-data="{ reassignQuery: '' }" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
            <a href="{{ route('admin.rider-jobs.index') }}" class="tp-icon-btn" title="กลับหน้ารายการงาน"><i class="fas fa-arrow-left"></i></a>
            <div style="min-width:0;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · งานไรเดอร์ · {{ $job->job_type_text }}</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0; display:flex; flex-wrap:wrap; align-items:center; gap:9px;">
                    งาน #{{ $job->job_number }}
                    @include('admin.riders.partials.status', ['statusKind' => 'job', 'statusValue' => $job->status, 'statusLabel' => $job->status_text])
                    @if ($job->dispatch_type === 'manual_needed' && ! $job->isTerminal())
                        @include('admin.riders.partials.pill', ['pillTone' => 'bad', 'pillText' => 'ต้องจัดเอง', 'pillIcon' => 'fa-hand', 'pillTitle' => 'ไม่มีไรเดอร์รับภายในเวลาที่ตั้งไว้'])
                    @endif
                </h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">สร้างเมื่อ {{ $job->created_at?->thaidate('j M Y H:i') }} · {{ $job->title }}</div>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            @if (! empty($source['url']))
                <a href="{{ $source['url'] }}" class="tp-btn tp-btn-sm"><i class="fas fa-receipt"></i> {{ $sourceTypeLabel }} {{ $source['order_number'] ? '#'.$source['order_number'] : '' }}</a>
            @endif
            @if (! empty($jobData['tracking_url']))
                <a href="{{ $jobData['tracking_url'] }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm"><i class="fas fa-link"></i> ลิงก์ติดตามของลูกค้า</a>
            @endif
            <a href="{{ route('admin.riders.monitor') }}" class="tp-btn tp-btn-sm"><i class="fas fa-satellite-dish"></i> มอนิเตอร์</a>
        </div>
    </div>

    @include('admin.riders.partials.flash')

    {{-- ===== สาเหตุยกเลิก / ส่งไม่สำเร็จ ===== --}}
    @if (! empty($jobData['cancellation']))
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--w-bad);">
            <div style="font-weight:700; font-size:13.5px;"><i class="fas fa-ban" style="color:var(--w-bad);"></i> งานถูกยกเลิก
                @if ($jobData['cancellation']['by']) <span style="font-weight:500; color:var(--ink2);">โดย {{ ['admin' => 'แอดมิน', 'rider' => 'ไรเดอร์', 'customer' => 'ลูกค้า', 'buyer' => 'ผู้ซื้อ', 'seller' => 'ร้านค้า', 'system' => 'ระบบ'][$jobData['cancellation']['by']] ?? $jobData['cancellation']['by'] }}</span>@endif
            </div>
            <div style="font-size:13px; margin-top:4px;">{{ $jobData['cancellation']['reason'] ?: 'ไม่ระบุเหตุผล' }}</div>
        </div>
    @endif
    @if (! empty($jobData['failure']))
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--w-bad);">
            <div style="font-weight:700; font-size:13.5px;"><i class="fas fa-triangle-exclamation" style="color:var(--w-bad);"></i> ส่งไม่สำเร็จ: {{ $jobData['failure']['reason_text'] ?? '-' }}</div>
            @if (! empty($jobData['failure']['note']))
                <div style="font-size:13px; margin-top:4px;">{{ $jobData['failure']['note'] }}</div>
            @endif
        </div>
    @endif

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">

        {{-- ================= คอลัมน์หลัก ================= --}}
        <div style="flex:3 1 560px; min-width:0; display:flex; flex-direction:column; gap:16px;">

            {{-- ตัวเลขงาน --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
                @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-route', 'kpiValue' => number_format((float) $job->distance_km, 2).' กม.', 'kpiLabel' => 'ระยะทาง', 'kpiTone' => null, 'kpiHref' => null, 'kpiHint' => 'ประมาณ '.(int) $job->estimated_duration_minutes.' นาที', 'kpiPulse' => false])
                @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-receipt', 'kpiValue' => '฿'.number_format((float) $job->total_fee, 2), 'kpiLabel' => 'ค่าส่งที่ลูกค้าจ่าย', 'kpiTone' => 'info', 'kpiHref' => null, 'kpiHint' => 'เริ่มต้น ฿'.number_format((float) $job->base_fee, 2).' + ระยะ ฿'.number_format((float) $job->distance_fee, 2), 'kpiPulse' => false])
                @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-motorcycle', 'kpiValue' => '฿'.number_format((float) $job->rider_earnings, 2), 'kpiLabel' => 'ไรเดอร์ได้', 'kpiTone' => 'ok', 'kpiHref' => null, 'kpiHint' => ($jobData['earnings_settled'] ?? false) ? 'จ่ายเข้าวอลเลตแล้ว' : 'ยังไม่จ่าย', 'kpiPulse' => false])
                @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-building', 'kpiValue' => '฿'.number_format((float) $job->platform_fee, 2), 'kpiLabel' => 'แพลตฟอร์มได้', 'kpiTone' => null, 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
                @if ((float) $job->cod_amount > 0)
                    @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-hand-holding-dollar', 'kpiValue' => '฿'.number_format((float) $job->cod_amount, 2), 'kpiLabel' => 'เก็บเงินปลายทาง', 'kpiTone' => 'warn', 'kpiHref' => null, 'kpiHint' => ! empty($jobData['cod']['settled_at']) ? 'หักวอลเลตไรเดอร์แล้ว' : (! empty($jobData['cod']['collected_at']) ? 'เก็บแล้ว รอหักวอลเลต' : 'ยังไม่เก็บ'), 'kpiPulse' => false])
                @endif
            </div>

            {{-- แผนที่ --}}
            @if ($hasMap)
                <div class="tp-card" style="padding:0; overflow:hidden;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:12px 16px;">
                        <div class="tp-section-h"><i class="fas fa-map-location-dot"></i> เส้นทาง</div>
                        <div style="display:flex; flex-wrap:wrap; gap:10px; font-size:11.5px; color:var(--ink2);">
                            <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--w-warn);"></span> จุดรับ</span>
                            <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--w-info);"></span> จุดส่ง</span>
                            @if (count($mapPoints['path']) > 0)<span><span style="display:inline-block; width:14px; height:4px; border-radius:3px; background:var(--accent1);"></span> เส้นทางจริง {{ number_format(count($mapPoints['path'])) }} จุด</span>@endif
                            @if ($mapPoints['rider'])<span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--w-violet);"></span> ไรเดอร์ตอนนี้</span>@endif
                        </div>
                    </div>
                    <div id="w1-job-map" style="width:100%; height:min(52vh, 440px); min-height:300px;"></div>
                </div>
            @endif

            {{-- จุดรับ / จุดส่ง --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr)); gap:16px;">
                <div class="tp-card" style="padding:18px; border-top:4px solid var(--w-warn);">
                    <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-store" style="color:var(--w-warn);"></i> จุดรับของ</div>
                    <div style="font-weight:700;">{{ $jobData['pickup']['name'] ?? '-' }}</div>
                    <div style="font-size:13px; color:var(--ink2); margin-top:3px;">{{ $jobData['pickup']['address'] ?? '-' }}</div>
                    @if (! empty($jobData['pickup']['phone']))
                        <a href="tel:{{ $jobData['pickup']['phone'] }}" class="w1-link" style="display:inline-block; margin-top:6px;"><i class="fas fa-phone"></i> {{ $jobData['pickup']['phone'] }}</a>
                    @endif
                    @if (! empty($jobData['pickup']['notes']))
                        <div class="tp-well" style="padding:8px 10px; margin-top:8px; font-size:12.5px;"><i class="fas fa-note-sticky" style="color:var(--ink2);"></i> {{ $jobData['pickup']['notes'] }}</div>
                    @endif
                </div>
                <div class="tp-card" style="padding:18px; border-top:4px solid var(--w-info);">
                    <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-flag-checkered" style="color:var(--w-info);"></i> จุดส่งของ</div>
                    <div style="font-weight:700;">{{ $jobData['dropoff']['name'] ?? ($job->customer?->name ?? '-') }}</div>
                    <div style="font-size:13px; color:var(--ink2); margin-top:3px;">{{ $jobData['dropoff']['address'] ?? '-' }}</div>
                    @if (! empty($jobData['dropoff']['phone']))
                        <a href="tel:{{ $jobData['dropoff']['phone'] }}" class="w1-link" style="display:inline-block; margin-top:6px;"><i class="fas fa-phone"></i> {{ $jobData['dropoff']['phone'] }}</a>
                    @else
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:6px;">เบอร์ลูกค้าถูกซ่อนหลังงานจบเกิน 1 ชั่วโมง</div>
                    @endif
                    @if (! empty($jobData['dropoff']['notes']))
                        <div class="tp-well" style="padding:8px 10px; margin-top:8px; font-size:12.5px;"><i class="fas fa-note-sticky" style="color:var(--ink2);"></i> {{ $jobData['dropoff']['notes'] }}</div>
                    @endif
                    @if (! empty($jobData['customer_live_location']))
                        <div style="font-size:11.5px; color:color-mix(in srgb, var(--w-ok) 74%, var(--ink)); margin-top:6px;"><i class="fas fa-location-dot"></i> ลูกค้าแชร์ตำแหน่งสดอยู่</div>
                    @endif
                </div>
            </div>

            @if (! empty($jobData['items_summary']) || ! empty($jobData['description']))
                <div class="tp-card" style="padding:18px;">
                    <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-basket-shopping"></i> ของที่ต้องส่ง</div>
                    <div style="font-size:13.5px; white-space:pre-line;">{{ $jobData['description'] ?? $jobData['items_summary'] }}</div>
                </div>
            @endif

            {{-- รูปหลักฐาน --}}
            @if (count($photos) > 0)
                <div class="tp-card" style="padding:18px;">
                    <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-camera"></i> รูปหลักฐาน</div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:12px;">
                        @foreach ($photos as $photoIndex => $photo)
                            <button type="button" style="border:0; padding:0; background:none; cursor:zoom-in; text-align:left; color:var(--ink);"
                                    @click="$dispatch('w1-doc', @js(['items' => $photos, 'index' => $photoIndex]))">
                                <span style="display:block; border-radius:13px; overflow:hidden; aspect-ratio:4/3; box-shadow:var(--inset-sm); background:var(--bg);">
                                    <img src="{{ $photo['url'] }}" alt="{{ $photo['label'] }}" loading="lazy" style="width:100%; height:100%; object-fit:cover; display:block;">
                                </span>
                                <span style="display:block; font-size:12.5px; font-weight:600; margin-top:6px;">{{ $photo['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ประวัติการกระจายงาน --}}
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
                    <div class="tp-section-h"><i class="fas fa-bullhorn"></i> ประวัติการกระจายงาน</div>
                    <span style="font-size:12px; color:var(--ink2);">
                        รอบที่ {{ (int) $job->dispatch_round }}@if ($job->dispatch_radius_km) · รัศมี {{ (float) $job->dispatch_radius_km }} กม.@endif
                        · แจ้งแล้ว {{ count($job->candidate_riders ?? []) }} คน · คืนงาน {{ (int) $job->release_count }} ครั้ง
                    </span>
                </div>
                @if (count($dispatchAttempts) > 0)
                    <div style="overflow-x:auto;">
                        <table style="width:100%; min-width:560px; border-collapse:collapse; font-size:13px;">
                            <thead>
                                <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                                    <th style="padding:9px 18px;">ไรเดอร์</th>
                                    <th style="padding:9px 12px;">ผล</th>
                                    <th style="padding:9px 12px;">เหตุผล</th>
                                    <th style="padding:9px 18px;">เวลา</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_reverse($dispatchAttempts) as $attempt)
                                    @php
                                        [$attemptLabel, $attemptTone] = $attemptLabels[$attempt['status'] ?? ''] ?? [(string) ($attempt['status'] ?? '-'), 'mute'];
                                        $attemptAt = $attempt['at'] ?? $attempt['responded_at'] ?? $attempt['sent_at'] ?? null;
                                        $attemptRiderId = (int) ($attempt['rider_id'] ?? 0);
                                    @endphp
                                    <tr style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                        <td style="padding:9px 18px;">
                                            @if ($attemptRiderId)
                                                <a href="{{ route('admin.riders.show', $attemptRiderId) }}" class="w1-link">{{ $attemptRiders[$attemptRiderId] ?? '#'.$attemptRiderId }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td style="padding:9px 12px;">@include('admin.riders.partials.pill', ['pillTone' => $attemptTone, 'pillText' => $attemptLabel, 'pillIcon' => null, 'pillTitle' => null])</td>
                                        <td style="padding:9px 12px; color:var(--ink2);">{{ $attempt['reason'] ?? '' }}</td>
                                        <td style="padding:9px 18px; white-space:nowrap; color:var(--ink2);">{{ $attemptAt ? \Illuminate\Support\Carbon::parse($attemptAt)->timezone(config('app.timezone'))->format('d/m H:i:s') : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="padding:0 18px 16px; margin:0; font-size:13px; color:var(--ink2);">โหมดแจ้งทุกคนในรัศมี — ไม่มีบันทึกการตอบรับรายคน</p>
                @endif
            </div>
        </div>

        {{-- ================= คอลัมน์ข้าง ================= --}}
        <div style="flex:1 1 300px; min-width:0; display:flex; flex-direction:column; gap:16px;">

            {{-- ไทม์ไลน์ --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-timeline"></i> ไทม์ไลน์</div>
                <div style="position:relative; padding-left:6px;">
                    @foreach ($steps as [$stepKey, $stepLabel, $stepIcon, $stepTone])
                        @php
                            $stepAt = $timeline[$stepKey] ?? null;
                            $isOptional = in_array($stepKey, ['cancelled_at', 'failed_at'], true);
                        @endphp
                        @continue($isOptional && ! $stepAt)
                        <div style="display:flex; gap:12px; position:relative; padding-bottom:14px;">
                            @if (! $loop->last)
                                <span style="position:absolute; left:14px; top:30px; bottom:0; width:2px; background:color-mix(in srgb, var(--ink2) 25%, transparent);"></span>
                            @endif
                            <span style="width:30px; height:30px; flex:none; border-radius:50%; display:grid; place-items:center; font-size:12px; z-index:1;
                                         {{ $stepAt ? 'color:var(--w-on); background:linear-gradient(135deg, var(--w-'.($stepTone === 'mute' ? 'info' : $stepTone).'), color-mix(in srgb, var(--w-'.($stepTone === 'mute' ? 'info' : $stepTone).') 70%, var(--ink)));' : 'color:var(--ink2); box-shadow:var(--inset-sm);' }}">
                                <i class="fas {{ $stepIcon }}"></i>
                            </span>
                            <div style="min-width:0;">
                                <div style="font-weight:{{ $stepAt ? 700 : 500 }}; font-size:13px; color:{{ $stepAt ? 'var(--ink)' : 'var(--ink2)' }};">{{ $stepLabel }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $stepAt ? \Illuminate\Support\Carbon::parse($stepAt)->timezone(config('app.timezone'))->thaidate('j M Y H:i:s') : 'ยังไม่ถึงขั้นนี้' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ไรเดอร์ --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-motorcycle"></i> ไรเดอร์</div>
                @if ($job->rider)
                    <div style="display:flex; align-items:center; gap:12px;">
                        @if ($job->rider->profile_image)
                            <img src="{{ route('admin.riders.document', [$job->rider, 'profile']) }}" alt="" loading="lazy" style="width:50px; height:50px; border-radius:50%; object-fit:cover; box-shadow:var(--raise);">
                        @else
                            <span class="tp-tile" style="width:50px; height:50px; border-radius:50%; font-weight:800; font-size:18px;">{{ mb_substr($job->rider->full_name ?: 'R', 0, 1) }}</span>
                        @endif
                        <div style="min-width:0;">
                            <a href="{{ route('admin.riders.show', $job->rider) }}" class="w1-link" style="color:var(--ink); font-size:14px;">{{ $job->rider->full_name }}</a>
                            <div style="font-size:12.5px;"><a href="tel:{{ $job->rider->phone }}" class="w1-link">{{ $job->rider->phone }}</a></div>
                            <div style="font-size:11.5px; color:var(--ink2);">{{ $job->rider->vehicle_type_text }} {{ $job->rider->vehicle_plate }} · <i class="fas fa-star" style="color:var(--accent1);"></i> {{ number_format((float) $job->rider->rating, 1) }}</div>
                        </div>
                    </div>
                    @if ($job->isTrackable())
                        <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:10px;">
                            @include('admin.riders.partials.pill', ['pillTone' => ($jobData['gps_active'] ?? false) ? 'ok' : 'bad', 'pillText' => ($jobData['gps_active'] ?? false) ? 'GPS ทำงาน' : 'GPS หาย', 'pillIcon' => 'fa-satellite-dish', 'pillTitle' => null])
                            @if ((int) $job->gps_warning_count > 0)
                                @include('admin.riders.partials.pill', ['pillTone' => 'warn', 'pillText' => 'เตือน GPS '.(int) $job->gps_warning_count.' ครั้ง', 'pillIcon' => null, 'pillTitle' => null])
                            @endif
                        </div>
                    @endif
                @else
                    <div style="text-align:center; color:var(--ink2); padding:12px 4px; font-size:13px;">
                        <i class="fas fa-user-clock" style="font-size:24px; opacity:.5; display:block; margin-bottom:8px;"></i>
                        ยังไม่มีไรเดอร์รับงาน
                    </div>
                @endif
            </div>

            {{-- การจัดการของแอดมิน --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-screwdriver-wrench"></i> การจัดการ</div>
                @if ($adminActions === [])
                    <p style="font-size:13px; color:var(--ink2); margin:0;">งานนี้{{ $job->status_text }}แล้ว ไม่มีการจัดการที่ทำได้</p>
                @else
                    <div style="display:flex; flex-direction:column; gap:9px;">
                        @if (in_array('cancel', $adminActions, true))
                            <button type="button" class="tp-btn" style="width:100%; color:var(--w-bad);"
                                    @click="$dispatch('w1-action', @js(['url' => route('admin.rider-jobs.cancel', $job), 'title' => 'ยกเลิกงาน #'.$job->job_number, 'message' => 'ไรเดอร์ยังไม่ได้รับของ งานจะถูกยกเลิกและออเดอร์กลับไปรอไรเดอร์ ทุกฝ่ายจะได้รับแจ้ง', 'reason' => 'required', 'reasonLabel' => 'เหตุผลที่ยกเลิก', 'confirm' => 'ยกเลิกงาน', 'tone' => 'bad', 'icon' => 'fa-ban', 'checkbox' => ['name' => 'redispatch', 'label' => 'สร้างงานใหม่และหาไรเดอร์คนใหม่ทันที', 'hint' => 'ออเดอร์ยังต้องส่ง แค่เปลี่ยนไรเดอร์', 'checked' => true]]))">
                                <i class="fas fa-ban"></i> ยกเลิกงาน
                            </button>
                        @endif
                        @if (in_array('fail', $adminActions, true))
                            <button type="button" class="tp-btn" style="width:100%; color:var(--w-bad);"
                                    @click="$dispatch('w1-action', @js(['url' => route('admin.rider-jobs.cancel', $job), 'title' => 'ปิดงาน (ส่งไม่สำเร็จ)', 'message' => "ไรเดอร์รับของไปแล้ว — งานจะถูกปิดเป็น \"ส่งไม่สำเร็จ\"\nต้องประสานให้ไรเดอร์นำของคืนร้าน หรือมอบหมายไรเดอร์ใหม่แทนถ้ายังส่งต่อได้", 'reason' => 'required', 'reasonLabel' => 'เหตุผล', 'confirm' => 'ปิดงาน (ส่งไม่สำเร็จ)', 'tone' => 'bad', 'icon' => 'fa-triangle-exclamation', 'checkbox' => ['name' => 'redispatch', 'label' => 'สร้างงานใหม่ให้ออเดอร์นี้หลังปิดงาน', 'hint' => 'ใช้เมื่อร้านจะเตรียมของใหม่ให้ส่งอีกรอบ', 'checked' => false]]))">
                                <i class="fas fa-triangle-exclamation"></i> ปิดงาน (ส่งไม่สำเร็จ)
                            </button>
                        @endif
                        @if (in_array('redispatch', $adminActions, true))
                            <button type="button" class="tp-btn tp-btn-primary" style="width:100%;"
                                    @click="$dispatch('w1-action', @js(['url' => route('admin.rider-jobs.redispatch', $job), 'title' => 'สร้างงานใหม่ให้ออเดอร์', 'message' => 'ระบบจะสร้างงานไรเดอร์ใหม่ให้ออเดอร์เดิม แล้วแจ้งไรเดอร์ใกล้ร้าน', 'reason' => 'none', 'confirm' => 'สร้างงานใหม่', 'tone' => 'gold', 'icon' => 'fa-rotate']))">
                                <i class="fas fa-rotate"></i> สร้างงานใหม่ / หาไรเดอร์ใหม่
                            </button>
                        @endif
                    </div>

                    @if (in_array('reassign', $adminActions, true))
                        <div class="tp-divider" style="margin:14px 0;"></div>
                        <div style="font-size:13px; font-weight:700; margin-bottom:4px;"><i class="fas fa-user-plus"></i> มอบหมายไรเดอร์{{ $job->rider ? 'คนใหม่' : '' }}</div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-bottom:8px;">เฉพาะไรเดอร์ที่อนุมัติแล้ว ไม่มีงานค้าง และไม่ใช่ผู้ซื้อ/ผู้ขาย — เรียงจากใกล้จุดรับ</div>
                        @if (count($eligibleRiders) > 0)
                            <input type="search" x-model="reassignQuery" class="tp-input" style="padding:8px 12px; font-size:12.5px; margin-bottom:8px;" placeholder="ค้นหาชื่อหรือเบอร์">
                            <div style="display:flex; flex-direction:column; gap:7px; max-height:340px; overflow-y:auto; padding:2px;">
                                @foreach ($eligibleRiders as $candidate)
                                    <div class="tp-well flex" style="padding:9px 11px; align-items:center; gap:10px;"
                                         x-show="! reassignQuery || @js(mb_strtolower($candidate['full_name'].' '.$candidate['phone'])).includes(reassignQuery.toLowerCase())">
                                        <div style="flex:1; min-width:0;">
                                            <div style="font-weight:700; font-size:12.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $candidate['full_name'] }}</div>
                                            <div style="font-size:11px; color:var(--ink2);">
                                                {{ $candidate['availability_text'] }} · {{ $candidate['vehicle_type_text'] }}
                                                @if ($candidate['distance_to_pickup_km'] !== null) · ห่างจุดรับ {{ number_format($candidate['distance_to_pickup_km'], 1) }} กม. @endif
                                            </div>
                                        </div>
                                        <button type="button" class="tp-btn tp-btn-sm tp-btn-primary"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.rider-jobs.reassign', $job), 'title' => 'มอบหมายงานให้ '.$candidate['full_name'], 'message' => ($job->rider ? 'ไรเดอร์คนเดิม ('.$job->rider->full_name.') จะถูกถอดออกจากงาน ' : '').'ระบบจะตรวจงานค้างและวงเงิน COD ของไรเดอร์ใหม่อีกครั้ง แล้วแจ้งทุกฝ่าย', 'reason' => 'none', 'confirm' => 'มอบหมาย', 'tone' => 'info', 'icon' => 'fa-user-check', 'fields' => ['rider_id' => $candidate['id']]]))">
                                            มอบหมาย
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p style="font-size:12.5px; color:var(--ink2); margin:0;">ตอนนี้ไม่มีไรเดอร์ที่มอบหมายได้</p>
                        @endif
                    @endif
                @endif
            </div>

            {{-- ลูกค้า + ออเดอร์ต้นทาง --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-user"></i> ลูกค้า / ออเดอร์</div>
                <div style="display:grid; grid-template-columns:auto 1fr; gap:6px 12px; font-size:13px;">
                    <span style="color:var(--ink2);">ลูกค้า</span>
                    <span>{{ $job->customer?->name ?? '-' }} @if ($job->customer_id)<span style="color:var(--ink2);">#{{ $job->customer_id }}</span>@endif</span>
                    <span style="color:var(--ink2);">ต้นทาง</span>
                    <span>
                        @if (! empty($source['url']))
                            <a href="{{ $source['url'] }}" class="w1-link">{{ $sourceTypeLabel }} {{ $source['order_number'] ? '#'.$source['order_number'] : '#'.$source['id'] }}</a>
                        @elseif (! empty($source['type']))
                            {{ $sourceTypeLabel }} #{{ $source['id'] }}
                        @else
                            งานสร้างตรง (ไม่มีออเดอร์)
                        @endif
                    </span>
                    <span style="color:var(--ink2);">การกระจาย</span>
                    <span>{{ ['broadcast' => 'แจ้งทุกคนในรัศมี', 'cascade' => 'เสนอทีละคน', 'manual_needed' => 'รอแอดมินจัดเอง', 'manual' => 'แอดมินมอบหมาย'][$job->dispatch_type] ?? ($job->dispatch_type ?: '-') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if ($hasMap)
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('w1-job-map');
            if (!el || !window.W1Map) return;
            const pts = @js($mapPoints);
            const map = W1Map.create(el, { center: pts.pickup || pts.dropoff || W1Map.BANGKOK, zoom: 14 });
            const bounds = [];
            if (pts.path && pts.path.length > 1) {
                L.polyline(pts.path, { color: W1Map.color('gold'), weight: 5, opacity: .85 }).addTo(map);
                pts.path.forEach((p) => bounds.push(p));
            }
            if (pts.pickup && pts.dropoff) {
                L.polyline([pts.pickup, pts.dropoff], { color: W1Map.color('mute'), weight: 2, opacity: .6, dashArray: '6 8' }).addTo(map);
            }
            if (pts.pickup) {
                L.marker(pts.pickup, { icon: W1Map.pin('warn', 'fa-store', { label: 'จุดรับ' }) }).addTo(map);
                bounds.push(pts.pickup);
            }
            if (pts.dropoff) {
                L.marker(pts.dropoff, { icon: W1Map.pin('info', 'fa-flag-checkered', { label: 'จุดส่ง' }) }).addTo(map);
                bounds.push(pts.dropoff);
            }
            if (pts.rider) {
                L.marker(pts.rider, { icon: W1Map.pin('violet', 'fa-motorcycle', { pulse: true, label: 'ไรเดอร์' }), zIndexOffset: 800 }).addTo(map);
                bounds.push(pts.rider);
            }
            if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
        });
    </script>
    @endpush
@endif
