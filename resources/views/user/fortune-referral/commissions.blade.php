{{-- resources/views/user/fortune-referral/commissions.blade.php --}}
@extends('layouts.user-v4')

@section('title', 'คอมมิชชั่นดูดวง')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #7c5cbf 18%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px; background:#7c5cbf;"><span style="color:#fff;">🔮</span></span>
                <div style="flex:1; min-width:200px;">
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">คอมมิชชั่นดูดวง</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">
                        รายได้จากการแนะนำเพื่อนมาดูดวง — Level 1 (สายตรง) และ Level 2 (ชั้นหลาน)
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── การ์ดสถิติ ───────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="font-size:18px;">💰</span>
                <span style="font-size:11px; font-weight:600; color:var(--ink2);">รวมทั้งหมด</span>
            </div>
            <div class="tp-num" style="font-size:24px; font-weight:800; color:var(--deep1);">{{ number_format($stats['total'], 2) }} <span style="font-size:13px; font-weight:500; color:var(--ink2);">บาท</span></div>
        </div>
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="font-size:18px;">🤝</span>
                <span style="font-size:11px; font-weight:600; color:var(--ink2);">Level 1 สายตรง</span>
            </div>
            <div class="tp-num" style="font-size:24px; font-weight:800; color:#5aa07e;">{{ number_format($stats['level1'], 2) }} <span style="font-size:13px; font-weight:500; color:var(--ink2);">บาท</span></div>
        </div>
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="font-size:18px;">👶</span>
                <span style="font-size:11px; font-weight:600; color:var(--ink2);">Level 2 หลาน</span>
            </div>
            <div class="tp-num" style="font-size:24px; font-weight:800; color:#d9a441;">{{ number_format($stats['level2'], 2) }} <span style="font-size:13px; font-weight:500; color:var(--ink2);">บาท</span></div>
        </div>
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="font-size:18px;">📅</span>
                <span style="font-size:11px; font-weight:600; color:var(--ink2);">เดือนนี้</span>
            </div>
            <div class="tp-num" style="font-size:24px; font-weight:800; color:#5689b8;">{{ number_format($stats['this_month'], 2) }} <span style="font-size:13px; font-weight:500; color:var(--ink2);">บาท</span></div>
        </div>
    </div>

    {{-- ── ตัวกรองชั้น ───────────────────────────────────────── --}}
    <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <a href="{{ route('user.fortune-referral.commissions', ['level' => 'all']) }}"
           class="tp-btn {{ $levelFilter === 'all' ? 'tp-btn-primary' : '' }} tp-btn-sm">ทั้งหมด</a>
        <a href="{{ route('user.fortune-referral.commissions', ['level' => '1']) }}"
           class="tp-btn tp-btn-sm" style="{{ $levelFilter === '1' ? 'background:#5aa07e; border-color:#5aa07e; color:#fff;' : '' }}">🤝 Level 1 สายตรง</a>
        <a href="{{ route('user.fortune-referral.commissions', ['level' => '2']) }}"
           class="tp-btn tp-btn-sm" style="{{ $levelFilter === '2' ? 'background:#d9a441; border-color:#d9a441; color:#fff;' : '' }}">👶 Level 2 หลาน</a>
    </div>

    {{-- ── ตารางคอมมิชชั่น ──────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="box-shadow:var(--inset-sm);">
                        <th style="padding:12px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">วันที่</th>
                        <th style="padding:12px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">จากใคร</th>
                        <th style="padding:12px 14px; text-align:center; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">ชั้น</th>
                        <th style="padding:12px 14px; text-align:center; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">ประเภท</th>
                        <th style="padding:12px 14px; text-align:right; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">ราคาดูดวง</th>
                        <th style="padding:12px 14px; text-align:right; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">คอมมิชชั่น</th>
                        <th style="padding:12px 14px; text-align:center; font-size:11px; font-weight:600; color:var(--ink2); text-transform:uppercase;">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $commission)
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="padding:12px 14px; white-space:nowrap; color:var(--ink);">{{ $commission->created_at->format('d/m/Y H:i') }}</td>
                            <td style="padding:12px 14px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <img src="{{ $commission->fromUser->profile_picture_url ?? 'https://ui-avatars.com/api/?name=U&background=6366f1&color=fff&size=32' }}"
                                         alt="" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                                    <div>
                                        <div style="font-weight:600; color:var(--ink);">{{ $commission->fromUser->name ?? 'ไม่ทราบ' }}</div>
                                        <div style="font-size:11px; color:var(--ink2);">บิล #{{ $commission->fortune_reading_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:12px 14px; text-align:center;">
                                @if($commission->level === 1)
                                    <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; color:#5aa07e; background:color-mix(in srgb, #5aa07e 16%, transparent);">L1 สายตรง</span>
                                @else
                                    <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; color:#d9a441; background:color-mix(in srgb, #d9a441 16%, transparent);">L2 หลาน</span>
                                @endif
                            </td>
                            <td style="padding:12px 14px; text-align:center; font-size:12px; color:var(--ink2);">
                                {{ $commission->commission_type === 'fixed' ? 'คงที่' : $commission->commission_rate . '%' }}
                            </td>
                            <td style="padding:12px 14px; text-align:right; color:var(--ink2);">{{ number_format($commission->reading_price, 2) }} บาท</td>
                            <td style="padding:12px 14px; text-align:right;">
                                <span class="tp-num" style="font-weight:800; color:var(--deep1);">+{{ number_format($commission->amount, 2) }} บาท</span>
                            </td>
                            <td style="padding:12px 14px; text-align:center;">
                                @switch($commission->status)
                                    @case('paid')
                                        <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; color:#5aa07e; background:color-mix(in srgb, #5aa07e 16%, transparent);">จ่ายแล้ว</span>
                                        @break
                                    @case('pending')
                                        <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; color:#d9a441; background:color-mix(in srgb, #d9a441 16%, transparent);">รอดำเนินการ</span>
                                        @break
                                    @case('approved')
                                        <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; color:#5689b8; background:color-mix(in srgb, #5689b8 16%, transparent);">อนุมัติ</span>
                                        @break
                                    @default
                                        <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; color:#d9534f; background:color-mix(in srgb, #d9534f 16%, transparent);">ปฏิเสธ</span>
                                @endswitch
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px 20px; text-align:center;">
                                <div style="display:flex; flex-direction:column; align-items:center; gap:12px;">
                                    <span style="font-size:46px;">🔮</span>
                                    <p style="color:var(--ink2);">ยังไม่มีคอมมิชชั่นดูดวง</p>
                                    <a href="{{ route('user.fortune-referral.recruit') }}" class="tp-btn tp-btn-primary" style="background:#7c5cbf; border-color:#7c5cbf;">ชวนเพื่อนมาดูดวง</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($commissions->hasPages())
            <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                {{ $commissions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
