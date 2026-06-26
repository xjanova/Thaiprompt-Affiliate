@extends('layouts.user-v4')

@section('title', 'แดชบอร์ดแผน MLM')

@php
    // ── ชนิดแผน (แปลงเป็นป้ายอ่านง่าย) ────────────────────────────
    $planTypeMap = [
        'binary'   => 'Binary',
        'unilevel' => 'Unilevel',
        'matrix'   => 'Matrix',
        'hybrid'   => 'Hybrid',
    ];
    $planTypeLabel = $planTypeMap[$member->plan?->type] ?? ($member->plan?->type ?? 'N/A');

    // ── ป้ายสถานะสมาชิก (สี + ข้อความ + ไอคอน) ───────────────────
    $statusMap = [
        'active'  => ['สี' => '#5aa07e', 'ข้อความ' => 'ใช้งาน',    'ไอคอน' => 'fa-circle-check'],
        'pending' => ['สี' => '#e0a52e', 'ข้อความ' => 'รออนุมัติ', 'ไอคอน' => 'fa-clock'],
    ];
    $statusInfo = $statusMap[$member->status] ?? ['สี' => 'var(--ink2)', 'ข้อความ' => 'ไม่ใช้งาน', 'ไอคอน' => 'fa-circle-xmark'];

    // ── การ์ดสถิติหลัก 4 ใบ (ตรงกับ MlmDashboardController::plan) ─
    $kpis = [
        ['emoji' => '💰', 'label' => 'ค่าคอมมิชชั่นรวม', 'value' => '฿' . number_format($statistics['total_commissions'] ?? 0, 2)],
        ['emoji' => '⭐', 'label' => 'PV ส่วนตัว',       'value' => number_format($pvStats['personal_pv'] ?? 0, 0)],
        ['emoji' => '👥', 'label' => 'PV ทีม',           'value' => number_format($pvStats['group_pv'] ?? 0, 0)],
        ['emoji' => '🤝', 'label' => 'สมาชิกทางตรง',     'value' => $statistics['direct_referrals'] ?? 0],
    ];

    // ── เมนูด่วน (กรองเฉพาะ route ที่มีจริง) ──────────────────────
    $quickRaw = [
        ['👥', 'ทีมของฉัน',   'ดูสมาชิกในทีม',     'user.mlm.team'],
        ['💰', 'ค่าคอมมิชชั่น', 'รายละเอียดรายได้',  'user.mlm.commissions'],
        ['🌳', 'โครงสร้างทีม', 'ดูแผนผังองค์กร',     'user.mlm.genealogy'],
    ];
    $quick = [];
    foreach ($quickRaw as $q) {
        if (\Illuminate\Support\Facades\Route::has($q[3])) {
            $quick[] = [$q[0], $q[1], $q[2], route($q[3])];
        }
    }
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero: หัวเรื่องแผน MLM ──────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:58px; height:58px; border-radius:18px; font-size:28px;">📊</span>
            <div style="flex:1; min-width:200px;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">แผน MLM · MLM PLAN DASHBOARD</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">{{ $member->plan?->name ?? 'N/A' }}</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">รหัสสมาชิก: <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ $member->member_code }}</span></div>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:7px;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600;">สถานะ</div>
                <span class="tp-pill" style="color:#fff; background:{{ $statusInfo['สี'] }}; font-size:12.5px; padding:6px 14px;">
                    <i class="fas {{ $statusInfo['ไอคอน'] }}" style="font-size:11px;"></i> {{ $statusInfo['ข้อความ'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── การ์ดสถิติหลัก 4 ใบ ─────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px;">
        @foreach($kpis as $k)
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:13px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:22px;">{{ $k['emoji'] }}</span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $k['label'] }}</div>
                        <div class="tp-num" style="font-size:22px; font-weight:800; margin-top:3px; letter-spacing:.3px;">{{ $k['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── รายละเอียดแผน + ยศปัจจุบัน (2 คอลัมน์) ──────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">

        {{-- รายละเอียดแผน --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:14px;">📋 รายละเอียดแผน</div>
            <div style="display:flex; flex-direction:column;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 0; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <span style="font-size:13px; color:var(--ink2);">ชื่อแผน</span>
                    <span style="font-size:13.5px; font-weight:700; text-align:right;">{{ $member->plan?->name ?? 'N/A' }}</span>
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 0; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <span style="font-size:13px; color:var(--ink2);">ประเภท</span>
                    <span class="tp-pill tp-pill-soft" style="font-size:11.5px;">{{ $planTypeLabel }}</span>
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 0; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <span style="font-size:13px; color:var(--ink2);">วันที่เข้าร่วม</span>
                    <span class="tp-num" style="font-size:13.5px; font-weight:700;">{{ $member->created_at->format('d/m/Y') }}</span>
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 0;">
                    <span style="font-size:13px; color:var(--ink2);">ผู้แนะนำ</span>
                    <span style="font-size:13.5px; font-weight:700; text-align:right;">
                        @if($member->sponsor)
                            {{ $member->sponsor->user->name ?? 'N/A' }} <span class="tp-num" style="color:var(--deep1);">({{ $member->sponsor->member_code }})</span>
                        @else
                            -
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- ยศปัจจุบัน --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:14px;">🏆 ยศปัจจุบัน</div>
            @if($member->rank)
                <div style="text-align:center;">
                    <div style="display:flex; justify-content:center; margin-bottom:14px;">
                        <x-rank-icon :rank="$member->rank" size="xl" />
                    </div>
                    <h3 style="font-size:20px; font-weight:800; margin:0 0 6px;">{{ $member->rank->name }}</h3>
                    <p style="font-size:12.5px; color:var(--ink2); margin:0 0 14px;">{{ $member->rank->description ?? '' }}</p>
                    <div style="padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
                        <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">ระดับ</div>
                        <div class="tp-num" style="font-size:28px; font-weight:800; color:var(--deep1);">{{ $member->rank->level }}</div>
                    </div>
                </div>
            @else
                <div style="text-align:center; padding:24px 0;">
                    <div style="font-size:46px; opacity:.6;">🎯</div>
                    <div style="font-size:13px; color:var(--ink2); margin-top:8px;">ยังไม่มียศ</div>
                    @if(\Illuminate\Support\Facades\Route::has('user.ranks.dashboard'))
                        <a href="{{ route('user.ranks.dashboard') }}" class="tp-btn tp-btn-primary" style="margin-top:14px;">ดูเงื่อนไขยศ</a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ── รายละเอียดค่าคอมมิชชั่น ────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">💵 รายละเอียดค่าคอมมิชชั่น</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
            <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">ค่าคอมมิชชั่นรวม</div>
                <div class="tp-num" style="font-size:22px; font-weight:800; color:#5aa07e;">฿{{ number_format($statistics['total_commissions'] ?? 0, 2) }}</div>
            </div>
            <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">ค่าคอมมิชชั่นเดือนนี้</div>
                <div class="tp-num" style="font-size:22px; font-weight:800; color:#5689b8;">฿{{ number_format($statistics['month_commissions'] ?? 0, 2) }}</div>
            </div>
            <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">ค่าคอมมิชชั่นสัปดาห์นี้</div>
                <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">฿{{ number_format($statistics['week_commissions'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ── สถิติ PV (Point Value) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">⭐ สถิติ PV (Point Value)</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px;">
            <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">PV ส่วนตัว</div>
                <div class="tp-num" style="font-size:22px; font-weight:800; color:#5689b8;">{{ number_format($pvStats['personal_pv'] ?? 0, 0) }}</div>
            </div>
            <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">PV ทีม</div>
                <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">{{ number_format($pvStats['group_pv'] ?? 0, 0) }}</div>
            </div>
            <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">PV เดือนนี้</div>
                <div class="tp-num" style="font-size:22px; font-weight:800; color:#5aa07e;">{{ number_format($pvStats['month_pv'] ?? 0, 0) }}</div>
            </div>
            <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">PV สัปดาห์นี้</div>
                <div class="tp-num" style="font-size:22px; font-weight:800; color:#e0a52e;">{{ number_format($pvStats['week_pv'] ?? 0, 0) }}</div>
            </div>
        </div>
    </div>

    {{-- ── เมนูด่วน ────────────────────────────────────────────── --}}
    @if(count($quick) > 0)
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
        @foreach($quick as [$emoji, $title, $sub, $href])
            <a href="{{ $href }}" class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px; padding:18px; text-decoration:none; color:inherit;">
                <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:22px;">{{ $emoji }}</span>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:14px; font-weight:700;">{{ $title }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $sub }}</div>
                </div>
                <span style="font-size:18px; color:var(--deep1);">→</span>
            </a>
        @endforeach
    </div>
    @endif

</div>
@endsection
