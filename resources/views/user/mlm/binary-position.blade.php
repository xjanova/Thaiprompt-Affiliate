@extends('layouts.user-v4')

@section('title', 'ตำแหน่งในระบบ Binary')

@php
    // ── ดึงค่าตำแหน่งจาก controller (genealogyService->getMemberPosition) ──
    $leftCount  = $position['left_count'] ?? 0;
    $rightCount = $position['right_count'] ?? 0;
    $leftPv     = $position['left_pv'] ?? 0;
    $rightPv    = $position['right_pv'] ?? 0;
    $leftCom    = $position['left_commission'] ?? 0;
    $rightCom   = $position['right_commission'] ?? 0;

    // ── คำนวณความสมดุลของทีม ───────────────────────────────────
    $totalPv        = $leftPv + $rightPv;
    $leftPercentage = $totalPv > 0 ? ($leftPv / $totalPv) * 100 : 50;
    $rightPercentage = $totalPv > 0 ? ($rightPv / $totalPv) * 100 : 50;
    $isBalanced     = abs($leftPv - $rightPv) / max($leftPv, $rightPv, 1) <= 0.3;

    // ── โบนัส Binary Matching ───────────────────────────────────
    $smallerPv     = min($leftPv, $rightPv) > 0 ? min($leftPv, $rightPv) : 0;
    $matchingRate  = $position['matching_rate'] ?? 10;
    $matchingBonus = $position['matching_bonus'] ?? 0;

    // ── สีฝั่งซ้าย/ขวา (hex คงที่ — ไม่ใช้ dynamic tailwind) ──────
    $cLeft    = '#5689b8';  // ซ้าย (info)
    $cRight   = '#8e6fb8';  // ขวา (ม่วงนวล)
    $cSuccess = '#5aa07e';
    $cWarn    = '#e0a52e';

    // ── ลิงก์วางตำแหน่ง (route guard) ───────────────────────────
    $leftLink  = \Illuminate\Support\Facades\Route::has('register')
        ? route('register', ['ref' => $member->member_code, 'position' => 'left'])
        : '';
    $rightLink = \Illuminate\Support\Facades\Route::has('register')
        ? route('register', ['ref' => $member->member_code, 'position' => 'right'])
        : '';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            @if(\Illuminate\Support\Facades\Route::has('user.mlm.dashboard'))
                <a href="{{ route('user.mlm.dashboard') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            @endif
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-sitemap" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ตำแหน่งในระบบ Binary</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">โครงสร้างทีมแบบ Binary Tree</div>
            </div>
        </div>
    </div>

    {{-- ── ข้อมูลตำแหน่งของคุณ (ซ้าย/ขวา) ─────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">📍 ข้อมูลตำแหน่งของคุณ</div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
            {{-- ฝั่งซ้าย --}}
            <div style="padding:18px; border-radius:16px; box-shadow:var(--inset-sm); border-left:4px solid {{ $cLeft }};">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:14px;">
                    <div style="font-size:15px; font-weight:700;">👈 ทีมซ้าย (Left)</div>
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:18px; font-weight:800; color:#fff; background:{{ $cLeft }};">L</span>
                </div>
                <div style="display:flex; flex-direction:column; gap:2px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                        <span style="font-size:12.5px; color:var(--ink2);">จำนวนสมาชิก:</span>
                        <span class="tp-num" style="font-weight:700;">{{ $leftCount }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                        <span style="font-size:12.5px; color:var(--ink2);">PV รวม:</span>
                        <span class="tp-num" style="font-weight:700; color:{{ $cLeft }};">{{ number_format($leftPv, 0) }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:9px 0;">
                        <span style="font-size:12.5px; color:var(--ink2);">รายได้สะสม:</span>
                        <span class="tp-num" style="font-weight:700; color:{{ $cSuccess }};">฿{{ number_format($leftCom, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- ฝั่งขวา --}}
            <div style="padding:18px; border-radius:16px; box-shadow:var(--inset-sm); border-left:4px solid {{ $cRight }};">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:14px;">
                    <div style="font-size:15px; font-weight:700;">👉 ทีมขวา (Right)</div>
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:18px; font-weight:800; color:#fff; background:{{ $cRight }};">R</span>
                </div>
                <div style="display:flex; flex-direction:column; gap:2px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                        <span style="font-size:12.5px; color:var(--ink2);">จำนวนสมาชิก:</span>
                        <span class="tp-num" style="font-weight:700;">{{ $rightCount }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                        <span style="font-size:12.5px; color:var(--ink2);">PV รวม:</span>
                        <span class="tp-num" style="font-weight:700; color:{{ $cRight }};">{{ number_format($rightPv, 0) }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:9px 0;">
                        <span style="font-size:12.5px; color:var(--ink2);">รายได้สะสม:</span>
                        <span class="tp-num" style="font-weight:700; color:{{ $cSuccess }};">฿{{ number_format($rightCom, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Binary Matching Bonus --}}
        <div style="margin-top:16px; padding:18px; border-radius:16px; box-shadow:var(--inset-sm); border-left:4px solid {{ $cSuccess }};">
            <div style="font-size:15px; font-weight:700; margin-bottom:14px;">⚖️ Binary Matching Bonus</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px;">
                <div style="text-align:center;">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">ทีมเล็กกว่า</div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; color:{{ $cWarn }};">{{ number_format($smallerPv, 0) }} PV</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">อัตราจับคู่</div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; color:{{ $cLeft }};">{{ $matchingRate }}%</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">โบนัสที่ได้</div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; color:{{ $cSuccess }};">฿{{ number_format($matchingBonus, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── สถานะความสมดุล ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">📊 สถานะความสมดุล</div>

        <div style="margin-bottom:14px;">
            <div style="display:flex; justify-content:space-between; gap:8px; font-size:12.5px; color:var(--ink2); margin-bottom:8px;">
                <span>👈 ซ้าย: {{ number_format($leftPv, 0) }} PV ({{ number_format($leftPercentage, 1) }}%)</span>
                <span>👉 ขวา: {{ number_format($rightPv, 0) }} PV ({{ number_format($rightPercentage, 1) }}%)</span>
            </div>
            {{-- แถบสมดุล ซ้าย/ขวา --}}
            <div style="display:flex; height:30px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden;">
                <div style="width:{{ $leftPercentage }}%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; font-weight:700;
                            background:linear-gradient(90deg, color-mix(in srgb, {{ $cLeft }} 80%, #000 0%), {{ $cLeft }});">
                    @if($leftPercentage > 15){{ number_format($leftPercentage, 0) }}%@endif
                </div>
                <div style="width:{{ $rightPercentage }}%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; font-weight:700;
                            background:linear-gradient(90deg, {{ $cRight }}, color-mix(in srgb, {{ $cRight }} 80%, #000 0%));">
                    @if($rightPercentage > 15){{ number_format($rightPercentage, 0) }}%@endif
                </div>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:14px; padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);
                    border-left:4px solid {{ $isBalanced ? $cSuccess : $cWarn }};">
            <span style="font-size:30px;">{{ $isBalanced ? '✅' : '⚠️' }}</span>
            <div>
                <div style="font-weight:700;">{{ $isBalanced ? 'ทีมสมดุล!' : 'ทีมไม่สมดุล' }}</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">
                    @if($isBalanced)
                        ทีมของคุณมีความสมดุลดี เหมาะสำหรับรับ Binary Matching Bonus สูงสุด
                    @else
                        ควรเพิ่มสมาชิกในทีมที่มี PV น้อยกว่า เพื่อเพิ่ม Binary Matching Bonus
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── ลิงก์วางตำแหน่ง ─────────────────────────────────────── --}}
    @if($leftLink || $rightLink)
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">🔗 ลิงก์วางตำแหน่ง</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:14px;">
            @if($leftLink)
            <div style="padding:14px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid {{ $cLeft }};">
                <div style="font-weight:700; margin-bottom:8px;">วางซ้าย (Left)</div>
                <div style="display:flex; gap:8px;">
                    <input type="text" id="leftLink" value="{{ $leftLink }}" readonly
                           class="tp-input tp-num" style="flex:1; min-width:0; font-size:12px;">
                    <button type="button" onclick="copyLink('leftLink')" class="tp-icon-btn" title="คัดลอกลิงก์ซ้าย"><i class="fas fa-copy"></i></button>
                </div>
            </div>
            @endif

            @if($rightLink)
            <div style="padding:14px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid {{ $cRight }};">
                <div style="font-weight:700; margin-bottom:8px;">วางขวา (Right)</div>
                <div style="display:flex; gap:8px;">
                    <input type="text" id="rightLink" value="{{ $rightLink }}" readonly
                           class="tp-input tp-num" style="flex:1; min-width:0; font-size:12px;">
                    <button type="button" onclick="copyLink('rightLink')" class="tp-icon-btn" title="คัดลอกลิงก์ขวา"><i class="fas fa-copy"></i></button>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ── เคล็ดลับ Binary System ───────────────────────────────── --}}
    <div class="tp-card" style="padding:18px; border-left:4px solid {{ $cWarn }};">
        <div class="tp-section-h" style="margin-bottom:14px;">💡 เคล็ดลับ Binary System</div>
        <ul style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px;">
            <li style="display:flex; gap:8px; font-size:13px; color:var(--ink);">
                <span>✅</span>
                <span><strong>รักษาความสมดุล:</strong> พยายามเพิ่มสมาชิกทั้งสองฝั่งให้สมดุล</span>
            </li>
            <li style="display:flex; gap:8px; font-size:13px; color:var(--ink);">
                <span>✅</span>
                <span><strong>Binary Matching:</strong> โบนัสคำนวณจากทีมที่เล็กกว่า</span>
            </li>
            <li style="display:flex; gap:8px; font-size:13px; color:var(--ink);">
                <span>✅</span>
                <span><strong>Spillover:</strong> สมาชิกใหม่อาจถูกวางในตำแหน่งที่เหมาะสม</span>
            </li>
            <li style="display:flex; gap:8px; font-size:13px; color:var(--ink);">
                <span>✅</span>
                <span><strong>PV สำคัญ:</strong> ไม่ใช่แค่จำนวนคน แต่ PV ก็สำคัญเช่นกัน</span>
            </li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
function copyLink(inputId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    input.select();
    navigator.clipboard.writeText(input.value).then(function () {
        if (window.showNotification) window.showNotification('คัดลอกลิงก์แล้ว', 'success');
    }).catch(function () {
        if (window.showNotification) window.showNotification('คัดลอกไม่สำเร็จ', 'error');
    });
}
</script>
@endpush
@endsection
