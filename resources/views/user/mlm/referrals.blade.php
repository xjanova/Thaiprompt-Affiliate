@extends('layouts.user-v4')

@section('title', 'สมาชิกที่แนะนำ')

@php
    // ── นับสถิติจากรายการในหน้าปัจจุบัน (รักษาตรรกะเดิม) ──────────
    $totalReferrals    = $directReferrals->total();
    $activeReferrals   = $directReferrals->where('status', 'active')->count();
    $thisMonthReferrals = $directReferrals->filter(fn($r) => $r->created_at->isCurrentMonth())->count();

    // ── การ์ดสถิติ 3 ใบ ──────────────────────────────────────
    $cards = [
        ['label' => 'จำนวนทั้งหมด',  'val' => $totalReferrals,     'color' => 'var(--deep1)', 'soft' => 'color-mix(in srgb, var(--accent1) 16%, transparent)', 'icon' => '👥'],
        ['label' => 'สมาชิกใช้งาน',  'val' => $activeReferrals,    'color' => '#5aa07e',      'soft' => 'rgba(90,160,126,.16)',  'icon' => '✅'],
        ['label' => 'เพิ่มเดือนนี้', 'val' => $thisMonthReferrals, 'color' => '#5689b8',      'soft' => 'rgba(86,137,184,.16)',  'icon' => '📅'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ───────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:17px; font-size:24px;"><i class="fas fa-handshake" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">สมาชิกที่แนะนำทั้งหมด</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">รายชื่อสมาชิกที่คุณแนะนำเข้ามา</div>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('user.mlm.referral'))
                <a href="{{ route('user.mlm.referral') }}" class="tp-btn tp-btn-primary">
                    <i class="fas fa-link" style="font-size:12px;"></i> ลิงก์แนะนำ
                </a>
            @endif
        </div>
    </div>

    {{-- ── สถิติ 3 ใบ ───────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        @foreach($cards as $c)
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="min-width:0;">
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $c['label'] }}</div>
                    </div>
                    <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:20px; background:{{ $c['soft'] }};">{{ $c['icon'] }}</span>
                </div>
                <div class="tp-num" style="font-size:30px; font-weight:800; margin-top:10px; color:{{ $c['color'] }};">{{ number_format($c['val']) }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── รายชื่อสมาชิก (ตาราง) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <div class="tp-section-h">📋 รายชื่อสมาชิก</div>
            @if(\Illuminate\Support\Facades\Route::has('user.mlm.referral'))
                <a href="{{ route('user.mlm.referral') }}" class="tp-btn tp-btn-sm">🔗 ลิงก์แนะนำ</a>
            @endif
        </div>

        @if($directReferrals->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            @foreach(['#','ชื่อ','รหัสสมาชิก','วันที่เข้าร่วม','สถานะ','การดำเนินการ'] as $h)
                                <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($directReferrals as $index => $referral)
                            @php
                                $st = $referral->status;
                                $stMap = [
                                    'active'  => ['✅ ใช้งาน',   '#5aa07e'],
                                    'pending' => ['⏳ รออนุมัติ', '#e0a52e'],
                                ];
                                $stInfo = $stMap[$st] ?? ['❌ ไม่ใช้งาน', 'var(--ink2)'];
                            @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td class="tp-num" style="padding:12px 16px; white-space:nowrap; color:var(--ink2);">
                                    {{ ($directReferrals->currentPage() - 1) * $directReferrals->perPage() + $index + 1 }}
                                </td>
                                <td style="padding:12px 16px;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="tp-tile" style="width:38px; height:38px; border-radius:50%; font-size:15px; font-weight:800; color:#fff;">{{ strtoupper(substr($referral->user->name ?? 'U', 0, 1)) }}</span>
                                        <div style="min-width:0;">
                                            <div style="font-weight:700; color:var(--ink);">{{ $referral->user->name ?? 'N/A' }}</div>
                                            <div style="font-size:11.5px; color:var(--ink2);">{{ $referral->user->email ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <code class="tp-num tp-pill tp-pill-soft" style="font-size:11.5px;">{{ $referral->member_code }}</code>
                                </td>
                                <td class="tp-num" style="padding:12px 16px; white-space:nowrap;">
                                    <div style="color:var(--ink);">{{ $referral->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11px; color:var(--ink2);">{{ $referral->created_at->diffForHumans() }}</div>
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <span class="tp-pill" style="color:{{ $stInfo[1] }}; background:color-mix(in srgb, {{ $stInfo[1] }} 16%, transparent);">{{ $stInfo[0] }}</span>
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <button type="button" class="tp-btn tp-btn-sm">ดูรายละเอียด</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($directReferrals->hasPages())
                <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    {{ $directReferrals->links() }}
                </div>
            @endif
        @else
            <div style="text-align:center; padding:56px 20px;">
                <div style="font-size:52px; opacity:.5;">🤝</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ยังไม่มีสมาชิกที่แนะนำ</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เริ่มแนะนำเพื่อนเข้าร่วมเพื่อสร้างทีมของคุณ</div>
                @if(\Illuminate\Support\Facades\Route::has('user.mlm.referral'))
                    <a href="{{ route('user.mlm.referral') }}" class="tp-btn tp-btn-primary" style="margin-top:16px;">รับลิงก์แนะนำ →</a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
