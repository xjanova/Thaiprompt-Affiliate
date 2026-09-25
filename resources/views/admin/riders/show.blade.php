{{--
 | รายละเอียดไรเดอร์ (admin.riders.show) — ธีม V4
 | ตัวแปรจาก Admin\RiderController@show:
 |   $rider (with user), $jobStats{total,completed,cancelled,failed,earnings}, $recentJobs, $services,
 |   $documents[{type,label,uploaded,required,url}] (url = admin.riders.document — private disk), $missingDocuments, $missingDocumentLabels,
 |   $canApprove, $documentsChangedAt, $activeJob, $walletBalance, $lastLocation{latitude,longitude,updated_at,updated_ago,is_stale}|null,
 |   $riderData (RiderAccountService::statusPayload), $pageTitle
 | การกระทำ (ฟอร์ม POST ผ่านโมดัลยืนยัน): approve, reject{reason}, suspend{reason}, toggle-active (ยกเลิกระงับ), documents-reviewed
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'รายละเอียดไรเดอร์')

@section('content')
@include('admin.riders.partials.v4-kit')
@include('admin.riders.partials.doc-viewer')
@if ($lastLocation)
    @include('admin.riders.partials.leaflet')
@endif
@php
    $actorIds = array_values(array_filter([$rider->approved_by, $rider->rejected_by, $rider->suspended_by]));
    $actorNames = $actorIds !== [] ? \App\Models\User::withTrashed()->whereIn('id', $actorIds)->pluck('name', 'id') : collect();
    $viewerItems = collect($documents)->filter(fn ($d) => ! empty($d['url']))->map(fn ($d) => ['label' => $d['label'], 'url' => $d['url']])->values()->all();
    $blockReason = $riderData['block_reason'] ?? null;
    $permissions = $riderData['permissions'] ?? [];
    $deposit = $riderData['deposit'] ?? [];
    $kyc = $riderData['kyc'] ?? [];
    $infoRows = [
        ['เลขบัตรประชาชน', $riderData['id_card_number_masked'] ?? '-'],
        ['วันเกิด', $rider->birth_date?->thaidate('j M Y') ?? '-'],
        ['ที่อยู่', $rider->address ?: '-'],
        ['พื้นที่', collect([$rider->district, $rider->province])->filter()->implode(', ') ?: '-'],
        ['ยานพาหนะ', $rider->vehicle_type_text.($rider->vehicle_brand ? ' · '.$rider->vehicle_brand : '').($rider->vehicle_color ? ' · สี'.$rider->vehicle_color : '')],
        ['ทะเบียนรถ', $rider->vehicle_plate ?: '-'],
        ['ประเภท', $rider->rider_type_text],
        ['สมัครเมื่อ', $rider->created_at?->thaidate('j M Y H:i') ?? '-'],
        ['อนุมัติเมื่อ', $rider->approved_at ? $rider->approved_at->thaidate('j M Y H:i').($rider->approved_by ? ' โดย '.($actorNames[$rider->approved_by] ?? '#'.$rider->approved_by) : '') : '-'],
        ['ยินยอมแชร์ตำแหน่งให้ลูกค้า', $rider->share_location_consent_at ? $rider->share_location_consent_at->thaidate('j M Y H:i') : 'ยังไม่ยินยอม (รับงานแรกไม่ได้)'],
    ];
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง + ปุ่มจัดการ ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
            <a href="{{ route('admin.riders.index') }}" class="tp-icon-btn" title="กลับหน้ารายชื่อไรเดอร์"><i class="fas fa-arrow-left"></i></a>
            <div style="min-width:0;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ไรเดอร์ · #{{ $rider->id }}</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0; display:flex; flex-wrap:wrap; align-items:center; gap:9px;">
                    {{ $rider->full_name }}
                    @include('admin.riders.partials.status', ['statusKind' => 'rider', 'statusValue' => $rider->status, 'statusLabel' => null])
                    @include('admin.riders.partials.status', ['statusKind' => 'availability', 'statusValue' => $rider->availability, 'statusLabel' => null])
                </h1>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <a href="{{ route('admin.riders.locations', $rider) }}" class="tp-btn tp-btn-sm"><i class="fas fa-location-crosshairs"></i> ตำแหน่ง GPS</a>
            <a href="{{ route('admin.rider-jobs.index', ['rider_id' => $rider->id]) }}" class="tp-btn tp-btn-sm"><i class="fas fa-list-check"></i> งานทั้งหมด</a>
            @if ($canApprove)
                <button type="button" class="tp-btn tp-btn-sm" style="color:var(--w-on); background:linear-gradient(135deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 72%, var(--ink)));"
                        @click="$dispatch('w1-action', @js(['url' => route('admin.riders.approve', $rider), 'title' => 'อนุมัติ '.$rider->full_name, 'message' => 'เอกสารที่บังคับครบแล้ว ไรเดอร์จะได้รับแจ้งเตือนในแอปทันที', 'reason' => 'none', 'confirm' => 'อนุมัติ', 'tone' => 'ok', 'icon' => 'fa-circle-check']))">
                    <i class="fas fa-check"></i> อนุมัติ
                </button>
            @endif
            @if (in_array($rider->status, ['pending', 'inactive'], true))
                <button type="button" class="tp-btn tp-btn-sm" style="color:var(--w-bad);"
                        @click="$dispatch('w1-action', @js(['url' => route('admin.riders.reject', $rider), 'title' => 'ปฏิเสธใบสมัคร', 'message' => 'ไรเดอร์จะเห็นเหตุผลในแอป และแก้ไขแล้วส่งใหม่ได้', 'reason' => 'required', 'reasonLabel' => 'เหตุผลที่ไม่อนุมัติ', 'placeholder' => 'เช่น รูปบัตรประชาชนไม่ชัด กรุณาถ่ายใหม่', 'confirm' => 'ปฏิเสธ', 'tone' => 'bad', 'icon' => 'fa-circle-xmark']))">
                    <i class="fas fa-xmark"></i> ปฏิเสธ
                </button>
            @endif
            @if ($rider->status === 'approved')
                <button type="button" class="tp-btn tp-btn-sm" style="color:var(--w-bad);"
                        @click="$dispatch('w1-action', @js(['url' => route('admin.riders.suspend', $rider), 'title' => 'ระงับไรเดอร์', 'message' => "ไรเดอร์จะถูกบังคับออฟไลน์ทันที\nงานที่ยังไม่รับของจะคืนเข้าคิว — งานที่รับของแล้วต้องมอบหมายไรเดอร์ใหม่หรือปิดงาน", 'reason' => 'required', 'reasonLabel' => 'เหตุผลที่ระงับ (ไรเดอร์จะเห็น)', 'confirm' => 'ระงับ', 'tone' => 'bad', 'icon' => 'fa-ban']))">
                    <i class="fas fa-ban"></i> ระงับ
                </button>
            @elseif ($rider->status === 'suspended')
                <button type="button" class="tp-btn tp-btn-sm" style="color:var(--w-on); background:linear-gradient(135deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 72%, var(--ink)));"
                        @click="$dispatch('w1-action', @js(['url' => route('admin.riders.toggle-active', $rider), 'title' => 'ยกเลิกการระงับ', 'message' => 'บัญชีกลับเป็น "อนุมัติแล้ว" แต่ยังออฟไลน์ ไรเดอร์ต้องกดเปิดรับงานเอง', 'reason' => 'none', 'confirm' => 'ยกเลิกการระงับ', 'tone' => 'ok', 'icon' => 'fa-unlock']))">
                    <i class="fas fa-unlock"></i> ยกเลิกการระงับ
                </button>
            @endif
        </div>
    </div>

    @include('admin.riders.partials.flash')

    {{-- ===== แถบแจ้งเตือนสถานะสำคัญ ===== --}}
    @if ($rider->status === 'rejected' && $rider->rejection_reason)
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--w-bad);">
            <div style="font-weight:700; font-size:13.5px;"><i class="fas fa-circle-xmark" style="color:var(--w-bad);"></i> ใบสมัครถูกปฏิเสธ</div>
            <div style="font-size:13px; margin-top:4px;">{{ $rider->rejection_reason }}</div>
            <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">
                {{ $rider->rejected_at?->thaidate('j M Y H:i') }}
                @if ($rider->rejected_by) · โดย {{ $actorNames[$rider->rejected_by] ?? '#'.$rider->rejected_by }} @endif
            </div>
        </div>
    @endif
    @if ($rider->suspended_at || $rider->status === 'suspended')
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--w-bad);">
            <div style="font-weight:700; font-size:13.5px;"><i class="fas fa-ban" style="color:var(--w-bad);"></i> บัญชีถูกระงับ</div>
            <div style="font-size:13px; margin-top:4px;">{{ $rider->suspension_reason ?: 'ไม่ระบุเหตุผล' }}</div>
            <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">
                {{ $rider->suspended_at?->thaidate('j M Y H:i') }}
                @if ($rider->suspended_by) · โดย {{ $actorNames[$rider->suspended_by] ?? '#'.$rider->suspended_by }} @endif
            </div>
        </div>
    @endif
    @if ($documentsChangedAt)
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--w-info); display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
            <div>
                <div style="font-weight:700; font-size:13.5px;"><i class="fas fa-file-circle-exclamation" style="color:var(--w-info);"></i> ไรเดอร์เปลี่ยนเอกสาร/ยานพาหนะหลังอนุมัติ</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">เปลี่ยนเมื่อ {{ $documentsChangedAt->thaidate('j M Y H:i') }} — ระหว่างนี้ไรเดอร์รับงานไม่ได้จนกว่าจะตรวจ</div>
            </div>
            <button type="button" class="tp-btn tp-btn-sm tp-btn-primary"
                    @click="$dispatch('w1-action', @js(['url' => route('admin.riders.documents-reviewed', $rider), 'title' => 'ยืนยันว่าตรวจเอกสารใหม่แล้ว', 'message' => 'ไรเดอร์จะกลับมารับงานได้ทันที กรุณาดูเอกสารทุกใบให้ครบก่อนกดยืนยัน', 'reason' => 'none', 'confirm' => 'ตรวจแล้ว ใช้งานต่อได้', 'tone' => 'info', 'icon' => 'fa-file-circle-check']))">
                <i class="fas fa-file-circle-check"></i> ตรวจเอกสารแล้ว
            </button>
        </div>
    @endif
    @if ($rider->status === 'approved' && $blockReason)
        <div class="tp-card" style="padding:12px 18px; border-left:4px solid var(--w-warn); font-size:13px;">
            <i class="fas fa-circle-info" style="color:var(--w-warn);"></i>
            ตอนนี้ไรเดอร์ยังรับงานใหม่ไม่ได้: <b>{{ $blockReason['message'] ?? '-' }}</b>
        </div>
    @endif

    {{-- ===== ตัวเลขผลงาน ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-box', 'kpiValue' => number_format($jobStats['total']), 'kpiLabel' => 'งานทั้งหมด', 'kpiTone' => null, 'kpiHref' => route('admin.rider-jobs.index', ['rider_id' => $rider->id]), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-circle-check', 'kpiValue' => number_format($jobStats['completed']), 'kpiLabel' => 'ส่งสำเร็จ', 'kpiTone' => 'ok', 'kpiHref' => route('admin.rider-jobs.index', ['rider_id' => $rider->id, 'status' => 'completed']), 'kpiHint' => $jobStats['total'] > 0 ? 'อัตราสำเร็จ '.number_format($jobStats['completed'] / $jobStats['total'] * 100, 1).'%' : null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-ban', 'kpiValue' => number_format($jobStats['cancelled'] + $jobStats['failed']), 'kpiLabel' => 'ยกเลิก / ส่งไม่สำเร็จ', 'kpiTone' => 'bad', 'kpiHref' => null, 'kpiHint' => 'ยกเลิก '.number_format($jobStats['cancelled']).' · ไม่สำเร็จ '.number_format($jobStats['failed']), 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-coins', 'kpiValue' => '฿'.number_format($jobStats['earnings'], 2), 'kpiLabel' => 'รายได้จากงานสำเร็จ', 'kpiTone' => 'violet', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-wallet', 'kpiValue' => '฿'.number_format($walletBalance, 2), 'kpiLabel' => 'ยอดวอลเลต', 'kpiTone' => 'info', 'kpiHref' => null, 'kpiHint' => 'วงเงินรับงาน COD ฿'.number_format((float) ($riderData['cod_credit_available'] ?? 0), 2), 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-star', 'kpiValue' => number_format((float) $rider->rating, 2), 'kpiLabel' => 'คะแนนเฉลี่ย', 'kpiTone' => null, 'kpiHref' => null, 'kpiHint' => number_format((int) $rider->rating_count).' รีวิว', 'kpiPulse' => false])
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr)); gap:16px; align-items:start;">

        {{-- ===== โปรไฟล์ ===== --}}
        <div class="tp-card" style="padding:20px;">
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:14px;">
                @if ($rider->profile_image)
                    <button type="button" style="border:0; padding:0; background:none; cursor:zoom-in;"
                            @click="$dispatch('w1-doc', @js(['items' => $viewerItems, 'index' => max(0, count($viewerItems) - 1)]))">
                        <img src="{{ route('admin.riders.document', [$rider, 'profile']) }}" alt="" style="width:64px; height:64px; border-radius:50%; object-fit:cover; box-shadow:var(--raise);">
                    </button>
                @else
                    <span class="tp-tile" style="width:64px; height:64px; border-radius:50%; font-size:24px; font-weight:800;">{{ mb_substr($rider->full_name ?: 'R', 0, 1) }}</span>
                @endif
                <div style="min-width:0;">
                    <div style="font-weight:700; font-size:16px;">{{ $rider->full_name }}</div>
                    <div style="font-size:13px;"><a href="tel:{{ $rider->phone }}" class="w1-link">{{ $rider->phone }}</a></div>
                    <div style="font-size:12px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis;">{{ $rider->user?->email ?? '-' }} · ผู้ใช้ #{{ $rider->user_id }}</div>
                </div>
            </div>
            <div class="tp-divider" style="margin:4px 0 12px;"></div>
            <div style="display:grid; grid-template-columns:minmax(110px,auto) 1fr; gap:8px 14px; font-size:13px;">
                @foreach ($infoRows as [$infoLabel, $infoValue])
                    <span style="color:var(--ink2);">{{ $infoLabel }}</span>
                    <span style="font-weight:600; word-break:break-word;">{{ $infoValue }}</span>
                @endforeach
            </div>
        </div>

        {{-- ===== เอกสาร (ตัวดูในหน้า) ===== --}}
        <div class="tp-card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:12px;">
                <div class="tp-section-h"><i class="fas fa-id-card"></i> เอกสารยืนยันตัวตน</div>
                @if ($missingDocuments === [])
                    @include('admin.riders.partials.pill', ['pillTone' => 'ok', 'pillText' => 'ครบ', 'pillIcon' => 'fa-circle-check', 'pillTitle' => null])
                @else
                    @include('admin.riders.partials.pill', ['pillTone' => 'bad', 'pillText' => 'ขาด '.count($missingDocuments), 'pillIcon' => 'fa-triangle-exclamation', 'pillTitle' => $missingDocumentLabels])
                @endif
            </div>
            <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px;">
                @foreach ($documents as $doc)
                    @if ($doc['url'])
                        @php
                            $docIndex = collect($viewerItems)->search(fn ($d) => $d['url'] === $doc['url']);
                            $docIndex = $docIndex === false ? 0 : (int) $docIndex;
                        @endphp
                        <button type="button"
                                style="border:0; padding:0; cursor:zoom-in; text-align:left; background:none; color:var(--ink);"
                                @click="$dispatch('w1-doc', @js(['items' => $viewerItems, 'index' => $docIndex]))">
                            <span style="display:block; border-radius:13px; overflow:hidden; box-shadow:var(--inset-sm); background:var(--bg); aspect-ratio:4/3;">
                                <img src="{{ $doc['url'] }}" alt="{{ $doc['label'] }}" loading="lazy" style="width:100%; height:100%; object-fit:cover; display:block;">
                            </span>
                            <span style="display:flex; align-items:center; gap:6px; font-size:12.5px; font-weight:600; margin-top:6px;">
                                <i class="fas fa-magnifying-glass-plus" style="color:var(--ink2);"></i> {{ $doc['label'] }}
                            </span>
                        </button>
                    @else
                        <div>
                            <div style="border-radius:13px; aspect-ratio:4/3; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; box-shadow:var(--inset-sm); color:{{ $doc['required'] ? 'var(--w-bad)' : 'var(--ink2)' }}; font-size:12px; text-align:center; padding:8px;">
                                <i class="fas {{ $doc['required'] ? 'fa-file-circle-exclamation' : 'fa-file' }}" style="font-size:22px;"></i>
                                {{ $doc['required'] ? 'ยังไม่อัปโหลด (บังคับ)' : 'ไม่มีไฟล์ (ไม่บังคับ)' }}
                            </div>
                            <div style="font-size:12.5px; font-weight:600; margin-top:6px;">{{ $doc['label'] }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
            <p style="font-size:11.5px; color:var(--ink2); margin:12px 0 0;"><i class="fas fa-lock"></i> ไฟล์เก็บแบบส่วนตัว เปิดได้เฉพาะแอดมินที่ล็อกอิน และทุกครั้งที่เปิดจะถูกบันทึกไว้</p>
        </div>

        {{-- ===== งานปัจจุบัน + ตำแหน่ง ===== --}}
        <div style="display:flex; flex-direction:column; gap:16px;">
            @if ($activeJob)
                <a href="{{ route('admin.rider-jobs.show', $activeJob) }}" class="tp-card tp-card-hover" style="padding:18px; text-decoration:none; color:var(--ink); border-left:4px solid var(--w-violet);">
                    <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                        <div class="tp-section-h"><i class="fas fa-truck-fast"></i> งานที่กำลังทำ</div>
                        @include('admin.riders.partials.status', ['statusKind' => 'job', 'statusValue' => $activeJob->status, 'statusLabel' => null])
                    </div>
                    <div class="tp-num" style="font-weight:700; margin-top:8px;">#{{ $activeJob->job_number }}</div>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;"><i class="fas fa-store"></i> {{ $activeJob->pickup_address ?: '-' }}</div>
                    <div style="font-size:12.5px; color:var(--ink2);"><i class="fas fa-flag-checkered"></i> {{ $activeJob->delivery_address ?: '-' }}</div>
                </a>
            @endif

            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; justify-content:space-between; gap:8px; align-items:center; margin-bottom:10px;">
                    <div class="tp-section-h"><i class="fas fa-location-dot"></i> ตำแหน่งล่าสุด</div>
                    @if ($lastLocation)
                        @include('admin.riders.partials.pill', ['pillTone' => $lastLocation['is_stale'] ? 'warn' : 'ok', 'pillText' => $lastLocation['is_stale'] ? 'สัญญาณเงียบ' : 'สด', 'pillIcon' => $lastLocation['is_stale'] ? 'fa-signal' : 'fa-satellite-dish', 'pillTitle' => null])
                    @endif
                </div>
                @if ($lastLocation)
                    <div id="w1-rider-mini-map" style="height:220px; border-radius:15px; overflow:hidden; box-shadow:var(--inset-sm);"></div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:8px;">
                        อัปเดต {{ $lastLocation['updated_ago'] ?? '-' }} ·
                        <span class="tp-num">{{ number_format($lastLocation['latitude'], 5) }}, {{ number_format($lastLocation['longitude'], 5) }}</span>
                    </div>
                    <div style="display:flex; gap:8px; margin-top:10px; flex-wrap:wrap;">
                        <a href="{{ route('admin.riders.locations', $rider) }}" class="tp-btn tp-btn-sm" style="flex:1;"><i class="fas fa-route"></i> เส้นทาง 24 ชม.</a>
                        <a href="{{ route('admin.riders.playback', $rider) }}" class="tp-btn tp-btn-sm" style="flex:1;"><i class="fas fa-circle-play"></i> เล่นย้อนหลัง</a>
                    </div>
                @else
                    <div style="text-align:center; color:var(--ink2); padding:24px 8px; font-size:13px;">
                        <i class="fas fa-location-crosshairs" style="font-size:24px; opacity:.5; display:block; margin-bottom:8px;"></i>
                        ยังไม่เคยส่งตำแหน่ง GPS
                    </div>
                @endif
            </div>

            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-shield-halved"></i> สิทธิ์ / มัดจำ / KYC</div>
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    @foreach (['gps' => 'GPS', 'camera' => 'กล้อง', 'notification' => 'แจ้งเตือน', 'location_consent' => 'แชร์ตำแหน่งให้ลูกค้า'] as $permKey => $permLabel)
                        @include('admin.riders.partials.pill', ['pillTone' => ! empty($permissions[$permKey]) ? 'ok' : 'mute', 'pillText' => $permLabel, 'pillIcon' => ! empty($permissions[$permKey]) ? 'fa-check' : 'fa-xmark', 'pillTitle' => null])
                    @endforeach
                </div>
                <div class="tp-divider" style="margin:12px 0;"></div>
                <div style="display:grid; grid-template-columns:auto 1fr; gap:6px 12px; font-size:13px;">
                    <span style="color:var(--ink2);">เงินประกัน</span>
                    <span>
                        @if (! empty($deposit['required']))
                            {{ $rider->deposit_status_text }} @if (($deposit['amount'] ?? 0) > 0) · ฿{{ number_format((float) $deposit['amount'], 2) }} @endif
                        @else
                            ไม่บังคับ (ปิดอยู่ในตั้งค่า)
                        @endif
                    </span>
                    <span style="color:var(--ink2);">KYC</span>
                    <span>{{ ! empty($kyc['verified']) ? 'ยืนยันตัวตนแล้ว' : 'ยังไม่ยืนยัน (ถอนรายได้ไม่ได้)' }}</span>
                </div>
            </div>

            @if (count($services) > 0)
                <div class="tp-card" style="padding:18px;">
                    <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-screwdriver-wrench"></i> บริการที่รับ</div>
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        @foreach ($services as $service)
                            <span class="tp-pill tp-pill-soft">{{ $service->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== งานล่าสุด ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
            <div class="tp-section-h"><i class="fas fa-clock-rotate-left"></i> งานล่าสุด 10 งาน</div>
            <a href="{{ route('admin.rider-jobs.index', ['rider_id' => $rider->id]) }}" class="w1-link" style="font-size:12.5px;">ดูทั้งหมด →</a>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:720px; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                        <th style="padding:9px 18px;">งาน</th>
                        <th style="padding:9px 12px;">เส้นทาง</th>
                        <th style="padding:9px 12px; text-align:right;">รายได้ไรเดอร์</th>
                        <th style="padding:9px 12px;">สถานะ</th>
                        <th style="padding:9px 18px;">เวลา</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentJobs as $job)
                        <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <td style="padding:11px 18px;"><a href="{{ route('admin.rider-jobs.show', $job) }}" class="w1-link tp-num">#{{ $job->job_number }}</a><div style="font-size:11.5px; color:var(--ink2);">{{ $job->job_type_text }}</div></td>
                            <td style="padding:11px 12px; max-width:320px;">
                                <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><i class="fas fa-store" style="color:var(--ink2); width:14px;"></i> {{ $job->pickup_address ?: '-' }}</div>
                                <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--ink2);"><i class="fas fa-flag-checkered" style="width:14px;"></i> {{ $job->delivery_address ?: '-' }}</div>
                            </td>
                            <td style="padding:11px 12px; text-align:right;" class="tp-num">฿{{ number_format((float) $job->rider_earnings, 2) }}</td>
                            <td style="padding:11px 12px;">@include('admin.riders.partials.status', ['statusKind' => 'job', 'statusValue' => $job->status, 'statusLabel' => null])</td>
                            <td style="padding:11px 18px; white-space:nowrap; color:var(--ink2);">{{ $job->created_at?->thaidate('j M H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:32px; text-align:center; color:var(--ink2);">ยังไม่มีประวัติงาน</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@if ($lastLocation)
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('w1-rider-mini-map');
            if (!el || !window.W1Map) return;
            const point = @js([$lastLocation['latitude'], $lastLocation['longitude']]);
            const map = W1Map.create(el, { center: point, zoom: 15, scrollWheelZoom: false });
            L.marker(point, { icon: W1Map.pin(@js($lastLocation['is_stale'] ? 'warn' : 'ok'), 'fa-motorcycle', { pulse: @js(! $lastLocation['is_stale']) }) })
                .addTo(map)
                .bindPopup(W1Map.esc(@js($rider->full_name)) + '<br>' + W1Map.esc(@js('อัปเดต '.($lastLocation['updated_ago'] ?? '-'))));
        });
    </script>
    @endpush
@endif
