@extends('layouts.user-v4')

@section('title', 'ทีมของฉัน - MLM')

@section('content')
{{-- แสดงข้อความแนะนำถ้าไม่ได้เป็น MLM Member --}}
@if(isset($notMlmMember) && $notMlmMember)
    {{-- ── การ์ดแนะนำให้สมัคร MLM ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:20px; padding:28px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
            <span class="tp-tile" style="width:64px; height:64px; border-radius:20px; font-size:30px; flex-shrink:0;">
                <i class="fas fa-triangle-exclamation" style="color:#fff;"></i>
            </span>
            <div style="flex:1; min-width:220px;">
                <h2 class="tp-num" style="font-size:clamp(18px,4vw,24px); font-weight:800; margin:0;">คุณยังไม่ได้เป็นสมาชิก MLM</h2>
                <div style="font-size:13px; color:var(--ink2); margin-top:6px;">เพื่อดูทีมงานและสร้างเครือข่าย กรุณาสมัครเป็นสมาชิก MLM ก่อน</div>
                <a href="{{ route('user.mlm.dashboard') }}" class="tp-btn tp-btn-primary" style="margin-top:14px;">
                    <i class="fas fa-arrow-right" style="font-size:12px;"></i> ไปหน้า MLM Dashboard
                </a>
            </div>
        </div>
    </div>
@else

@php
    // ── สถิติ 4 ใบ (นับจำนวนสมาชิกทางตรง) ──────────────────────
    $totalReferrals   = $directReferrals->total();
    $activeReferrals  = $directReferrals->where('status', 'active')->count();
    $pendingReferrals = $directReferrals->where('status', 'pending')->count();
    $monthReferrals   = $directReferrals->filter(function ($ref) {
        return $ref->created_at->isCurrentMonth();
    })->count();

    $teamStats = [
        ['label' => 'สมาชิกทางตรง', 'en' => 'Direct',  'val' => $totalReferrals,   'color' => 'var(--deep1)', 'soft' => 'color-mix(in srgb, var(--accent1) 16%, transparent)', 'icon' => '👤'],
        ['label' => 'ใช้งาน',       'en' => 'Active',  'val' => $activeReferrals,  'color' => '#5aa07e',      'soft' => 'rgba(90,160,126,.16)', 'icon' => '✅'],
        ['label' => 'รออนุมัติ',    'en' => 'Pending', 'val' => $pendingReferrals, 'color' => '#e0a52e',      'soft' => 'rgba(224,165,46,.16)', 'icon' => '⏳'],
        ['label' => 'เดือนนี้',     'en' => 'Month',   'val' => $monthReferrals,   'color' => '#5689b8',      'soft' => 'rgba(86,137,184,.16)', 'icon' => '📅'],
    ];
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden; position:relative;">
        {{-- ภาพประกอบหัวเรื่อง (เจนเอง เก็บที่ public/images/art) --}}
        <x-art.hero-art image="usr-team" />
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-user-friends" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ทีมของฉัน</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">สมาชิกที่แนะนำทางตรง (Direct Referrals)</div>
            </div>
        </div>
    </div>

    {{-- ── สถิติ 4 ใบ ───────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        @foreach($teamStats as $c)
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="min-width:0;">
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $c['label'] }}</div>
                        <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $c['en'] }}</div>
                    </div>
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:18px; background:{{ $c['soft'] }};">{{ $c['icon'] }}</span>
                </div>
                <div class="tp-num" style="font-size:30px; font-weight:800; margin-top:10px; color:{{ $c['color'] }};">{{ number_format($c['val']) }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── รายชื่อสมาชิกในทีม ───────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <h2 class="tp-num" style="font-size:16px; font-weight:800; margin:0; display:flex; align-items:center; gap:8px;">
                <span>📋</span> รายชื่อสมาชิกในทีม
            </h2>
        </div>

        @if($directReferrals->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            @foreach(['#','สมาชิก','รหัสสมาชิก','วันที่เข้าร่วม','สถานะ','ยศ'] as $h)
                                <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($directReferrals as $index => $referral)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                {{-- ลำดับ --}}
                                <td class="tp-num" style="padding:12px 16px; white-space:nowrap; color:var(--ink2);">
                                    {{ ($directReferrals->currentPage() - 1) * $directReferrals->perPage() + $index + 1 }}
                                </td>
                                {{-- สมาชิก --}}
                                <td style="padding:12px 16px;">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <span class="tp-tile" style="width:40px; height:40px; border-radius:50%; font-size:16px; font-weight:800; color:#fff; flex-shrink:0;">
                                            {{ strtoupper(substr($referral->user->name ?? 'U', 0, 1)) }}
                                        </span>
                                        <div style="min-width:0;">
                                            <div style="font-weight:700; color:var(--ink);">{{ $referral->user->name ?? 'N/A' }}</div>
                                            <div style="font-size:12px; color:var(--ink2);">{{ $referral->user->email ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                {{-- รหัสสมาชิก --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <code class="tp-num" style="padding:5px 11px; border-radius:9px; box-shadow:var(--inset-sm); font-size:12px; color:var(--ink);">{{ $referral->member_code }}</code>
                                </td>
                                {{-- วันที่เข้าร่วม --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <div class="tp-num" style="color:var(--ink);">{{ $referral->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11px; color:var(--ink2);">{{ $referral->created_at->diffForHumans() }}</div>
                                </td>
                                {{-- สถานะ --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    {{-- สถานะบัญชี --}}
                                    @if($referral->status === 'active')
                                        <span class="tp-pill" style="color:#fff; background:#5aa07e;">✅ ใช้งาน</span>
                                    @elseif($referral->status === 'pending')
                                        <span class="tp-pill" style="color:#fff; background:#e0a52e;">⏳ รออนุมัติ</span>
                                    @elseif($referral->status === 'inactive')
                                        <span class="tp-pill tp-pill-soft">❌ ไม่ใช้งาน</span>
                                    @else
                                        <span class="tp-pill" style="color:#fff; background:#d9534f;">🚫 ระงับ</span>
                                    @endif

                                    {{-- สถานะรักษายอด (volume retention) --}}
                                    @if($referral->status === 'active')
                                        @php
                                            $refRetention = \App\Helpers\MlmRetentionHelper::getRetentionStatus($referral);
                                        @endphp
                                        @if(($refRetention['retention_enabled'] ?? false) && ($refRetention['status'] ?? 'active') !== 'active')
                                            @if($refRetention['status'] === 'grace_period')
                                                <span class="tp-pill" style="margin-left:5px; color:#fff; background:#e0a52e;">⏳ ผ่อนผัน</span>
                                            @else
                                                <span class="tp-pill" style="margin-left:5px; color:#fff; background:#d9534f;">PV ไม่ถึง</span>
                                            @endif
                                        @endif
                                    @endif
                                </td>
                                {{-- ยศ --}}
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    @if($referral->rank)
                                        <x-rank-icon :rank="$referral->rank" size="sm" show-name />
                                    @else
                                        <span style="font-size:13px; color:var(--ink2);">ไม่มียศ</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($directReferrals->hasPages())
                <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    {{ $directReferrals->links() }}
                </div>
            @endif
        @else
            {{-- Empty state --}}
            <div style="text-align:center; padding:56px 20px;">
                <div style="font-size:52px; opacity:.5;">👥</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ยังไม่มีสมาชิกในทีม</div>
                <a href="{{ route('user.mlm.referral') }}" style="display:inline-block; margin-top:10px; font-weight:700; color:var(--deep1); text-decoration:none;">
                    ดูลิงก์แนะนำ →
                </a>
            </div>
        @endif
    </div>

    {{-- ── เมนูด่วน (Quick Actions) ──────────────────────────── --}}
    @php
        $quickActions = [
            ['🔗', 'ลิงก์แนะนำ',   'แชร์ลิงก์เชิญเพื่อน', 'user.mlm.referral'],
            ['🌳', 'โครงสร้างทีม', 'ดูแผนผังองค์กร',     'user.mlm.genealogy'],
            ['📊', 'Dashboard',     'กลับหน้าหลัก MLM',    'user.mlm.dashboard'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
        @foreach($quickActions as $a)
            @if(\Illuminate\Support\Facades\Route::has($a[3]))
                <a href="{{ route($a[3]) }}" class="tp-card tp-card-hover" style="padding:18px; text-decoration:none; color:inherit;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:22px; background:color-mix(in srgb, var(--accent1) 16%, transparent); flex-shrink:0;">{{ $a[0] }}</span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:800; color:var(--ink);">{{ $a[1] }}</div>
                            <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">{{ $a[2] }}</div>
                        </div>
                        <span style="color:var(--deep1); font-weight:800;">→</span>
                    </div>
                </a>
            @endif
        @endforeach
    </div>
</div>
@endif
@endsection
